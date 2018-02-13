<?php
namespace common\models\driver;

use Yii;
use framework\db\ActiveRecord;

/**
 * Order Status History
 * @property integer $entity_id
 * @property integer $driver_order_id
 * @property integer $order_id
 * @property integer $driver_id
 * @property string $comment
 * @property string $created_at
 * @property string $status
 * @property string $lat
 * @property string $lng
 */
class OrderStatusHistory extends ActiveRecord
{

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    public static function tableName()
    {
        return 'driver_order_status_history';
    }
}
