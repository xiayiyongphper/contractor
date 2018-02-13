<?php

namespace common\models;

use common\components\push\CustomerEnterpriseJiGuang;
use common\components\push\CustomerJiGuang;
use common\components\push\MerchantJiGuang;
use service\components\Tools;
use service\models\common\CustomerException;
use yii\base\Exception;
use framework\db\ActiveRecord;

/**
 * This is the model class for table "le_app_push_queue".
 *
 * @property integer $entity_id
 * @property string $token
 * @property integer $group_id
 * @property integer $channel
 * @property integer $platform
 * @property integer $value_id
 * @property string $params
 * @property string $checksum
 * @property string $message
 * @property integer $status
 * @property integer $priority
 * @property string $created_at
 * @property string $scheduled_at
 * @property string $send_at
 * @property integer $typequeue
 */
class LeAppPushQueue extends ActiveRecord
{

    const PLATFORM_CUSTOMER = 1;
    const PLATFORM_MERCHANT = 2;
    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILURE = 2;
    const STATUS_PROCESSING = 3;
    const CUSTOMER_PLATFORM = 1;
    const CUSTOMER_PLATFORM_ENTERPRISE = 2;
    const MERCHANT_PLATFORM = 3;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_app_push_queue';
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
        return [[['token', 'channel', 'value_id'], 'required'], [['group_id', 'channel', 'platform', 'value_id', 'status', 'priority', 'typequeue'], 'integer'], [['params', 'message'], 'string'], [['created_at', 'scheduled_at', 'send_at'], 'safe'], [['token'], 'string', 'max' => 100], [['checksum'], 'string', 'max' => 32]];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return ['entity_id' => 'Entity ID', 'token' => 'Token', 'group_id' => 'Group ID', 'channel' => 'Channel', 'platform' => 'Platform', 'value_id' => 'Value ID', 'params' => 'Params', 'checksum' => 'Checksum', 'message' => 'Message', 'status' => 'Status', 'priority' => 'Priority', 'created_at' => 'Created At', 'scheduled_at' => 'Scheduled At', 'send_at' => 'Send At', 'typequeue' => 'Typequeue',];
    }

    /**
     * Function: send
     * Author: Jason Y. Wang
     * 推送消息
     * @return $this
     */
    public function send()
    {
        $mobilesys = 'android';
        $queue = new CustomerJiGuang();
        switch ($this->platform){
            case 1:
                if ($this->channel >= 100000 && $this->channel < 200000) {
                    $mobilesys = 'ios';
                    $queue = new CustomerJiGuang();
                } elseif ($this->channel >= 200000 && $this->channel < 300000) {
                    $mobilesys = 'ios';
                    $queue = new CustomerEnterpriseJiGuang();
                } else {
                    $mobilesys = 'android';
                    $queue = new CustomerJiGuang();
                }
                break;
            case 2:
                if ($this->channel >= 100000 && $this->channel < 300000) {
                    $mobilesys = 'ios';
                    $queue = new MerchantJiGuang();
                } else {
                    $mobilesys = 'android';
                    $queue = new MerchantJiGuang();
                }
                break;
            default:
                break;
        }

        try {

            $params = unserialize($this->params);
            if (!$params) {
                CustomerException::customerSystemError();
            }

            //推送参数
            $data = array('user_id' => $this->token, 'title' => $params['title'], 'content' => $params['content'], 'scheme' => $params['scheme'], 'mobilesys' => $mobilesys, 'sendno' => $this->entity_id,);
            //推送
            $result = $queue->push($data);
            Tools::log($result);
            if ($result) {
                $this->status = self::STATUS_SUCCESS;
            }
        } catch (Exception $e) {
            $this->status = self::STATUS_FAILURE;
            $this->message = $e->getMessage();
        }

        $this->send_at = date('Y-m-d H:i:s');
        return $this;
    }
}
