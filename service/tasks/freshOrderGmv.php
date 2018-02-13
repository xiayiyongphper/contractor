<?php
/**
 * 有效GMV
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:22
 */

namespace service\tasks;

use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTaskHistory;
use framework\components\ToolsAbstract;
use framework\db\readonly\models\core\SalesFlatOrder;
use framework\tasks\TaskAbstract;
use service\components\Tools;
use yii\db\Expression;

class freshOrderGmv extends TaskAbstract
{
    const METRIC_IDENTIFIER = 'valid_gmv';

    public function run($data = null)
    {
        ToolsAbstract::log('freshOrderGmv-start', 'CustomerGmvStat.log');
        $metricId = ContractorMetrics::getMetricIdByIdentifier(self::METRIC_IDENTIFIER);
        if ($metricId === false) {
            ToolsAbstract::log(sprintf('Metric:%s not found', self::METRIC_IDENTIFIER), 'CustomerGmvStat.log');
            return false;
        }

        $startDate = ToolsAbstract::getDate()->date("Y-m-d 16:00:00", strtotime('-12 day'));//8天前，-8小时
        $endDate = ToolsAbstract::getDate()->date("Y-m-d 16:00:00");//当前时间

        $data = $this->getContractorOrderGmvStatistics($startDate, $endDate);

        if (count($data) === 0) {
            ToolsAbstract::log(sprintf('Metric:%s does not match any data.', self::METRIC_IDENTIFIER), 'CustomerGmvStat.log');
            return false;
        }

        $this->log($data);
        foreach ($data as $key => $value) {
            $city = $value['city'];
            $subtotal = $value['subtotal'];
            $day = $value['date'];
            $newKey = $city . '_' . $day;//计算每个城市的小计
            if (!isset($data[$newKey])) {
                $data[$newKey] = [
                    'city' => $city,
                    'subtotal' => $subtotal,
                    'contractor_id' => 0,
                    'date' => $day
                ];
            } else {
                $data[$newKey]['subtotal'] += $subtotal;
            }
            //未分配业务员的数据，只属于城市
            if (!isset($value['contractor_id']) || !$value['contractor_id']) {
                unset($data[$key]);
            }
        }

        foreach ($data as $value) {
            $city = $value['city'];
            $subtotal = $value['subtotal'];
            $contractorId = $value['contractor_id'];
            $day = $value['date'];
            $history = ContractorTaskHistory::findOne(['city' => $city, 'owner_id' => $contractorId, 'metric_id' => $metricId, 'date' => $day]);
            if (!$history) {
                $history = new ContractorTaskHistory();
                $history->value = $subtotal;
                $history->date = $day;
                $history->city = $city;
                $history->owner_id = $contractorId;
                $history->metric_id = $metricId;
                $history->updated_at = ToolsAbstract::getDate()->date();
            } else {
                $history->value = $subtotal;
            }
            $history->save();
            if (count($history->errors) > 0) {
                $this->log('We have encounter an unhandled error!');
                $this->log($history->toArray());
                $this->log($history->errors);
            }
        }
        ToolsAbstract::log('freshOrderGmv-finished', 'CustomerGmvStat.log');
    }


    protected function getContractorOrderGmvStatistics($startDate, $endDate)
    {
        $invalidOrderStr = implode("','", SalesFlatOrder::INVALID_ORDER_STATE());

        $query = SalesFlatOrder::find()
            ->addSelect(['contractor_id', 'city', new Expression("SUM(CASE WHEN state in('$invalidOrderStr') THEN 0 ELSE subtotal END) as subtotal"), new Expression("substring(addtime(created_at,'8:00:00'),1,10) as date")])
            ->where(['customer_tag_id' => SalesFlatOrder::CUSTOMER_TAG_ID_NORMAL])
            ->andWhere(['not in', 'wholesaler_id', SalesFlatOrder::excludeWholesalerIds()])
            ->andWhere(['not in', 'customer_id', SalesFlatOrder::excludeCustomerIds()])
            ->andWhere(['not like', 'wholesaler_name', ['t', 'T', '特通渠道', '乐来供应链', '测试']])
            ->andWhere(['>=', 'created_at', $startDate])
            ->andWhere(['<=', 'created_at', $endDate])
            ->groupBy(['contractor_id', 'date']);
//        ToolsAbstract::log($query->createCommand()->getRawSql(), 'CustomerGmvStat.log');
        $data = $query->asArray()->all();
        return $data;
    }

    protected function getLogFileName()
    {
        if (!$this->logFileName) {
            $this->logFileName = $this->getDefaultLogFileName();
        }
        return $this->logFileName;
    }
}