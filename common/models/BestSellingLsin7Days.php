<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/11/30
 * Time: 14:17
 */

namespace common\models;

use framework\db\ActiveRecord;
use framework\components\ToolsAbstract;
use Yii;

/**
 * Class BestSellingLsin7Days
 * @package common\models\customer
 * @property string $lsin
 * @property integer $order_num
 * @property integer $created_at
 */
class BestSellingLsin7Days extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'best_selling_lsin_7_days';
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
    public function beforeSave($insert)
    {
        $curDateTime = ToolsAbstract::getDate()->date();
        if ($insert) {
            $this->created_at = $curDateTime;
        }
        $this->updated_at = $curDateTime;

        return parent::beforeSave($insert);
    }
}