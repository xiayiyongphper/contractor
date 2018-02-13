<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * 其他供货商
 * @property integer $entity_id
 * @property string $name
 * @property string $city
 */
class OtherMerchant extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'other_merchant';
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
            [['name', 'city'], 'required'],
            [['city'], 'number', 'min' => 1, 'max' => PHP_INT_MAX],
        ];
    }
}
