<?php
/**
 * Created by Jason Y. wang
 * User: wangyang
 * Date: 16-7-21
 * Time: 下午6:02
 */

namespace service\resources\contractor\v1;

use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTaskHistory;
use common\models\contractor\ContractorTasks;
use common\models\contractor\TargetHelper;
use common\models\LeContractor;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\HomeResponse2;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class home2 extends Contractor
{
    /**
     * @var ContractorMetrics[]
     */
    private $metrics = [];

    /**
     * @param string $data
     * @return HomeResponse2
     * @throws \Exception
     */
    public function run($data)
    {
        if (version_compare($this->getAppVersion(), '1.6.0', '<')) {
            throw new \Exception('当前使用的版本过低，请升级到最新版本！', 999);
        }
        /** @var ContractorAuthenticationRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);
        $cityList = array_filter(explode('|', $contractor->city_list));
        $city = $request->getCity();

        if (!is_array($cityList) || empty($cityList)) {
            ContractorException::contractorCityEmpty();
        }

        $todayMonth = ToolsAbstract::getDate()->date('Y-m');
        /** @var TargetHelper $targetHelper */
        $targetHelper = null;
        if ($this->isRegularContractor($contractor)) {
            $city = $contractor->city;
            $storeManagerSchema = 'lelaibd://customerStore/manager';
            $orderManagerSchema = 'lelaibd://order/manager';
            $targetHelper = new TargetHelper($contractor, $contractor->entity_id, $city);
        } else if (count($cityList) == 1 || $city) {
            $city = $city ?: current($cityList);
            $storeManagerSchema = 'lelaibd://customerStore/manager?cityId=' . $city;
            $orderManagerSchema = 'lelaibd://order/manager?cityId=' . $city;
            $targetHelper = new TargetHelper($contractor, 0, $city); // 城市指标传0
        } else {
            $storeManagerSchema = 'lelaibd://customerStore/manager';
            $orderManagerSchema = 'lelaibd://order/manager';
        }

        /* TMD什么鬼需求！！！多个城市例外统计所有城市的信息，单个城市用$targetHelper获取 */
        if (!empty($targetHelper)) {
            $targetListItemArr = $targetHelper->setForceGetHistory(true)->setForceConcatUpdateTimeIfManual(false)
                ->getTargets($todayMonth, 14, ['fromIndex' => 1]);
            $metrics = $targetHelper->getMetrics();
        } else {
            $targetListItemArr = $this->getTargets($contractor, $cityList, $todayMonth);
            $metrics = $this->metrics;
        }

        $targetListItemArr = $targetListItemArr ?: [];
        /* 新增的需求！！！！获取订单数，有则合并 7-27 */
        $orderTargets = $this->getOrderTargets($contractor, $city ?: $cityList);
        if ($orderTargets) {
            $targetListItemArr = array_merge_recursive($targetListItemArr, $orderTargets);
        }

        $responseData = [
            'detail_url' => 'http://data-stats.lelai.com/site/login',
            'quick_entry' => $this->getQuickEntry($storeManagerSchema, $orderManagerSchema),
        ];

        $this->setData($responseData, $targetListItemArr, $metrics);
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    /**
     * 获取快捷入口
     * @param string $storeManagerSchema
     * @param string $orderManagerSchema
     * @return array
     */
    private function getQuickEntry($storeManagerSchema, $orderManagerSchema)
    {
        return [
            [
                'name' => '超市',
                'icon' => 'http://assets.lelai.com/assets/contractor/chaoshi.png',
                'schema' => $storeManagerSchema,
            ],
            [
                'name' => '订单',
                'icon' => 'http://assets.lelai.com/assets/contractor/dingdan.png',
                'schema' => $orderManagerSchema,
            ],
            [
                'name' => '工作',
                'icon' => 'http://assets.lelai.com/assets/contractor/gongzuo.png',
                'schema' => 'lelaibd://business/manager',
            ],
            [
                'name' => '业绩中心',
                'icon' => 'http://assets.lelai.com/assets/contractor/yejizhongxin.png',
                'schema' => 'lelaibd://pager/targetCenter',
            ],
            [
                'name' => '设置',
                'icon' => 'http://assets.lelai.com/assets/contractor/shezhi.png',
                'schema' => 'lelaibd://pager/setting',
            ],
        ];
    }

    /**
     * @param array $responseData
     * @param array $targetListItemArr
     * @param $metrics
     */
    private function setData(&$responseData, $targetListItemArr, $metrics)
    {
        $data = [
            ['title' => '今日', 'data' => []],
            ['title' => '昨日', 'data' => []]
        ];

        if (!$targetListItemArr) {
            $responseData['data'] = $data;
            return;
        }

        $yesterdayTimestamp = ToolsAbstract::getDate()->timestamp('-1 days');
        $today = ToolsAbstract::getDate()->date('n月j日');
        $yesterday = date('n月j日', $yesterdayTimestamp);
        foreach ($targetListItemArr as $targetListItem) {
            $target = $targetListItem['target'];
            $responseData['month_data'][] = [
                'value' => (string)$target['current_value'],
                'key' => (string)$target['metric']
            ];

            $metric = isset($metrics[$target['metric_id']]) ? $metrics[$target['metric_id']] : null;
            if ($metric && ($metric['type'] & ContractorMetrics::NOT_SHOW_DAYS_ITEM_FROM_HOME)) {
                continue;
            }

            /* 如果没有历史明细，也显示指标及昨天今天的值，其值都为0 */
            if (empty($targetListItem['histories'])) {
                $tmp = $this->getEmptyHistoryData($target, $metric);
                $data[0]['data'][] = $tmp;
                $data[1]['data'][] = $tmp;
            } else {
                $flag = [0, 0];
                $index = 0;
                foreach ($targetListItem['histories'] as $history) {
                    $formatDate = preg_replace('/^\d{4}-0?(\d{1,2})-0?(\d{1,2})$/', '$1月$2日', $history['date']);
                    if ($today == $formatDate) {
                        $data[0]['data'][] = $this->getHistoryData($target, $metric, $history['value'],
                            $history['today_on_yesterday'], $history['today_on_lastweek']);
                        $flag[0] = 1;
                    } elseif ($yesterday == $formatDate) {
                        $data[1]['data'][] = $this->getHistoryData($target, $metric, $history['value'],
                            $history['today_on_yesterday'], $history['today_on_lastweek']);
                        $flag[1] = 1;
                    }

                    if (++$index == count($targetListItem['histories'])) {
                        if ($flag[0] == 0) {
                            $data[0]['data'][] = $this->getEmptyHistoryData($target, $metric);
                        }
                        if ($flag[1] == 0) {
                            $data[1]['data'][] = $this->getEmptyHistoryData($target, $metric);
                        }
                    }
                }
            }
        }
        $responseData['data'] = $data;
    }

    /**
     *
     * @param array $target
     * @param array $metric
     * @return array
     */
    private function getEmptyHistoryData($target, $metric)
    {
        return $this->getHistoryData($target, $metric, 0, '0%', '0%');
    }

    /**
     *
     * @param array $target
     * @param array $metric
     * @param string $curValue
     * @param string $yesterdayOnToday
     * @param string $todayOnLastweek
     * @return array
     */
    private function getHistoryData($target, $metric, $curValue, $yesterdayOnToday, $todayOnLastweek)
    {
        $ret = [
            'name' => $target['metric'],
            'value' => $this->formatTargetValue($curValue, $metric)
        ];
        if (empty($target['can_update'])) {
            $ret['compare_data'] = [
                ['key' => '昨天', 'value' => strip_tags($yesterdayOnToday)],
                ['key' => '上周', 'value' => strip_tags($todayOnLastweek)],
            ];
        }
        return $ret;
    }

    /**
     * @param string $value
     * @param array $metric
     * @return string
     */
    private function formatTargetValue($value, $metric)
    {
        if (is_numeric($value) && strpos((string)$value, '.') !== false) {
            $value = number_format($value, 2, '.', '');
        }

        $value = preg_replace('/^(\d+)?\.0{1,2}$/', '$1', (string)$value);
        if (!empty($metric['value_type']) && $metric['value_type'] == 2
            && mb_strpos($value, '¥', 0, "UTF-8") === false
        ) {
            return '¥' . $value;
        }
        return $value;
    }

    /**
     * @param LeContractor $contractor
     * @param array $cityList
     * @return array|null
     */
    private function getOrderTargets($contractor, $cityList)
    {
        $metric = ContractorMetrics::findOne(['identifier' => 'order_count']);
        if (empty($metric)) {
            return null;
        }
        return $this->getTargetsByMetricIds($contractor, $metric->entity_id, $cityList);
    }

    /**
     * @param LeContractor $contractor
     * @param array $cityList
     * @param string $todayMonth
     * @return array|null
     */
    private function getTargets($contractor, $cityList, $todayMonth)
    {
        $month = (int)str_replace('-', '', $todayMonth);
        /* 获取这些城市的维度 */
        $metricIds = ContractorTasks::find()->select('metric_id')
            ->where(['city' => $cityList, 'month' => $month, 'owner_id' => 0])
            ->groupBy('metric_id')->column();

        if (empty($metricIds)) {
            return null;
        }

        return $this->getTargetsByMetricIds($contractor, $metricIds, $cityList);
    }


    /**
     * @param LeContractor $contractor
     * @param array|int $metricIds
     * @param array $cityList
     * @return array
     */
    private function getTargetsByMetricIds($contractor, $metricIds, $cityList)
    {
        $metricsArr = ContractorMetrics::find()->where(['entity_id' => $metricIds])->all();
        /** @var ContractorMetrics $metric */
        $currentMetrics = [];
        foreach ($metricsArr as $metric) {
            $currentMetrics[$metric->entity_id] = $metric;
            $this->metrics[$metric->entity_id] = $metric;
        }

        /* 获取明细总额 */
        $startDateTime = ToolsAbstract::getDate()->date('Y-m-01 00:00:00');
        $endDateTime = ToolsAbstract::getDate()->date('Y-m-d 23:59:59');
        $ownerId = $this->isRegularContractor($contractor) ? $contractor->entity_id : 0;
        $histories = ContractorTaskHistory::find()->select('metric_id,date,sum(value) as value')
            ->where(['city' => $cityList, 'owner_id' => $ownerId, 'metric_id' => $metricIds])
            ->andWhere(['>=', 'date', $startDateTime])
            ->andWhere(['<=', 'date', $endDateTime])
            ->groupBy('metric_id,date')->orderBy('metric_id asc,date asc')->all();

        $totals = [];
        $metricHistories = [];
        /** @var ContractorTaskHistory $history */
        foreach ($histories as $history) {
            if (isset($totals[$history->metric_id])) {
                $totals[$history->metric_id] += (float)$history->value;
            } else {
                $totals[$history->metric_id] = (float)$history->value;
            }
            $metricHistories[$history->metric_id][] = $history;
        }

        /* 手动的要获取最新的，而不是统计所有的。。坑爹的~~·*/
        $manualTotals = [];
        $_cityList = $cityList;
        if (!is_array($cityList)) {
            $_cityList = [$cityList];
        }
        foreach ($_cityList as $cityCode) {
            foreach ($currentMetrics as $_metricId => $_metric) {
                $isManualType = $_metric['type'] & ContractorMetrics::TYPE_MANUAL;
                if (!$isManualType) {
                    continue;
                }
                if (!isset($manualTotals[$_metricId][$cityCode])) {
                    $res = ContractorTaskHistory::getMonthLastManualHistory($cityCode, $ownerId, $_metricId);
                    if ($res) {
                        $manualTotals[$_metricId][$cityCode] = $res->value;
                    } else {
                        $manualTotals[$_metricId][$cityCode] = 0;
                    }
                }
            }
        }

        foreach ($manualTotals as $manualMetricId => $cityValues) {
            $totals[$manualMetricId] = array_sum($cityValues);
        }

        /* 月店均GMV和dau取均值。。。 */
        $storeAvgGMVMetricId = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::ID_STORE_AVG_GMV);
        $dauMetricId = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::ID_DAU);
        if ($storeAvgGMVMetricId && is_array($metricIds) && in_array($storeAvgGMVMetricId, $metricIds)) {
            $totals[$storeAvgGMVMetricId] = ContractorTaskHistory::getStoreAvgGMV($cityList, $ownerId);
        }
        if ($dauMetricId && isset($totals[$dauMetricId])) {
            $firstDayDateTime = ToolsAbstract::getDate()->date('Y-m-01 00:00:00');
            $firstDayTimestamp = strtotime($firstDayDateTime);
            $curTimestamp = ToolsAbstract::getDate()->timestamp();
            $days = ceil(($curTimestamp - $firstDayTimestamp) / 86400);
            $totals[$dauMetricId] = $totals[$dauMetricId] / ($days < 1 ? 1 : $days);
        }

        $ret = [];
        $targetHelper = new TargetHelper($contractor, 0, $cityList);
        foreach ($currentMetrics as $metricId => $metric) {
            if (!empty($metricHistories[$metricId])) {
                list(/* - */, $last7DaysHistories) = $targetHelper->formatHistoryList($metricHistories[$metricId]);
                $ret[] = [
                    'target' => [
                        'metric_id' => $metricId,
                        'metric' => $this->metrics[$metricId]['name'],
                        'can_update' => ContractorMetrics::isManualType($this->metrics[$metricId]['type']),
                        'current_value' => $this->formatTargetValue($totals[$metricId], $this->metrics[$metricId])
                    ],
                    'histories' => $last7DaysHistories
                ];
            } else {
                $total = isset($totals[$metricId]) ? $totals[$metricId] : 0;
                $ret[] = [
                    'target' => [
                        'metric_id' => $metricId,
                        'metric' => $this->metrics[$metricId]['name'],
                        'can_update' => ContractorMetrics::isManualType($this->metrics[$metricId]['type']),
                        'current_value' => $this->formatTargetValue($total, $this->metrics[$metricId])
                    ],
                    'histories' => []
                ];
            }
        }
        return $ret;
    }

    /**
     * 是否普通业务员
     *
     * @param LeContractor $contractor
     * @return bool
     */
    private function isRegularContractor(LeContractor $contractor)
    {
        return $contractor->role === self::COMMON_CONTRACTOR;
    }

    public static function request()
    {
        return new ContractorAuthenticationRequest();
    }

    public static function response()
    {
        return new HomeResponse2();
    }

}
