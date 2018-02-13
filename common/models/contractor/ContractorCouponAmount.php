<?php

namespace common\models\contractor;

use Yii;
use framework\db\ActiveRecord;

/*
 * 业务员拜访记录表
 * */
class ContractorCouponAmount extends ActiveRecord
{


    public static function tableName()
    {
        return 'contractor_coupon_amount';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}