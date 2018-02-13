<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * This is the model class for table "le_customers_balance_additional_package".
 *
 * @property integer $entity_id
 * @property integer $customer_id
 * @property string $balance
 */
class LeCustomersBalanceAdditionalPackage extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_customers_balance_additional_package';
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
            [['customer_id'], 'required'],
            [['customer_id'], 'integer'],
            [['balance'], 'number']
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
            'balance' => '补充包余额',
        ];
    }

	/**
	 * 根据用户id拿到余额模型
	 * @param null $customerId
	 *
	 * @return null|static
	 */
	public static function findByCustomerId($customerId=null)
	{
		return self::findOne(['customer_id'=>$customerId]);
	}

	/**
	 * 根据用户id查额度包余额
	 * @param null $customerId
	 *
	 * @return bool|int|string
	 */
	public static function getByCustomerId($customerId=null)
	{
		if(!$customerId){
			return false;
		}
		$res = self::findOne(['customer_id'=>$customerId]);
		if(!$res){
			return 0;
		}else{
			return $res->balance;
		}
	}
}
