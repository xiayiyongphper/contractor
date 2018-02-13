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
 * @property integer entity_id
 * @property integer type
 * @property string title
 * @property string content
 * @property string created_at
 * @property string updated_at
 * @property integer status
 */
class ContractorMessage extends ActiveRecord
{
    /**
     * 消息类型总数，查询统计时要
     * @var int
     */
    public $count = 0;
    /**
     * 状态：可用
     */
    const STATUS_ENABLE = 1;
    /**
     * 状态：不可用
     */
    const STATUS_DISABLE = 0;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'contractor_message';
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $curTime = date('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $curTime;
        }
        $this->updated_at = $curTime;
    }

    /**
     * @inheritdoc
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }
}