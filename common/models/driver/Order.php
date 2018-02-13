<?php
namespace common\models\driver;

use framework\components\ToolsAbstract;
use service\components\Tools;
use service\models\common\DriverException;
use service\resources\Exception;
use Yii;
use framework\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * User model
 * @property integer $entity_id
 * @property integer $driver_id
 * @property integer $order_id
 * @property string $increment_id
 * @property integer $wholesaler_id
 * @property string $wholesaler_name
 * @property integer $customer_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property string $lat
 * @property string $lng
 * @property string $state
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $completed_at
 * @property integer $delivery_time
 * @property string $driver_name
 * @property string $driver_phone
 */
class Order extends ActiveRecord
{

    /**
     * 新订单
     */
    const STATE_NEW = 'new';

    /**
     * 处理中
     */
    const STATE_PROCESSING = 'processing';

    /**
     * 完成
     */
    const STATE_COMPLETE = 'complete';


    /**
     * 新订单
     */
    const STATUS_PENDING = 'pending';

    /**
     * 处理中
     */
    const STATUS_PROCESSING = 'processing';

    /**
     * 完成
     */
    const STATUS_COMPLETE = 'complete';


    protected $_orderStateChanged = false;
    /**
     * @var Driver
     */
    protected $_driver = null;
    protected $_statusHistory = [];

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    public static function tableName()
    {
        return 'driver_order';
    }


    /**
     * Order state setter.
     * If status is specified, will add order status history with specified comment
     * the setData() cannot be overriden because of compatibility issues with resource model
     *
     * @param string $state
     * @param string|bool $status
     * @param string $comment
     * @param bool $isCustomerNotified
     * @return $this
     */
    public function setState($state, $status = false, $comment = '', $isCustomerNotified = null)
    {
        return $this->_setState($state, $status, $comment, $isCustomerNotified);
    }

    /**
     * Order state protected setter.
     * By default allows to set any state. Can also update status to default or specified value
     * Сomplete and closed states are encapsulated intentionally, see the _checkState()
     *
     * @param string $state
     * @param string|bool $status
     * @param string $comment
     * @param bool $isCustomerNotified
     * @param $shouldProtectState
     * @return $this
     */
    protected function _setState($state, $status = false, $comment = '',
                                 $isCustomerNotified = null)
    {
        $oldStatus = $this->status;
        $this->state = $state;

        // add status history
        if ($status) {
            if ($status === true) {
                throw new \Exception('Please set status instead of useing true.');
            }
            $this->status = $status;
            $history = $this->addStatusHistoryComment($comment, false); // no sense to set $status again
        }

        if ($oldStatus != $status) {
            $this->setOrderStateChanged(true);
        } else {
            $this->setOrderStateChanged(false);
        }
        return $this;
    }

    /**
     * @param $comment
     * @param bool $status
     * @param Driver $driver
     * @return OrderStatusHistory
     * @throws \Exception
     */
    public function addStatusHistoryComment($comment, $status = false)
    {
        if (false === $status) {
            $status = $this->status;
        } elseif (true === $status) {
            throw new \Exception('Please set status instead of useing true.');
        } else {
            $this->status = $status;
        }
        $date = ToolsAbstract::getDate();
        $history = new OrderStatusHistory();
        $history->status = $status;
        //$history->driver_id = $driver->entity_id;
        $history->comment = $comment;
        $history->created_at = $date->gmtTimestamp();
        $this->addStatusHistory($history);
        return $history;
    }

