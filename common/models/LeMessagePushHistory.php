<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * Class AvailableCity
 * @package common\models
 * @property integer $entity_id
 * @property integer $push_id
 * @property integer $customer_id
 * @property string $status
 * @property string $reason
 * @property string $created_at
 */
class LeMessagePushHistory extends ActiveRecord
{

    const LE_MESSAGE_PUSH_DEVICE_TOKEN_ERROR = '用户token不正确';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_message_push_history';
    }


    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    public function attributeLabels()
    {
        return [
            'entity_id' => 'ID',
            'customer_id' => '用户ID',
            'status' => '状态',
            'reason' => '备注',
        ];
    }

}
