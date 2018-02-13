<?php
namespace common\models;

use Yii;
use framework\db\ActiveRecord;
/**
 * Class LeCustomersAddressBook
 * @package common\models
 *
 * @property integer $customer_id
 * @property string $receiver_name
 * @property string $phone
 * @property integer $created_at
 * @property integer $updated_at
 *
 */

class LeCustomersAddressBook extends ActiveRecord
{
	    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_customers_address_book';
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
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['customer_id', 'integer'],
            [['receiver_name', 'phone'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customers_id' => 'Customers ID',
            'receiver_name' => 'Receiver',
            'phone' => 'Phone',
        ];
    }
    /**
     * 获取超市收货人信息
     * @param $customer_id
     * @return self
     */
	public static function findReceiverCustomerId($customer_id)
    {
        $receiver  = static::findOne(['customer_id' => $customer_id]);
        return $receiver;
    }
    
    /**
     * 更新用户地址列表缓存,暂时不会使用
     *
     */
    public function updateListByUserIdForRedis($userid)
    {
		$address_list = LeCustomersAddressBook::find()
    	->where(['customer_id' =>$userid])
    	->asArray()->all();
    	
    	Yii::$app->redisCache->set('le_user_address_'.$userid,$address_list);
    	
    	return Yii::$app->redisCache->get('le_user_address_'.$userid);
    }

}
