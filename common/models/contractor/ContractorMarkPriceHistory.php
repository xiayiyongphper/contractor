<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 16-10-12
 * Time: 上午11:39
 */

namespace common\models\contractor;

use framework\db\ActiveRecord;
use framework\components\Date;

/**
 * This is the model class for table "ContractorMarkPriceHistory".
 *
 * @property integer $entity_id
 * @property integer $contractor_id
 * @property integer $city
 * @property string $contractor_name
 * @property integer $mark_price_product_id
 * @property float $price
 * @property string $created_at
 * @property string $source
 * @property string $source_type
 * @property string $customer_id
 * @property string $customer_name
 * @property string $gallery
 */

class ContractorMarkPriceHistory extends ActiveRecord
{

    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }

    public static function tableName()
    {
        return 'contractor_mark_price_history';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['contractor_id', 'contractor_name', 'city', 'mark_price_product_id'], 'required'],
            [['contractor_id', 'city', 'mark_price_product_id'], 'number', 'min' => 1, 'max' => PHP_INT_MAX],
            [['source_type'], 'number']
        ];
    }

    public static function getPriceById($mark_price_product_id){
        /** @var  ContractorMarkPriceHistory $priceHistory */
        $priceHistory = ContractorMarkPriceHistory::find()->where(['mark_price_product_id' => $mark_price_product_id])
            ->orderBy('created_at desc')->one();
        if($priceHistory){
            return $priceHistory->price;
        }
        return 0;
    }

    public static function getLatMarkPriceInfo($mark_price_product_id){
        /** @var  ContractorMarkPriceHistory $priceHistory */
        $priceHistory = ContractorMarkPriceHistory::find()->where(['mark_price_product_id' => $mark_price_product_id])
            ->orderBy('created_at desc')->one();
        return $priceHistory;
    }

    //当月某个价格上报商品的所有上报记录
    //如果传了$contractor_id，只查该业务员的
    public static function getMarkPriceHistoryByProductId($mark_price_product_id,$contractor_id = null){
        $date = new Date();
        $start_time = $date->date('Y-m-01 00:00:00');
        /** @var  ContractorMarkPriceHistory $priceHistory */
        $priceHistory = ContractorMarkPriceHistory::find()->where(['mark_price_product_id' => $mark_price_product_id])
            ->andWhere(['>=', 'created_at', $start_time]);
        if($contractor_id){
            $priceHistory->andWhere(['contractor_id' => $contractor_id]);
        }
        $priceHistory = $priceHistory->orderBy(['created_at' => SORT_DESC,'contractor_id' => SORT_ASC])->all();
        return $priceHistory;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(MarkPriceProduct::className(), ['entity_id' => 'mark_price_product_id']);
    }

}