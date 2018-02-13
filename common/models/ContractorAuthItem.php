<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 *
 * @property string $name
 * @property integer $type
 */
class ContractorAuthItem extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auth_item';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}
