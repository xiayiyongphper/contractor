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
use common\models\LeCustomers;
use framework\components\Date;
use framework\components\ToolsAbstract;
use framework\db\readonly\models\core\Dau as DauModel;
use framework\tasks\TaskAbstract;
use yii\db\Expression;

/**
 * 统计日活数据
 * Class dau
 * @package service\tasks
 * @author henryzhu <zhuxiaojiang@lelai.com>
 * @timestamp 2017-07-27 11:13
 */
class dau extends TaskAbstract
{
    const METRIC_IDENTIFIER = 'dau';

    public function run($data)
    {
        $this->log('dauTask-start');
        $metricId = ContractorMetrics::getMetricIdByIdentifier(self::METRIC_IDENTIFIER);
        if ($metricId === false) {
            $this->log(sprintf('Metric:%s not found', self::METRIC_IDENTIFIER));
            return false;
        }
        $date = ToolsAbstract::getDate();
        $max = 1;
        for ($i = 0; $i < $max; $i++) {
            $day = $date->date("Y-m-d", strtotime('-' . $i . 'day'));//当天日期
            $startDate = $date->date("Y-m-d 00:00:00", strtotime("-$i day"));//几天前
            $endDate = $date->date("Y-m-d 00:00:00", strtotime('-' . ($i - 1) . 'day'));//几天前
            $this->log($day . '-start');
            $query = DauModel::find()
                ->addSelect(['customer_id'])
                ->where(['type' => 1])
                ->andWhere(['>', 'customer_id', 0])
                ->andWhere(['>=', 'created_at', $startDate])
                ->andWhere(['<=', 'created_at', $endDate])
                ->groupBy('customer_id');
            $ids = $query->asArray()->column();
            $this->log($query->createCommand()->getRawSql());
            $this->processDaily($metricId, $day, $ids);
            $this->log($day . '-end');
        }
        $this->log('dauTask-finished');
    }

    protected function processDaily($metricId, $day, $ids)
    {
        $query = LeCustomers::find()
            ->addSelect(new Expression('COUNT(*) as dau_count'))
            ->addSelect(['city', 'contractor_id'])
            ->where(['entity_id' => $ids])
            ->groupBy(['contractor_id']);
        $data = $query->asArray()->all();
        $this->log($data);

        if (count($data) === 0) {
            $this->log(sprintf('Metric:%s does not match any data.SQL:%s', self::METRIC_IDENTIFIER, $query->createCommand()->getRawSql()));
            return false;
        }

        foreach ($data as $key => $value) {
            $city = $value['city'];
            $dauCount = $value['dau_count'];
            $newKey = $city . '_';//计算每个城市的小计
            if (!isset($data[$newKey])) {
                $data[$newKey] = [
                    'city' => $city,
                    'dau_count' => $dauCount,
                    'contractor_id' => 0,
                ];
            } else {
                $data[$newKey]['dau_count'] += $dauCount;
            }
            //未分配业务员的数据，只属于城市
            if (!isset($value['contractor_id']) || !$value['contractor_id']) {
                unset($data[$key]);
            }
        }

        $date = new Date();
        foreach ($data as $value) {
            $city = $value['city'];
            $dauCount = $value['dau_count'];
            $contractorId = $value['contractor_id'];
            $history = ContractorTaskHistory::findOne(['city' => $city, 'owner_id' => $contractorId, 'metric_id' => $metricId, 'date' => $day]);
            if (!$history) {
                $history = new ContractorTaskHistory();
                $history->value = $dauCount;
                $history->date = $day;
                $history->city = $city;
                $history->owner_id = $contractorId;
                $history->metric_id = $metricId;
            } else {
                $history->value = $dauCount;
            }
            $history->updated_at = $date->date();
            $history->save();
            if (count($history->errors) > 0) {
                $this->log('We have encounter an unhandled error!');
                $this->log($history->toArray());
                $this->log($history->errors);
            }
        }
    }

    protected function getLogFileName()
    {
        if (!$this->logFileName) {
            $this->logFileName = $this->getDefaultLogFileName();
        }
        return $this->logFileName;
    }
}