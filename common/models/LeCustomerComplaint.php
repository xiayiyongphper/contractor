<?php

namespace common\models;

use framework\db\ActiveRecord;
use Yii;
/**
 * Created by PhpStorm.
 * User: Jason Y. Wang
 * Date: 2015/12/23
 * Time: 18:32
 *
 * @property integer $wholesaler_id
 * @property integer $customer_id
 * @property string $increment_id
 * @property string $contact_name
 * @property string $contact_phone
 * @property integer $type
 * @property string $content
 * @property integer $created_at
 *
 */
class LeCustomerComplaint extends ActiveRecord
{

    public static function tableName(){
        return 'le_customer_complaint';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wholesaler_id', 'increment_id','type','content'], 'required'],
        ];
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

}