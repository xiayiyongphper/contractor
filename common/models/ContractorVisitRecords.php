<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/*
 * 业务员拜访记录表
 * */
class ContractorVisitRecords extends ActiveRecord
{


    public static function tableName()
    {
        return 'contractor_visit_records';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}
