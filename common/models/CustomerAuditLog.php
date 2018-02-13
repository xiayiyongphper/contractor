<?php
namespace common\models;

use Yii;
use framework\db\ActiveRecord;


/**
 * User model
 *
 * @property integer $id
 * @property string $customer_id
 * @property integer $type
 * @property string $content
 * @property string $operator
 * @property integer $created_at
 */
class CustomerAuditLog extends ActiveRecord
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
        return 'customer_audit_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['customer_id', 'required', 'message' => 'Customer id is null'],
//            ['type', 'required', 'message' => 'type is null'],
//            ['content', 'required', 'message' => 'content is null'],
//            ['operator', 'required', 'message' => 'operator is null'],
        ];
    }

    public function loadJson($json)
    {
        $formName = 'import';
        if (is_string($json)) {
            $data = json_decode($json, true);
        } else{
            $data = $json;
        }
        $data = [$formName => $data];
        return parent::load($data, $formName);
    }
}
