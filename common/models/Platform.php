<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * 其他平台
 * @property integer $entity_id
 * @property string $name
 */
class Platform extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'platform';
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
            [['name'], 'required'],
        ];
    }
}
