<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/11/7
 * Time: 10:33
 */

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * Class CustomerShelves
 * @package common\models
 */
class ShelvesProductCategoryMap extends ActiveRecord
{
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
    public static function tableName()
    {
        return 'shelves_product_category_map';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shelves_category_id','first_category_id'], 'required'],
        ];
    }
}