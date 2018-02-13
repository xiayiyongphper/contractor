<?php
namespace common\models\contractor;

use common\models\LeContractor;
use framework\components\ToolsAbstract;

/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/7/20
 * Time: 14:51
 */
class TargetHelper
{
    /**
     * @var LeContractor
     */
    private $contractor;
    /**
     * @var int
     */
    private $targetContractorId;
    /**
     * @var int
     */
    private $city;

    /**
     *
     * @var array
     */
    private $metrics;

    /**
     * 强制获取更新历史
     * @var bool
     */
    private $forceGetHistory = false;

    /**
     * 手动更新的是否加上更新时间
     * @var bool
     */
    private $forceConcatUpdateTimeIfManual = true;

    /**
     * 是否是普通业务员
     * @var bool
     */
    private $isRegularContractor = true;

    /**
     * @var array
     */
    private $params = [];

    /**
     * TargetHelper constructor.
     * @param LeContractor $contractor
     * @param int $targetContractorId
     * @param int $city
     */
    public function __construct(LeContractor $contractor, $targetContractorId, $city)
    {
        $this->contractor = $contractor;
        $this->targetContractorId = $targetContractorId;
        $this->city = $city;
        $this->isRegularContractor = ($contractor->entity_id == $targetContractorId ? true : false);
    }

    /**
     *
     * @param string $date yyyy-MM格式
     * @param int $lastDays 最近N天
     * @param array $params
     * @return array
     */
    public function getTargets($date, $lastDays = 14, $params = [])
    {
        $ret = [];
        $this->params = $params;
        $month = (int)str_replace('-', '', $date);
        if (!$this->isRegularContractor) {  // 不是普通业务员
            $targets = ContractorTasks::getTasksByOwnerIdMonth($this->targetContractorId, $month, $this->city);
        } else {    // 是普通业务员
            $targets = ContractorTasks::getTasksByOwnerIdMonth($this->targetContractorId, $month, $this->city);
        }

        if (!$targets) {
            return $ret;
        }

        /** @var ContractorTasks $target */
        foreach ($targets as $target) {
            if ($formatTarget = $this->formatTarget($target, $month, $lastDays)) {
                $ret[] = $formatTarget;
            }
        }
        return $ret;
    }

    /**
     * @param ContractorTasks $target
     * @param string $month yyyyMM格式
     * @param int $lastDays 最近N天
     * @return null|array
     */
    private function formatTarget(ContractorTasks $target, $month, $lastDays = 14)
    {
        $ret['target'] = [
            'base_value' => $target->base_value,
            'target_value' => $target->target_value,
            'perfect_value' => $target->perfect_value,
            'task_id' => $target->entity_id,
            'current_value' => 0,
            'contractor_id' => $target->owner_id,
            'city' => $target->city,
            'metric_id' => $target->metric_id,
        ];

        /* 不是本月的不显示历史记录！!! */
        $isGetHistory = ToolsAbstract::getDate()->date('Ym') == $month ? true : false;
        /* 指标名称，找不到指标项直接返回null */
        $metric = ContractorMetrics::findOne(['entity_id' => $target->metric_id]);
        if (!$metric) {
            return null;
        }

        $this->metrics[$metric->entity_id] = $metric; // 存起来
        $ret['target']['metric'] = $metric->name;
        if (!$this->isRegularContractor) {
            $ret['can_update'] = ContractorMetrics::isManualType($metric->type);
        }
        $histories = [];
        /* 获取近两周明细（因为要同比环比） */
        if ($metric->type & ContractorMetrics::TYPE_AUTO) { // 展示明细
            if ($isGetHistory && ContractorMetrics::isShowHistoryType($metric->type)) {
                $ret['history_title'] = '近一周数据';
                $ret['history_chart_columes'] = $this->getChartColumes($target, $metric);

                $histories = ContractorTaskHistory::getHistoryByCityOwnerIdMetricIdLastDays(
                    $target->city,
                    $target->owner_id,
                    $target->metric_id,
                    $lastDays
                );
            }
            $ret['target']['current_value'] = $this->getCurrentValue($metric, $target);
        } else {
            $histories = ContractorTaskHistory::getMonthManualHistory(
                $target->city,
                $target->owner_id,
                $target->metric_id,
                $month
            );
            if ($histories) {
                $ret['target']['current_value'] = $histories[0]->value;
                $ret['target']['history_id'] = $histories[0]->entity_id;
                /* 判断是否强制获取明细 */
                if ($isGetHistory && $this->getForceGetHistory()) {
                    /* 修正日期，为了后面的formatHistoryList能用而且目前手动更新返回的histories还没用到 */
                    $timestamp = ToolsAbstract::getDate()->timestamp();
                    $curDate = date('Y-m-d', $timestamp);
                    foreach ($histories as $key => $history) {
                        if ($key === 0 && $history->date < $curDate) { // 没有今天的
                            $timestamp -= 86400;
                        }
                        $history->date = date('Y-m-d', $timestamp);
                        $histories[$key] = $history;
                        $timestamp -= 86400;    // 前一天
                    }
                }
            }
        }

        /* 若是当前值已经超过标杆值，则用当前值来标记进度条的尾端 */
        /*
        if ($ret['target']['current_value'] > $ret['target']['perfect_value']) {
            $ret['target']['perfect_value'] = $ret['target']['current_value'];
        }*/

        if (ContractorMetrics::isManualType($metric->type) && $this->getForceConcatUpdateTimeIfManual() && $histories) {
            $ret['target']['metric'] .= '(更新时间：' . substr($histories[0]->updated_at, 5, 5) . ')';
        }

        if ($isGetHistory && (ContractorMetrics::isShowHistoryType($metric->type) || $this->getForceGetHistory())) {
            list(/* $last7DaysTotal */, $last7DaysHistories) = $this->formatHistoryList($histories);
            $thisMonthFirstDay = ToolsAbstract::getDate()->date('Y-m-01');
            /** @var ContractorTaskHistory $history */
            foreach ($last7DaysHistories as $history) {
                if ($history->date < $thisMonthFirstDay) {  // 不是本月的不显示
                    continue;
                }
                $ret['histories'][] = [
                    'date' => preg_replace('/^\d{4}-0?(\d{1,2})-0?(\d{1,2})$/', '$1月$2日', $history->date),
                    'value' => $this->formatTargetValue($history->value, $metric),
                    'today_on_yesterday' => $history->today_on_yesterday,
                    'today_on_lastweek' => $history->today_on_lastweek
                ];
            }
        }
        return $ret;
    }

