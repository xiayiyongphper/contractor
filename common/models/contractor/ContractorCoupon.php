<?php

namespace common\models\contractor;

use Yii;
use framework\db\ActiveRecord;

/*
 * 业务员拜访记录表
 * */
class ContractorCoupon extends ActiveRecord
{


    public static function tableName()
    {
        return 'contractor_coupon';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}