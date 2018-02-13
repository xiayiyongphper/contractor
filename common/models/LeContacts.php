<?php

namespace common\models;

use framework\db\ActiveRecord;

/**
 * This is the model class for table "le_contacts".
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $user_name
 * @property string $phone
 * @property string $content
 * @property string $typevar
 * @property integer $type_id
 * @property string $create_at
 * @property integer $status
 */
class LeContacts extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_contacts';
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
            [['user_id', 'type_id', 'status'], 'integer'],
            [['phone'], 'required'],
            [['content'], 'string'],
            [['user_name', 'phone'], 'string', 'max' => 255],
            [['typevar'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'user_name' => 'User Name',
            'phone' => 'Phone',
            'content' => 'Content',
            'typevar' => 'Typevar',
            'type_id' => 'Type ID',
            'create_at' => 'Create At',
            'status' => 'Status',
        ];
    }
}