    /**
     * Set the order status history object and the order object to each other
     * Adds the object to the status history collection, which is automatically saved when the order is saved.
     * See the entity_id attribute backend model.
     * Or the history record can be saved standalone after this.
     *
     * @param OrderStatusHistory $history
     * @return $this
     */
    public function addStatusHistory(OrderStatusHistory $history)
    {
        $this->status = $history->status;
        if (!$history->getPrimaryKey()) {
            $this->_statusHistory[] = $history;
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOrderStateChanged()
    {
        return $this->_orderStateChanged;
    }

    /**
     * @param boolean $orderStateChanged
     */
    public function setOrderStateChanged($orderStateChanged)
    {
        $this->_orderStateChanged = $orderStateChanged;
    }

    /**
     * 订单保存之后的处理
     * @param bool $insert
     * @param array $changedAttributes
     * @return $this
     */
    public function afterSave($insert, $changedAttributes)
    {
        /**
         * 订单状态历史
         */
        if (null !== $this->_statusHistory && count($this->_statusHistory) > 0) {
            /** @var OrderStatusHistory $statusHistory */
            foreach ($this->_statusHistory as $statusHistory) {
                $statusHistory->driver_order_id = $this->getPrimaryKey();
                if ($this->_driver) {
                    $statusHistory->driver_id = $this->_driver->entity_id;
                } else {
                    $statusHistory->driver_id = 0;
                }
                $statusHistory->save();
            }
        }

        /*
         * 订单保存之后触发事件，并且要求订单状态发生实际改变时才触发事件。
         */
        if ($this->isOrderStateChanged()) {
            Tools::log('isOrderStateChanged');
        }

        parent::afterSave($insert, $changedAttributes);

    }

    /**
     * 订单删除之后的处理
     * @return $this
     */
    public function afterDelete()
    {
        if (null !== $this->_statusHistory && count($this->_statusHistory) > 0) {
            /** @var OrderStatusHistory $statusHistory */
            foreach ($this->_statusHistory as $statusHistory) {
                $statusHistory->driver_order_id = $this->getPrimaryKey();
                $statusHistory->save();
            }
        }
        parent::afterDelete();
    }

    public static function getByOrderId($order_id)
    {
        if ($order_id) {
            return static::findOne(['order_id' => $order_id]);
        } else {
            return false;
        }

    }

    public static function getProcessingOrderByOrderId($order_id)
    {
        if ($order_id) {
            return static::findOne(['order_id' => $order_id, 'state' => Order::STATE_PROCESSING]);
        } else {
            return false;
        }

    }


    public static function getByDriverOrderId($driver_order_id)
    {
        if ($driver_order_id) {
            return static::findOne(['entity_id' => $driver_order_id]);
        } else {
            return false;
        }
    }

    public function beforeSave($insert)
    {
        $date = Tools::getDate();
        $this->updated_at = $date->gmtDate();
        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }

    /**
     * @param string $comment
     * @return $this
     * @throws DriverException
     */
    public function deliverySuccess($comment = '')
    {
        if ($this->canDeliverySuccess()) {
            $this->setState(self::STATE_COMPLETE, self::STATUS_COMPLETE, $comment);
            $this->setCompletedAt();
        } else {
            DriverException::orderCanNotDeliverySuccess();
        }
        return $this;
    }

    /**
     * 是否可以配送成功
     * @return bool
     */
    public function canDeliverySuccess()
    {
        $status = $this->status;
        $statues = array(
            self::STATUS_PROCESSING,
        );
        if (in_array($status, $statues)) {
            return true;
        }
        return false;
    }


    /**
     * 拒绝订单
     * @param $comment
     * @return $this
     * @throws Exception
     */
    public function reset($comment = '')
    {
        if ($this->canReset()) {
            $this->setState(self::STATE_NEW, self::STATUS_PENDING, $comment);
        } else {
            DriverException::orderCanNotReset();
        }
        return $this;
    }

    /**
     * 是否可以配送成功
     * @return bool
     */
    public function canReset()
    {
        $status = $this->status;
        $statues = array(
            self::STATUS_PROCESSING,
        );
        if (in_array($status, $statues)) {
            return true;
        }
        return false;
    }

    public function setCompletedAt()
    {
        $date = ToolsAbstract::getDate();
        $time = $date->gmtDate();
        $this->completed_at = $time;
        $this->setDeliveryTime();
        return $this;
    }

    public function setDeliveryTime()
    {
        $statuses = [
            self::STATUS_COMPLETE
        ];
        if (!in_array($this->status, $statuses)) {
            return false;
        }

        $completedAt = $this->completed_at;
        $date = ToolsAbstract::getDate();
        if (!$completedAt) {
            $time = $date->gmtDate();
            $completedAt = $time;
        }
        $completedAt = strtotime($completedAt);
        $createdAt = strtotime($this->created_at);
        $this->delivery_time = ceil(($completedAt - $createdAt) / 60);
    }

    /**
     * @param Driver $driver
     * @param $list_type
     * @param $keyword
     * @return array
     */
    public static function getOrderIdsByDriver($driver, $list_type, $keyword)
    {
        $orders = static::find()->select('order_id')
            ->where(['driver_id' => $driver->entity_id])
            ->andWhere(['wholesaler_id' => $driver->wholesaler_id])
            ->andWhere(['>', 'order_id', 0])
            ->groupBy('order_id');

        if ($list_type == 1) {
            $orders->andWhere(['state' => Order::STATE_COMPLETE]);
            $orders->orderBy('completed_at desc');
            if ($keyword) {
                $orders->andWhere(['or', ['like', 'increment_id', $keyword], ['like', 'customer_name', $keyword]]);
            }

        } else {
            $orders->andWhere(['state' => Order::STATE_PROCESSING]);
            $orders->orderBy('created_at desc');
        }
        Tools::log($orders->createCommand()->getRawSql(), 'wangyang.log');
        $orders = $orders->asArray()->all();
        $orders = ArrayHelper::getColumn($orders, 'order_id');
        return $orders;
    }

    /**
     * @param $increment_id
     * @return null|static
     */
    public static function getOrderByIncrementId($increment_id)
    {
        $order = static::findOne(['increment_id' => $increment_id]);
        return $order;
    }

    /**
     * @return Driver
     */
    public function getDriver()
    {
        return $this->_driver;
    }

    /**
     * @param Driver $driver
     */
    public function setDriver($driver)
    {
        $this->_driver = $driver;
    }

    /**
     * @param $status
     * @return string
     */
    public function getStatusLabel($status = null)
    {
        if (!$status) {
            $status = $this->status;
        }
        switch ($status) {
            case self::STATUS_PENDING:
                $label = '待发货';
                break;
            case self::STATUS_PROCESSING:
                $label = '送货中';
                break;
            case self::STATUS_COMPLETE:
                $label = '已完成';
                break;
            default:
                $label = $status;
        }
        return $label;
    }

    /**
     * set location for status history
     * @param $lat
     * @param $lng
     * @return $this
     */
    public function setLocation($lat, $lng)
    {
        if ($lat && $lng && null !== $this->_statusHistory && count($this->_statusHistory) > 0) {
            /** @var OrderStatusHistory $statusHistory */
            foreach ($this->_statusHistory as $statusHistory) {
                $statusHistory->lat = $lat;
                $statusHistory->lng = $lng;
            }
        }
        return $this;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrderDriver()
    {
        return $this->hasOne(Driver::className(), ['driver_id' => 'entity_id']);
    }
}
