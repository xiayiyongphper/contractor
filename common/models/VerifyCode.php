<?php
namespace common\models;

use Yii;
use framework\db\ActiveRecord;

/**
 * VerifyCode model
 *
 * @property integer $id
 * @property string $phone
 * @property string $code
 * @property integer $verify_type
 * @property integer $count
 * @property integer $created_at
 *
 */
class VerifyCode extends ActiveRecord
{

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'verifycode';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['phone', 'string'],
        ];
    }
}
