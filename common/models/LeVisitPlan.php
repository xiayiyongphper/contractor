<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/*
 * 业务员拜访记录表
 * */
class LeVisitPlan extends ActiveRecord
{


    public static function tableName()
    {
        return 'visit_plan';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}