<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;


/**
 * @author Jason
 * @property integer $entity_id
 * @property string $start_time
 * @property string $end_time
 * @property integer $customer_id
 * @property integer $visit_task_type
 */
class ContractorVisitTask extends ActiveRecord
{


    public static function tableName()
    {
        return 'contractor_visit_task';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}
