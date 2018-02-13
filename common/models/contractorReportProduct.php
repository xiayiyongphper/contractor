<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "contractor_report_product".
 *
 * @property integer $entity_id
 * @property string $name
 * @property string $brand
 * @property string $barcode
 * @property string $wholesaler
 * @property string $remark
 * @property string $gallery
 * @property integer $contractor_id
 */
class contractorReportProduct extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'contractor_report_product';
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
            [['name', 'contractor_id'], 'required'],
            [['gallery'], 'string'],
            [['contractor_id'], 'integer'],
            [['name', 'wholesaler'], 'string', 'max' => 50],
            [['brand', 'barcode'], 'string', 'max' => 25],
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
            'name' => 'Name',
            'brand' => 'Brand',
            'barcode' => 'Barcode',
            'wholesaler' => 'Wholesaler',
            'remark' => 'Remark',
            'gallery' => 'Gallery',
            'contractor_id' => 'Contractor ID',
        ];
    }
}
