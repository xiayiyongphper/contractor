<?php
namespace common\models\driver;

use framework\components\Security;
use Yii;
use framework\db\ActiveRecord;

/**
 * User model
 * @property integer $entity_id
 * @property string $auth_token
 * @property string $name
 * @property string $phone
 * @property integer $wholesaler_id
 * @property integer $gender
 * @property string $created_at
 * @property string $password
 * @property string $city
 * @property string $city_name
 * @property string $wholesaler_name
 */
class Driver extends ActiveRecord
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
        return 'driver';
    }
    
    public static function findByPhone($phone)
    {
        $driver = static::findOne(['phone' => $phone]);
        return $driver;
    }

    public static function findById($driver_id)
    {
        $driver = static::findOne(['entity_id' => $driver_id]);
        return $driver;
    }

    /**
     * @return Driver
     */
    public static function loginByPhonePassword($phone, $password)
    {
        //password已经过md5加密
        $driver = static::findOne(['phone' => $phone]);
        $newPassword = '';
        $flag = Security::passwordVerify($password,$driver->password,$newPassword);
        if($newPassword){
            $driver->password = $newPassword;
            $driver->save();
        }
        if($flag){
            return $driver;
        }else{
            return null;
        }

    }
    
}
