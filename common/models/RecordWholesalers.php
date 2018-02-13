<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "record_wholesalers".
 *
 * @property integer $entity_id
 * @property integer $record_id
 * @property integer $wholesaler_id
 * @property string $wholesaler_name
 * @property integer $status
 * @property integer $order_id
 * @property string $updated_at
 */
class RecordWholesalers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'record_wholesalers';
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
            [['record_id', 'wholesaler_id', 'wholesaler_name', 'updated_at'], 'required'],
            [['record_id', 'wholesaler_id', 'status'], 'integer'],
            [['updated_at'], 'safe'],
            [['wholesaler_name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'record_id' => 'Record ID',
            'wholesaler_id' => 'Wholesaler ID',
            'wholesaler_name' => 'Wholesaler Name',
            'status' => 'Status',
            'updated_at' => 'Updated At',
        ];
    }
}
