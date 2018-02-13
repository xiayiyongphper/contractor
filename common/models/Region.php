<?php

namespace common\models;


use Yii;
use framework\db\ActiveRecord;
/**
 * This is the model class for table "region".
 *
 * @property integer $entity_id
 * @property integer $code
 * @property string $chinese_name
 *
 */
class Region extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'region';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }

    public static function getCityInfoByCityIds($city_id_list){
        $query = self::find()->select([self::tableName().'.code as city_code',self::tableName().'.chinese_name as city_name','a.code as province_code','a.chinese_name as province_name'])
            ->where([self::tableName().'.code' => $city_id_list])->leftJoin(['a' => self::tableName()],"a.entity_id = ".self::tableName().".parent_id")
            ->asArray()->all();
        return $query;
    }
}
