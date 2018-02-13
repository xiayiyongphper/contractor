<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "record_products".
 *
 * @property integer $entity_id
 * @property integer $record_wholesaler_id
 * @property integer $product_id
 * @property string $product_name
 * @property integer $num
 * @property string $price
 * @property string $total_price
 * @property string $created_at
 * @property integer $in_cart
 * @property integer $customer_id
 */
class RecordProducts extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'record_products';
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
            [['record_wholesaler_id', 'product_id', 'product_name', 'num', 'price', 'total_price', 'created_at'], 'required'],
            [['record_wholesaler_id', 'product_id', 'num', 'in_cart', 'customer_id'], 'integer'],
            [['price', 'total_price'], 'number'],
            [['created_at'], 'safe'],
            [['product_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'record_wholesaler_id' => 'Record Wholesaler ID',
            'product_id' => 'Product ID',
            'product_name' => 'Product Name',
            'num' => 'Num',
            'price' => 'Price',
            'total_price' => 'Total Price',
            'created_at' => 'Created At',
            'in_cart' => 'In Cart',
            'customer_id' => 'Customer ID',
        ];
    }
}
