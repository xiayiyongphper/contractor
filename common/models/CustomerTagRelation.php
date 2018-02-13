<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * User model
 *
 * @property integer $entity_id
 * @property integer $tag_id
 * @property integer $customer_id
 */
class CustomerTagRelation extends ActiveRecord
{

    public static function tableName()
    {
        return 'customer_tag_relation';
    }

    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }
}
