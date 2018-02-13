<?php
/**
 * Created by PhpStorm.
 * User: jason
 * Date: 16-10-12
 * Time: 上午11:39
 */

namespace common\models\contractor;


use framework\db\ActiveRecord;

/**
 * This is the model class for table "MarkPriceProduct".
 *
 * @property integer $entity_id
 * @property string $name
 * @property string $image
 * @property string $barcode
 * @property integer $first_category_id
 * @property integer $city
 * @property integer $status
 * @property string $created_at
 * @property string $updated_at
 */

class MarkPriceProduct extends ActiveRecord
{
    const MARK_PRICE_PRODUCT_STATUS_SHOW = 1;
    const MARK_PRICE_PRODUCT_STATUS_NOT_SHOW = 0;
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }

    public static function tableName()
    {
        return 'contractor_mark_price_product_list';
    }

}