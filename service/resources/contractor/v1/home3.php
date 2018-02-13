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


class home3 extends Contractor
{

    /**
     * @param string $data
     * @return HomeResponse2
     * @throws \Exception
     */
    public function run($data)
    {
        /** @var ContractorAuthenticationRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);
        $contractor_city = $contractor->city;

        $cityList = array_filter(explode('|', $contractor->city_list));
        if (!is_array($cityList) || empty($cityList)) {
            ContractorException::contractorCityEmpty();
        }
        //需要管理统计的城市
        $city = $request->getCity();

        $month = ToolsAbstract::getDate()->date('Ym');

        $current_day = ToolsAbstract::getDate()->date('d');

        //今天，昨天，前天，上周，昨天的上周
        $monthStart = ToolsAbstract::getDate()->date('Y-m-01'); //本月1号
        $today = ToolsAbstract::getDate()->date('Y-m-d'); //今天
        $yesterday = ToolsAbstract::getDate()->date('Y-m-d', strtotime('-1 day')); //昨天
        $dayBeforeYesterday = ToolsAbstract::getDate()->date('Y-m-d', strtotime('-2 day')); //前天
        $lastWeek = ToolsAbstract::getDate()->date('Y-m-d', strtotime('-7 day')); //上周
        $yesterdayLastWeek = ToolsAbstract::getDate()->date('Y-m-d', strtotime('-8 day')); //昨天的上周

        $responseData = [];
        $today_data_total['title'] = '今天';
        $yesterday_data_total['title'] = '昨天';
        if ($this->isRegularContractor($contractor)) {
            //单个业务员需要统计的指标
            $metricIds = ContractorTasks::find()->select('metric_id')
                ->where(['month' => $month, 'owner_id' => $contractor->entity_id, 'city' => $contractor_city])
                ->groupBy('metric_id')->column();
            $city_condition = "and city = {$contractor_city}";
            $owner_condition = "and owner_id = {$contractor->entity_id}";
            $owner_id = $contractor->entity_id;
            $city_list_filter = $contractor_city;
        } else {
            $owner_condition = "and owner_id = 0";
            $owner_id = 0;
            if ($city) {
                $city_condition = "and city = {$city}";
                $city_filter = $city;
            } else {
                $city_condition = '(' . implode(',', $cityList) . ')';
                $city_condition = "and city in {$city_condition}";
                $city_filter = $cityList;
            }
            $city_list_filter = $city_filter;
            //城市需要统计的指标
            $metricIds = ContractorTasks::find()->select('metric_id')
                ->where(['month' => $month, 'owner_id' => 0, 'city' => $city_filter])
                ->groupBy('metric_id')->column();

        }


        //固定显示 有效gmv  零食gmv   订单数
        array_unshift($metricIds, 11);
        array_unshift($metricIds, 2);
        array_unshift($metricIds, 5);

        $metricIds = array_unique($metricIds);

        foreach ($metricIds as $metricId) {
            $contractorMetric = ContractorMetrics::findOne(['entity_id' => $metricId]);

            if (!$contractorMetric) {
                continue;
            }

            //月店均GMV处理方式问题，总gmv除以月下单门店数
            if ($contractorMetric->entity_id == 9) {
                Tools::log($city_list_filter, 'home3.log');
                Tools::log($owner_id, 'home3.log');
                $gmv = ContractorTaskHistory::getStoreAvgGMV($city_list_filter, $owner_id);
                Tools::log($gmv, 'home3.log');
                $responseData['month_data'][] = ['key' => $contractorMetric->name, 'value' => $gmv];
                continue;
            }

            //日均DAU单独处理，不计算当天数据
            if($contractorMetric->entity_id == 6){
                $select = <<<SQL
SELECT month_data.month_data,today_data.today_data,yesterday_data.yesterday_data,day_before_yesterday_data.day_before_yesterday_data,last_week_data.last_week_data,
yesterday_last_week_data.yesterday_last_week_data from 
(SELECT SUM(`value`) as month_data FROM contractor_task_history WHERE `date` >= '$monthStart' and `date` < '$today' and `metric_id` = $metricId $city_condition $owner_condition) as month_data, 
(SELECT SUM(`value`) as today_data FROM contractor_task_history WHERE `date` = '$today'  and `metric_id` = $metricId $city_condition $owner_condition) as today_data,
(SELECT SUM(`value`) as yesterday_data FROM contractor_task_history WHERE `date` = '$yesterday'  and `metric_id` = $metricId $city_condition $owner_condition) as yesterday_data,
(SELECT SUM(`value`) as day_before_yesterday_data FROM contractor_task_history WHERE `date` = '$dayBeforeYesterday'  and `metric_id` = $metricId $city_condition $owner_condition) as day_before_yesterday_data,
(SELECT SUM(`value`) as last_week_data FROM contractor_task_history WHERE `date` = '$lastWeek'  and `metric_id` = $metricId $city_condition $owner_condition) as last_week_data,
(SELECT SUM(`value`) as yesterday_last_week_data FROM contractor_task_history WHERE `date` = '$yesterdayLastWeek'  and `metric_id` = $metricId $city_condition $owner_condition) as yesterday_last_week_data
SQL;
            }else{
                $select = <<<SQL
SELECT month_data.month_data,today_data.today_data,yesterday_data.yesterday_data,day_before_yesterday_data.day_before_yesterday_data,last_week_data.last_week_data,
yesterday_last_week_data.yesterday_last_week_data from 
(SELECT SUM(`value`) as month_data FROM contractor_task_history WHERE `date` >= '$monthStart' and `metric_id` = $metricId $city_condition $owner_condition) as month_data, 
(SELECT SUM(`value`) as today_data FROM contractor_task_history WHERE `date` = '$today'  and `metric_id` = $metricId $city_condition $owner_condition) as today_data,
(SELECT SUM(`value`) as yesterday_data FROM contractor_task_history WHERE `date` = '$yesterday'  and `metric_id` = $metricId $city_condition $owner_condition) as yesterday_data,
(SELECT SUM(`value`) as day_before_yesterday_data FROM contractor_task_history WHERE `date` = '$dayBeforeYesterday'  and `metric_id` = $metricId $city_condition $owner_condition) as day_before_yesterday_data,
(SELECT SUM(`value`) as last_week_data FROM contractor_task_history WHERE `date` = '$lastWeek'  and `metric_id` = $metricId $city_condition $owner_condition) as last_week_data,
(SELECT SUM(`value`) as yesterday_last_week_data FROM contractor_task_history WHERE `date` = '$yesterdayLastWeek'  and `metric_id` = $metricId $city_condition $owner_condition) as yesterday_last_week_data
SQL;
            }



            /** @var \yii\db\Connection $mainDb */
            $mainDb = \Yii::$app->mainDb;
            $queryData = $mainDb->createCommand($select)->queryOne();

            $month_data = $queryData['month_data'] ?: 0;
            $today_data = $queryData['today_data'] ?: 0;
            $yesterday_data = $queryData['yesterday_data'] ?: 0;
            $day_before_yesterday_data = $queryData['day_before_yesterday_data'] ?: 0;
            $last_week_data = $queryData['last_week_data'] ?: 0;
            $yesterday_last_week_data = $queryData['yesterday_last_week_data'] ?: 0;

            //月下单门店数和月店均GMV应该不展示在今日昨日数据中
            if ($contractorMetric->entity_id != 4 && $contractorMetric->entity_id != 9) {
                //今天数据
                $saleData = [];
                $saleData['name'] = $contractorMetric->name;
                $saleData['value'] = $today_data;
                $compare_data = [];
                $compare_data['key'] = '昨天';
                $compare_data_today_yesterday = $yesterday_data != 0 ? ($today_data - $yesterday_data) / $yesterday_data : 0;
                $compare_data['value'] = number_format($compare_data_today_yesterday, 2);
                $saleData['compare_data'][] = $compare_data;
                $compare_data['key'] = '上周';
                $compare_data_week_lastWeek = $last_week_data != 0 ? ($today_data - $last_week_data) / $last_week_data : 0;
                $compare_data['value'] = number_format($compare_data_week_lastWeek, 2);
                $saleData['compare_data'][] = $compare_data;
                $today_data_total['data'][] = $saleData;
                //昨天数据
                $saleData = [];
                $saleData['name'] = $contractorMetric->name;
                $saleData['value'] = $yesterday_data;
                $compare_data = [];
                $compare_data['key'] = '昨天';
                $compare_data_yesterday_beforeYesterday = $day_before_yesterday_data != 0 ? ($yesterday_data - $day_before_yesterday_data) / $day_before_yesterday_data : 0;
                $compare_data['value'] = number_format($compare_data_yesterday_beforeYesterday, 2);
                $saleData['compare_data'][] = $compare_data;

                $compare_data['key'] = '上周';
                $compare_data_lastWeek_beforeLastWeek = $yesterday_last_week_data != 0 ? ($yesterday_data - $yesterday_last_week_data) / $yesterday_last_week_data : 0;
                $compare_data['value'] = number_format($compare_data_lastWeek_beforeLastWeek, 2);
                $saleData['compare_data'][] = $compare_data;
                $yesterday_data_total['data'][] = $saleData;
            }

            //月数据
            if ($contractorMetric->entity_id == 6) {
                $contractorMetric->name = '日均DAU';
                //1号数据为0，看前一天数据
                if ($current_day == 1) {
                    $month_data = 0;
                } else {
                    $month_data = round($month_data / ($current_day - 1), 2);
                }
            }

            if ($contractorMetric->entity_id == 9) {
                //1号数据为0，看前一天数据
                if ($current_day == 1) {
                    $month_data = 0;
                } else {
                    $month_data = round($month_data / ($current_day - 1), 2);
                }
            }
            //解决 类似 0.00 被过滤掉的问题
            if ($month_data == 0) {
                $month_data = 0;
            }

            $responseData['month_data'][] = ['key' => $contractorMetric->name, 'value' => $month_data];
        }

        $responseData['data'][] = $today_data_total;
        $responseData['data'][] = $yesterday_data_total;

        /** @var TargetHelper $targetHelper */
        $targetHelper = null;
        if ($this->isRegularContractor($contractor)) {
            $storeManagerSchema = 'lelaibd://customerStore/manager';
            $orderManagerSchema = 'lelaibd://order/manager';
        } else if (count($cityList) == 1 || $city) {
            $city = $city ?: current($cityList);
            $storeManagerSchema = 'lelaibd://customerStore/manager?cityId=' . $city;
            $orderManagerSchema = 'lelaibd://order/manager?cityId=' . $city;
        } else {
            $storeManagerSchema = 'lelaibd://customerStore/manager';
            $orderManagerSchema = 'lelaibd://order/manager';
        }

        $responseData['detail_url'] = 'http://data-stats.lelai.com/site/login';
        $responseData['quick_entry'] = $this->getQuickEntry($storeManagerSchema, $orderManagerSchema);

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
                'name' => '补货推荐',
                'icon' => 'http://assets.lelai.com/assets/contractor/ludan1.png',
                'schema' => 'lelaibd://record/list'
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