    /**
     * @param ContractorTasks $target
     * @param ContractorMetrics $metric
     * @return array
     */
    private function getChartColumes(ContractorTasks $target, $metric)
    {
        return ['日期', $metric ? $metric->name : ' ', '环比昨日', '同比上周'];
    }

    /**
     * 获取格式化后的历史明细（同比上周，环比昨天）
     *
     * @param array $histories 保证数组是按照日期升序排的
     * @return array
     */
    public function formatHistoryList(array $histories)
    {
        if (empty($histories)) {
            return [0, []];
        }

        $last7DaysTotal = 0;
        $last7DaysFlag = 0;
        $last7DaysHistories = [];   // 近7天的
        $before7DaysHistories = []; // 7天前的
        $last7DaysEndDateTime = ToolsAbstract::getDate()->date('Y-m-d 23:59:59');
        $last7DaysStartDateTime = date('Y-m-d H:i:s', strtotime('-7 days', strtotime($last7DaysEndDateTime)));
        /** @var ContractorTaskHistory $history */
        foreach ($histories as $history) {
            $dateTimeKey = substr($history->date, 0, 10);
            $yesterdayTimeKey = date('Y-m-d', strtotime('yesterday', strtotime($history->date)));
            $lastWeekTimeKey = date('Y-m-d', strtotime('-7 days', strtotime($history->date)));
            if ($history->date >= $last7DaysStartDateTime && $history->date <= $last7DaysEndDateTime) { // 7天内
                $last7DaysTotal += (float)$history->value;
                /* 同比上周 */
                if (!empty($before7DaysHistories[$lastWeekTimeKey])) {
                    $history->today_on_lastweek = $this->getThisWeekOnLastweek(
                        $history->value,
                        $before7DaysHistories[$lastWeekTimeKey]['value']
                    );
                } else {
                    $history->today_on_lastweek = $this->getThisWeekOnLastweek($history->value, 0);
                }

                /* 同比昨天 */
                if ($last7DaysFlag === 0) { // $lastDateKey 0，说明刚到7天后的数据，所以与七天前的数据对比
                    if (!empty($before7DaysHistories[$yesterdayTimeKey])) {
                        $history->today_on_yesterday = $this->getTodayOnYesterday(
                            $history->value,
                            $before7DaysHistories[$yesterdayTimeKey]['value']
                        );
                    } else {
                        $history->today_on_yesterday = $this->getTodayOnYesterday($history->value, 0);
                    }
                } else {
                    if (!empty($last7DaysHistories[$yesterdayTimeKey])) {
                        $history->today_on_yesterday = $this->getTodayOnYesterday(
                            $history->value,
                            $last7DaysHistories[$yesterdayTimeKey]['value']
                        );
                    } else {
                        $history->today_on_yesterday = $this->getTodayOnYesterday($history->value, 0);
                    }
                }

                $last7DaysFlag |= 1; // 置1
                $last7DaysHistories[$dateTimeKey] = $history;
            } else { // 7天前
                $before7DaysHistories[$dateTimeKey] = $history;
            }
        }

        $last7DaysTotal = number_format($last7DaysTotal, 2, '.', '');
        return [$last7DaysTotal, $last7DaysHistories];
    }

