<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/*
 * 业务员拜访记录表
 * */
class LePlanGroup extends ActiveRecord
{


    public static function tableName()
    {
        return 'plan_group';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}