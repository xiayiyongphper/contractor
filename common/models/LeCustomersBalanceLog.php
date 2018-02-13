<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;
/**
 * This is the model class for table "le_customers_balance_log".
 *
 * @property integer $entity_id
 * @property integer $customer_id
 * @property string $title
 * @property string $action
 * @property string $amount
 * @property string $balance
 * @property integer $order_id
 * @property string $created_at
 * @property string $order_no
 * @property string $transaction_no
 */
class LeCustomersBalanceLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_customers_balance_log';
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
            [['customer_id', 'action', 'amount', 'balance'], 'required'],
            [['customer_id', 'order_id'], 'integer'],
            [['amount', 'balance'], 'number'],
            [['created_at'], 'safe'],
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
			'customer_id' => 'Customer ID',
			'title' => '标题',
            'action' => '操作类型',
            'amount' => '变更额度',
            'balance' => '余额',
            'order_id' => '相关订单',
			'created_at' => 'Created At',
			'order_no' => '订单号',
			'transaction_no' => '交易号',
        ];
    }

	/**
	 * 生成交易号
	 * @return string
	 */
    static public function getTransactionNo()
	{
		list($s1, $s2) = explode(' ', microtime());
		$millisecond = explode('.', $s1);
		$mill = substr($millisecond[1], 0, 5);
		return sprintf('%s%s', date('ymdHis', $s2), $mill);
	}
}
