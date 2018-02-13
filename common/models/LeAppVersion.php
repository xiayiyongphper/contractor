<?php

namespace common\models;

use framework\db\ActiveRecord;


/**
 * This is the model class for table "le_app_version".
 *
 * @property integer $entity_id
 * @property string $title
 * @property integer $system
 * @property integer $platform
 * @property integer $channel
 * @property string $code
 * @property string $version
 * @property string $lowest_version
 * @property string $advise_version
 * @property string $url
 * @property string $description
 * @property integer $type
 * @property string $create_at
 */
class LeAppVersion extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_app_version';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return \Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['system', 'platform', 'channel', 'type'], 'integer'],
            [['channel', 'code', 'version', 'lowest_version', 'advise_version', 'url', 'description'], 'required'],
            [['description'], 'string'],
            [['create_at'], 'safe'],
            [['title'], 'string', 'max' => 50],
            [['code', 'version'], 'string', 'max' => 32],
            [['lowest_version', 'advise_version'], 'string', 'max' => 8],
            [['url'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'title' => 'Title',
            'system' => 'System',
            'platform' => 'Platform',
            'channel' => 'Channel',
            'code' => 'Code',
            'version' => 'Version',
            'lowest_version' => 'Lowest Version',
            'advise_version' => 'Advise Version',
            'url' => 'Url',
            'description' => 'Description',
            'type' => 'Type',
            'create_at' => 'Create At',
        ];
    }
}
