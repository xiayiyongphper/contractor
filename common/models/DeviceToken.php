<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * This is the model class for table "device_token".
 *
 * @property integer $entity_id
 * @property integer $customer_id
 * @property string $token
 * @property integer $system
 * @property integer $channel
 * @property string $checksum
 * @property string $created_at
 * @property string $updated_at
 * @property integer $typequeue
 */
class DeviceToken extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'device_token';
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
            [['customer_id', 'token', 'checksum'], 'required'],
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
            'token' => 'Token',
            'system' => 'System',
            'channel' => 'Channel',
            'checksum' => 'Checksum',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'typequeue' => 'Typequeue',
        ];
    }

    /**
     * Function: findDeviceTokenByCustomerId
     * Author: Jason Y. Wang
     *
     * @param $customer_id
     * @return null|static|self
     */
    public static function findDeviceTokenByCustomerId($customer_id){
        return static::findOne(['customer_id' => $customer_id]);
    }
}
