<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;
/**
 * This is the model class for table "region_area".
 *
 * @property integer $entity_id
 * @property integer $source
 * @property string $city_id
 * @property integer $district_id
 * @property integer $circle_id
 * @property string $area_name
 * @property string $area_address
 * @property string $lng
 * @property string $lat
 * @property string $whole_spell
 * @property string $each_first_letter
 */
class RegionArea extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'region_area';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['source', 'city', 'district', 'circle_id'], 'integer'],
            [['area_name'], 'string', 'max' => 120],
            [['area_address'], 'string', 'max' => 255],
            [['lng', 'lat'], 'string', 'max' => 32],
            [['whole_spell', 'each_first_letter'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'source' => 'Source',
            'city' => 'City Code',
            'district' => 'District',
            'circle_id' => 'Circle ID',
            'area_name' => 'Area Name',
            'area_address' => 'Area Address',
            'lng' => 'Lng',
            'lat' => 'Lat',
            'whole_spell' => 'Whole Spell',
            'each_first_letter' => 'Each First Letter',
        ];
    }
}
