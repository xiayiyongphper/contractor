<?php
/**
 *
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:22
 */

namespace service\tasks;

use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTaskHistory;
use common\models\LeContractor;
use framework\components\ToolsAbstract;
use framework\tasks\TaskAbstract;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * 初始化各个指标的每天的初始值，每次执行初始化一个月的初始值，默认初始化为0
 * Class initialTaskHistory
 * @package service\tasks
 * @author henryzhu <zhuxiaojiang@lelai.com>
 * @timestamp 2017-07-27 19:09
 */
class initialTaskHistory extends TaskAbstract
{
    public function run($data)
    {
        $this->log('initialTaskHistoryTask-start');
        $date = ToolsAbstract::getDate();
        $data = LeContractor::find()->addSelect(['entity_id', 'city'])->where(['status' => 1])->asArray()->all();
        $targetType = ContractorMetrics::TYPE_MANUAL;
        $query = ContractorMetrics::find()->addSelect(['entity_id'])->where(new Expression("`type`&$targetType=0"));
        $metricIds = $query->asArray()->all();
        $metricIds = ArrayHelper::getColumn($metricIds, 'entity_id');
        foreach ($data as $value) {
            $city = $value['city'];
            $newKey = $city . '_';//计算每个城市的小计
            if (!isset($data[$newKey])) {
                $data[$newKey] = [
                    'city' => $city,
                    'entity_id' => 0,
                ];
            }
        }

        $monthLastDay = (int)date('t');//获取当前月的最后一天
        $this->log($monthLastDay);
        //每次初始化一周
        foreach ($metricIds as $metricId) {
            foreach ($data as $value) {
                $contractorId = $value['entity_id'];
                $contractorCity = $value['city'];
                foreach ($this->getNextDays(7) as $day) {
                    $history = ContractorTaskHistory::findOne(['city' => $contractorCity, 'owner_id' => $contractorId, 'metric_id' => $metricId, 'date' => $day]);
                    if (!$history) {
                        $this->log(sprintf('city:%s,owner_id:%s,metric_id:%s,day:%s', $contractorCity, $contractorId, $metricId, $day));
                        $history = new ContractorTaskHistory();
                        $history->value = 0;
                        $history->date = $day;
                        $history->city = $contractorCity;
                        $history->owner_id = $contractorId;
                        $history->metric_id = $metricId;
                        $history->updated_at = $date->date();
                        $history->save();
                    }
                }
            }
        }
        $this->log('initialTaskHistoryTask-finished');
    }

    protected function getLogFileName()
    {
        if (!$this->logFileName) {
            $this->logFileName = $this->getDefaultLogFileName();
        }
        return $this->logFileName;
    }
}