    /**
     * @param string $val
     * @param ContractorMetrics $metric
     * @return string
     */
    private function formatTargetValue($val, $metric)
    {
        $value = preg_replace('/^(\d+)?\.0{1,2}$/', '$1', (string)$val);
        if (!empty($this->params['fromIndex']) && !empty($metric['value_type']) && $metric['value_type'] == 2) {
            return '¥' . $value;
        }
        return $value;
    }

    /**
     * 环比昨日
     *
     * @param ContractorTaskHistory $today
     * @param ContractorTaskHistory $yesterday
     * @return float
     */
    private function getTodayOnYesterday($today, $yesterday)
    {
        $today = (float)$today;
        $yesterday = (float)$yesterday;
        if (empty($yesterday)) {
            $val = $today >= 0.01 ? 100 : 0;
        } else {
            $val = (int)(($today / $yesterday - 1) * 100);
        }
        return $this->getValueHtml($val);
    }

    /**
     * 同比上周
     *
     * @param ContractorTaskHistory $thisWeek
     * @param ContractorTaskHistory $lastWeek
     * @return float
     */
    private function getThisWeekOnLastweek($thisWeek, $lastWeek)
    {
        $thisWeek = (float)$thisWeek;
        $lastWeek = (float)$lastWeek;
        if (empty($lastWeek)) {
            $val = $thisWeek >= 0.01 ? 100 : 0;
        } else {
            $val = (int)(($thisWeek / $lastWeek - 1) * 100);
        }
        return $this->getValueHtml($val);
    }

    /**
     * @param ContractorMetrics $metric
     * @param ContractorTasks $target
     * @return string
     */
    private function getCurrentValue($metric, $target)
    {
        $month = $target->month;
        $curMonth = ToolsAbstract::getDate()->date('Ym');
        if ($target->month == $curMonth) {
            $month = null;
        }

        if ($metric->identifier == ContractorMetrics::ID_DAU) { // DAU取均值
            $value = ContractorTaskHistory::getAvgValueByCityOwnerIdMetricId(
                $target->city,
                $target->owner_id,
                $target->metric_id,
                $month
            );
        } else if ($metric->identifier == ContractorMetrics::ID_STORE_AVG_GMV) {    // 月店均GMV=有效GMV/月下单门店数
            $value = ContractorTaskHistory::getStoreAvgGMV($target->city, $target->owner_id, $month);
        } else {
            $value = ContractorTaskHistory::getTotalValueByCityOwnerIdMetricId(
                $target->city,
                $target->owner_id,
                $target->metric_id,
                $month
            );
        }
        return $this->formatTargetValue($value, $metric);
    }

    /**
     * @param float $val
     * @return string
     */
    private function getValueHtml($val)
    {
        if ($val > 0) {
            return '<font color="#D4372F">+' . $val . '%</font>';
        } else if ($val === 0) {
            return '<font color="#666666">' . $val . '%</font>';
        } else {
            return '<font color="#49C0C4">' . $val . '%</font>';
        }
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setForceGetHistory($bool)
    {
        $this->forceGetHistory = $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function getForceGetHistory()
    {
        return $this->forceGetHistory;
    }

    /**
     * @param $bool
     * @return $this
     */
    public function setForceConcatUpdateTimeIfManual($bool)
    {
        $this->forceConcatUpdateTimeIfManual = $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function getForceConcatUpdateTimeIfManual()
    {
        return $this->forceConcatUpdateTimeIfManual;
    }

    /**
     * @return ContractorMetrics[]
     */
    public function getMetrics()
    {
        return $this->metrics;
    }
}