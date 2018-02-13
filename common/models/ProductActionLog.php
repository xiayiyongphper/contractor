<?php
namespace common\models;

use Yii;
use framework\db\ActiveRecord;


/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property integer $province
 * @property integer $city_id
 * @property integer $district
 * @property integer $area_id
 * @property string $address
 * @property string $store_name
 * @property string $lat
 * @property string $lng
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class ProductActionLog extends ActiveRecord
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
        return 'product_action_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['wholesaler_id', 'required', 'message' => 'Wholesaler id is null'],
            ['product_id', 'required', 'message' => 'Product id is null'],
            ['operator', 'required', 'message' => 'operator is null'],
            ['content', 'required', 'message' => 'content is null'],
            ['operate_at', 'required', 'message' => 'operate at is null'],
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
