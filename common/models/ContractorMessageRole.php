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
 * 消息模型
 * @author zqy
 * @property integer msg_id
 * @property integer city_id
 * @property integer role_id
 * @property integer publish_at
 * @property integer status
 */
class ContractorMessageRole extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'contractor_message_role';
    }

    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }
}