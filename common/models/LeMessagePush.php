<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * Class AvailableCity
 * @package common\models
 * @property integer $entity_id
 * @property string $title
 * @property string $message
 * @property string $customer_id
 * @property string $status
 * @property string $created_at
 */
class LeMessagePush extends ActiveRecord
{

    const LE_MESSAGE_PUSH_STATUS_FAIL = '推送信息设置错误，推送失败';
    const LE_MESSAGE_PUSH_STATUS_PROCESSING = '正在推送';
    const LE_MESSAGE_PUSH_STATUS_COMPLETED = '推送完成';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_message_push';
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
            'title' => '名称',
            'message' => '消息'
        ];
    }

}
