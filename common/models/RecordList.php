<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "record_list".
 *
 * @property integer $entity_id
 * @property integer $contractor_id
 * @property integer $customer_id
 * @property string $store_name
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $remark
 * @property integer $order_code
 */
class RecordList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'record_list';
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
            [['contractor_id', 'customer_id', 'store_name', 'created_at', 'updated_at'], 'required'],
            [['contractor_id', 'customer_id', 'status'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['store_name'], 'string', 'max' => 50],
            [['remark'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'contractor_id' => 'Contractor ID',
            'customer_id' => 'Customer ID',
            'store_name' => 'Store Name',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'remark' => 'Remark',
        ];
    }
}
