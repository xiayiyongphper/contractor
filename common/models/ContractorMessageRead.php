<?php
namespace common\models;

use framework\db\ActiveRecord;
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/3/28
 * Time: 16:51
 */

/**
 * 已读消息模型
 * @author zqy
 * @property integer $msg_id
 * @property integer $contractor_id
 * @property integer $read_at
 */
class ContractorMessageRead extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'contractor_message_read';
    }

    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }
}