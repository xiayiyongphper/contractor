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
use framework\components\ToolsAbstract;
use framework\db\readonly\models\core\SalesFlatOrder;
use framework\tasks\TaskAbstract;
use yii\db\Expression;

/**
 * 订单数量统计脚本
 * Class orderCountStatistics
 * @package service\tasks
 * @author henryzhu <zhuxiaojiang@lelai.com>
 * @timestamp 2017-07-27 11:13
 */
class orderCountStatistics extends TaskAbstract
{
    const METRIC_IDENTIFIER = 'order_count';

    public function run($data)
    {
        $this->log('orderCountStatisticsTask-start');
        $metricId = ContractorMetrics::getMetricIdByIdentifier(self::METRIC_IDENTIFIER);
        if ($metricId === false) {
            $this->log(sprintf('Metric:%s not found', self::METRIC_IDENTIFIER));
            return false;
        }

        $startDate = ToolsAbstract::getDate()->date("Y-m-d 16:00:00", strtotime('-9 day'));//8天前，-8小时
        $endDate = ToolsAbstract::getDate()->date("Y-m-d 16:00:00");//当前时间


        $invalidOrderStr = implode("','", SalesFlatOrder::INVALID_ORDER_STATE());
        $query = SalesFlatOrder::find()
            ->addSelect(['contractor_id', 'city', new Expression('COUNT(*) as count_total'), new Expression("SUM(CASE WHEN state in('$invalidOrderStr') THEN 0 ELSE 1 END) as count"), new Expression("substring(addtime(created_at,'8:00:00'),1,10) as date")])
            ->where(['customer_tag_id' => SalesFlatOrder::CUSTOMER_TAG_ID_NORMAL])
//            ->andWhere(['not in', 'state', SalesFlatOrder::INVALID_ORDER_STATE()])
            ->andWhere(['not in', 'wholesaler_id', SalesFlatOrder::excludeWholesalerIds()])
            ->andWhere(['not in', 'customer_id', SalesFlatOrder::excludeCustomerIds()])
            ->andWhere(['not like', 'wholesaler_name', ['t', 'T', '特通渠道', '乐来供应链', '测试']])
            ->andWhere(['>=', 'created_at', $startDate])
            ->andWhere(['<=', 'created_at', $endDate])
            ->groupBy(['contractor_id', 'date']);

        $data = $query->asArray()->all();
        $this->log($query->createCommand()->getRawSql());
        if (count($data) === 0) {
            $this->log($query->createCommand()->getRawSql());
            $this->log(sprintf('Metric:%s does not match any data.', self::METRIC_IDENTIFIER));
            return false;
        }

        foreach ($data as $key => $value) {
            $city = $value['city'];
            $count = $value['count'];
            $day = $value['date'];
            $newKey = $city . '_' . $day;//计算每个城市的小计
            if (!isset($data[$newKey])) {
                $data[$newKey] = [
                    'city' => $city,
                    'count' => $count,
                    'contractor_id' => 0,
                    'date' => $day
                ];
            } else {
                $data[$newKey]['count'] += $count;
            }
            //未分配业务员的数据，只属于城市
            if (!isset($value['contractor_id']) || !$value['contractor_id']) {
                unset($data[$key]);
            }
        }


        foreach ($data as $value) {
            $city = $value['city'];
            $count = $value['count'];
            $contractorId = $value['contractor_id'];
            $day = $value['date'];
            $history = ContractorTaskHistory::findOne(['city' => $city, 'owner_id' => $contractorId, 'metric_id' => $metricId, 'date' => $day]);
            if (!$history) {
                $history = new ContractorTaskHistory();
                $history->value = $count;
                $history->date = $day;
                $history->city = $city;
                $history->owner_id = $contractorId;
                $history->metric_id = $metricId;
                $history->updated_at = ToolsAbstract::getDate()->date();
            } else {
                $history->value = $count;
            }
            $history->save();
            if (count($history->errors) > 0) {
                $this->log('We have encounter an unhandled error!');
                $this->log($history->toArray());
                $this->log($history->errors);
            }
        }

        $this->log('orderCountStatisticsTask-finished');
    }

    protected function getLogFileName()
    {
        if (!$this->logFileName) {
            $this->logFileName = $this->getDefaultLogFileName();
        }
        return $this->logFileName;
    }
}