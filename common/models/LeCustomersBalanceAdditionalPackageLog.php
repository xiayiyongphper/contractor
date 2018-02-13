<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;
/**
 * This is the model class for table "le_customers_balance_additional_package_log".
 *
 * @property integer $entity_id
 * @property integer $customer_id
 * @property string $title
 * @property string $action
 * @property integer $type
 * @property string $amount
 * @property string $balance
 * @property integer $order_id
 * @property string $created_at
 * @property string $order_no
 * @property string $transaction_no
 */
class LeCustomersBalanceAdditionalPackageLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_customers_balance_additional_package_log';
    }

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
    public function rules()
    {
        return [
            [['customer_id', 'amount', 'balance'], 'required'],
            [['customer_id', 'type', 'order_id'], 'integer'],
            [['amount', 'balance'], 'number'],
            [['created_at'], 'safe'],
            [['title', 'order_no', 'transaction_no'], 'string', 'max' => 50],
            [['action'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'customer_id' => '用户id',
            'title' => '标题',
            'action' => '（未用）操作类型',
            'type' => '0为减少，1为增加',
            'amount' => '变更额度',
            'balance' => '余额',
            'order_id' => '相关订单',
            'created_at' => 'Created At',
            'order_no' => '订单号',
            'transaction_no' => '交易号',
        ];
    }
}
