<?php
namespace common\models;

use framework\db\ActiveRecord;

/**
 * User model
 * @property integer $entity_id
 * @property string $username
 * @property integer $province
 * @property integer $city
 * @property integer $phone
 * @property integer $district
 * @property integer $area_id
 * @property string $address
 * @property string $detail_address
 * @property string $store_name
 * @property string $storekeeper
 * @property string $store_area
 * @property string $lat
 * @property string $lng
 * @property string $img_lat
 * @property string $img_lng
 * @property string $password_reset_token
 * @property string $email
 * @property string $contractor
 * @property string $auth_token
 * @property float $orders_total_price
 * @property integer $status
 * @property integer $type
 * @property integer $level
 * @property integer $contractor_id
 * @property integer $created_at
 * @property integer $updated_at
 * @property integer $apply_at
 * @property string $business_license_img
 * @property string $password write-only password
 * @property float $balance
 * @property string business_license_no
 * @property string store_front_img
 * @property string storekeeper_instore_times
 *
 */
class LeCustomersIntention extends ActiveRecord
{

    /**
     * 是否意向超市
     * @var integer
     */
	public $intention;
	public $distance;

	public $last_visited_at;
    public $last_place_order_at;
	public $last_place_order_total;

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
	{
		return 'le_customers_intention';
	}

    /**
     * 通过userId得到超市模型
     * @param $customerId
     * @return null|static
     */
    public static function findByCustomerId($customerId)
    {
        return static::findOne(['entity_id' => $customerId]);
    }

    /**
     * 检查电话号码是否存在
     * @param $phone
     * @return null|static
     */
    public static function checkUserByPhone($phone)
    {

        return static::findOne(['phone' => $phone]);
    }

}
