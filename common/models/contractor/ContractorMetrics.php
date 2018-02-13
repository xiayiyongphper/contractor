<?php
namespace common\models\contractor;

use common\models\LeCustomers;
use common\models\LeCustomersIntention;
use framework\components\ToolsAbstract;
use Yii;
use framework\db\ActiveRecord;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-7-22
 * Time: 上午11:24
 */

/**
 * Class VisitRecords
 * @package common\models\contractor
 * @property integer $entity_id
 * @property string $name
 * @property bool $type
 * @property string $identifier
 */
class ContractorMetrics extends ActiveRecord
{
    /**
     * 自动更新
     * @var int
     */
    const TYPE_AUTO = 1;
    /**
     * 手动更新
     * @var int
     */
    const TYPE_MANUAL = 2;
    /**
     * 显示历史明细
     * @var int
     */
    const TYPE_SHOW_HISTORY = 4;
    /**
     * 不显示在首页的今日昨日
     * @var int
     */
    const NOT_SHOW_DAYS_ITEM_FROM_HOME = 8;

    /**
     * 固定费率供应商签约数
     * @string
     */
    const ID_FIXED_RATE_MERCHANT_COUNT = 'fixed_rate_merchant_count';
    /**
     * 有效GMV
     * @string
     */
    const ID_VALID_GMV = 'valid_gmv';
    /**
     * 新增下单用户数
     * @string
     */
    const ID_FIRST_ORDER_CUSTOMER_COUNT = 'first_order_customer_count';
    /**
     * 月下单门店数
     * @string
     */
    const ID_MONTH_ORDER_CUSTOMER_COUNT = 'month_order_customer_count';
    /**
     * 订单数
     * @string
     */
    const ID_ORDER_COUNT = 'order_count';
    /**
     * DAU
     * @string
     */
    const ID_DAU = 'dau';
    /**
     * 固定费率供应商增量GMV
     * @string
     */
    const ID_FIXED_RATE_MERCHANT_GMV_INCREMENT = 'fixed_rate_merchant_gmv_increment';
    /**
     * 开拓合格供应商个数
     * @string
     */
    const ID_NEW_VALID_MERCHANT_COUNT = 'new_valid_merchant_count';
    /**
     * 月店均GMV
     * @string
     */
    const ID_STORE_AVG_GMV = 'store_avg_gmv';
    /**
     * 营收指标
     * @string
     */
    const ID_REVENUE = 'revenue';

    //首单用户数标记
    const METRIC_IDENTIFIER_FIRST_ORDER_CUSTOMER_COUNT = self::ID_FIRST_ORDER_CUSTOMER_COUNT;
    //当月下单用户数标记
    const METRIC_IDENTIFIER_MONTH_ORDER_CUSTOMER_COUNT = self::ID_MONTH_ORDER_CUSTOMER_COUNT;
    //gmv
    const METRIC_IDENTIFIER_MONTH_ORDER_GMV = self::ID_VALID_GMV;

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
        return 'contractor_metrics';
    }

    public static function getMetricsMap()
    {
        $metric_list = static::find()->all();
        $metrics = [];
        foreach ($metric_list as $item) {
            $metrics[$item['entity_id']] = $item['name'];
        }
        return $metrics;
    }

    public static function getAllMetricsIds()
    {
        $metric_list = static::find()->select(['entity_id'])->all();
        $metrics_ids = [];
        foreach ($metric_list as $item) {
            $metrics_ids[] = $item['entity_id'];
        }
        return $metrics_ids;
    }

    /**
     * 是否展示历史明细
     * @param int $type
     * @return bool
     */
    public static function isShowHistoryType($type)
    {
        return $type & self::TYPE_SHOW_HISTORY ? true : false;
    }

    /**
     * 是否展示历史明细
     * @param int $type
     * @return bool
     */
    public static function isManualType($type)
    {
        return $type & self::TYPE_MANUAL ? true : false;
    }

    public static function getMetricIdByIdentifier($identifier)
    {
        $metric = static::find()->select(['entity_id'])->where(['identifier' => $identifier])->one();
        if (empty($metric)) {
            return false;
        }

        return $metric['entity_id'];
    }
}