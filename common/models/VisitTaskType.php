<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;


/**
 * @author Jason
 * @property integer $entity_id
 * @property string $desc
 */
class VisitTaskType extends ActiveRecord
{


    public static function tableName()
    {
        return 'visit_task_type';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}
