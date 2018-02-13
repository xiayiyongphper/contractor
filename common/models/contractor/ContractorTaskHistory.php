<?php

namespace common\models\contractor;

/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/7/18
 * Time: 17:10
 */


use framework\components\ToolsAbstract;
use service\components\Tools;
use Yii;
use framework\db\ActiveRecord;

/**
 * Class ContractorTaskHistory
 * @package common\models\contractor
 * @property integer $entity_id
 * @property string|float $value
 * @property string $date
 * @property int city
 * @property int owner_id
 * @property int metric_id
 * @property string $updated_at
 */
class ContractorTaskHistory extends ActiveRecord
{

    public $sales_total;
    public $orders_count;

    /**
     * 环比昨日
     * @var string
     */
    public $today_on_yesterday;
    /**
     * 同比上周
     * @var string
     */
    public $today_on_lastweek;

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'contractor_task_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['value'], 'number', 'min' => 0, 'max' => 99999999.99],
            [['city', 'metric_id'], 'number', 'min' => 1, 'max' => PHP_INT_MAX],
            [['owner_id'], 'number', 'min' => 0, 'max' => PHP_INT_MAX],
            [['owner_id', 'metric_id', 'city', 'value'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->updated_at = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
        return parent::beforeSave($insert);
    }

    /**
     * 获取本月均值
     *
     * @param int $city
     * @param int $ownnerId
     * @param int $metricId
     * @param int $month
     * @return mixed
     */
    public static function getAvgValueByCityOwnerIdMetricId($city, $ownnerId, $metricId, $month = null)
    {
        if ($month === null) {
            $startDateTime = ToolsAbstract::getDate()->date('Y-m-01 00:00:00');
            $endDateTime = ToolsAbstract::getDate()->date('Y-m-d 00:00:00');
        } else {
            $month = str_replace('-', '', $month);
            $month = preg_replace('/^(\d{4})(\d{2})$/', '$1-$2', $month);
            $startDateTime = ToolsAbstract::getDate()->date($month . '-01 00:00:00');
            $endDateTime = ToolsAbstract::getDate()->date($month . '-31 00:00:00');
        }

        $avg = static::find()->where([
            'owner_id' => $ownnerId,
            'metric_id' => $metricId,
            'city' => $city
        ])->andWhere(['>=', 'date', $startDateTime])
            ->andWhere(['<', 'date', $endDateTime]);

        $avg = $avg->average('value');

        return $avg ? number_format($avg, 2, '.', '') : 0;
    }

    /**
     * 获取本月总值
     *
     * @param int $city
     * @param int $ownnerId
     * @param int $metricId
     * @param int $month
     * @return mixed
     */
    public static function getTotalValueByCityOwnerIdMetricId($city, $ownnerId, $metricId, $month = null)
    {
        if ($month === null) {
            $startDateTime = ToolsAbstract::getDate()->date('Y-m-01 00:00:00');
            $endDateTime = ToolsAbstract::getDate()->date('Y-m-d 23:59:59');
        } else {
            $month = str_replace('-', '', $month);
            $month = preg_replace('/^(\d{4})(\d{2})$/', '$1-$2', $month);
            $startDateTime = ToolsAbstract::getDate()->date($month . '-01 00:00:00');
            $endDateTime = ToolsAbstract::getDate()->date($month . '-31 23:59:59');
        }

        $total = static::find()->where([
            'owner_id' => $ownnerId,
            'metric_id' => $metricId,
            'city' => $city
        ])->andWhere(['>=', 'date', $startDateTime])
            ->andWhere(['<=', 'date', $endDateTime])->sum('value');
        return $total ? $total : 0;
    }

    /**
     * 获取最近N天的数据
     *
     * @param int $city
     * @param int $ownnerId
     * @param int $metricId
     * @param int $lastDays 获取最近多少天
     * @return array|ContractorTaskHistory[]
     */
    public static function getHistoryByCityOwnerIdMetricIdLastDays($city, $ownnerId, $metricId, $lastDays = 7)
    {
        $endDateTime = ToolsAbstract::getDate()->date('Y-m-d 23:59:59');
        $startDateTime = date('Y-m-d H:i:s', strtotime("-{$lastDays} days", strtotime($endDateTime)));
        return static::find()->where([
            'owner_id' => $ownnerId,
            'metric_id' => $metricId,
            'city' => $city
        ])->andWhere(['>=', 'date', $startDateTime])
            ->andWhere(['<=', 'date', $endDateTime])
            ->orderBy('date ASC')->all();
    }

    /**
     * 获取本月手动更新指标值
     *
     * @param int $city
     * @param int $ownnerId
     * @param int $metricId
     * @param string $month 格式yyyyMM
     * @param int $limit
     * @return ContractorTaskHistory[]
     */
    public static function getMonthManualHistory($city, $ownnerId, $metricId, $month = null, $limit = 3)
    {
        $curMonth = ToolsAbstract::getDate()->date('Ym');
        if ($month && $curMonth != $month) {
            $nextMonth = (string)((int)$month + 1);
            $nextMonth = substr($nextMonth, 0, 4) . '-' . substr($nextMonth, -2, 2);
            $month = substr((string)$month, 0, 4) . '-' . substr((string)$month, -2, 2);
            $endDateTime = ToolsAbstract::getDate()->date($nextMonth . '-01 00:00:00');
            $startDateTime = ToolsAbstract::getDate()->date($month . '-01 00:00:00');
            $endDateCond = ['<', 'date', $endDateTime];
        } else {
            $endDateTime = ToolsAbstract::getDate()->date('Y-m-d 23:59:59');
            $startDateTime = ToolsAbstract::getDate()->date('Y-m-01 00:00:00');
            $endDateCond = ['<=', 'date', $endDateTime];
        }

        return static::find()->where([
            'owner_id' => $ownnerId,
            'metric_id' => $metricId,
            'city' => $city
        ])->andWhere(['>=', 'date', $startDateTime])
            ->andWhere($endDateCond)
            ->orderBy('date DESC')->limit($limit)->all();
    }

    /**
     * 获取手动更新指标值
     *
     * @param int $city
     * @param int $ownnerId
     * @param int $metricId
     * @param string $month 格式yyyyMM
     * @return ContractorTaskHistory
     */
    public static function getMonthLastManualHistory($city, $ownnerId, $metricId, $month = null)
    {
        $result = static::getMonthManualHistory($city, $ownnerId, $metricId, $month, 1);
        if ($result) {
            return $result[0];
        }
        return null;
    }

    /**
     * @param int $entityId
     * @param string $date 格式yyyy-MM
     * @param int $value
     * @return bool
     */
    public static function saveManualValue($entityId, $city, $ownnerId, $metricId, $date, $value)
    {
        $curDate = ToolsAbstract::getDate()->date('Y-m-d');
        $selfObj = null;
        $saveDate = $curDate;
        if ($entityId) {
            $selfObj = static::findOne(['entity_id' => $entityId]);
            if (!$selfObj) {
                return false;
            }
            /*
             *  1.当天的：如果值相同直接返回true，不是就保存新值；
             *  2.大于当天：而且不是当月，则新的日期为该月的第一天
             *  3.小于当天：取当月最后一天
             */
            if ($curDate == $selfObj->date) {
                if ($value == $selfObj->value) {
                    return true;
                } else {
                    $selfObj->value = $value;
                    return $selfObj->save();
                }
            } elseif ($date > $curDate) {
                if (strpos($curDate, $date) === false) {    // 非当月
                    $saveDate = $date . '-01';
                }
            } elseif (strpos($curDate, $date) === false) {  // 不是当月的
                $timestamp = ToolsAbstract::getDate()->timestamp('last day of ' . $date);
                $saveDate = date('Y-m-d', $timestamp);
            }
        } else {    // 当月没有设置过值
            if (strpos($curDate, $date) === false) {    // 非当月
                $saveDate = $date . '-01';
            }
        }

        $selfObj = static::findOne([
            'metric_id' => $metricId,
            'owner_id' => $ownnerId,
            'city' => $city,
            'date' => $saveDate
        ]);

        if ($selfObj) {
            $selfObj->value = $value;
        } else {
            $selfObj = new static();
            $selfObj->city = $city;
            $selfObj->metric_id = $metricId;
            $selfObj->owner_id = $ownnerId;
            $selfObj->value = $value;
            $selfObj->date = $saveDate;
        }
        return $selfObj->save();
    }

    /**
     * @param int $city
     * @param int $ownnerId
     * @param int $month
     * @return int|string
     */
    public static function getStoreAvgGMV($city, $ownnerId, $month = null)
    {
        $validGMVMetricId = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::ID_VALID_GMV);
        $orderMetricId = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::ID_MONTH_ORDER_CUSTOMER_COUNT);
        $value1 = ContractorTaskHistory::getTotalValueByCityOwnerIdMetricId($city, $ownnerId, $validGMVMetricId, $month);
        $value2 = ContractorTaskHistory::getTotalValueByCityOwnerIdMetricId($city, $ownnerId, $orderMetricId, $month);
        if ($value2 < 0.01) {
            $value = 0;
        } else {
            $value = number_format($value1 / $value2, 2, '.', '');
        }
        return $value;
    }
}
