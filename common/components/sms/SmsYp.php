<?php

namespace common\components\sms;



use common\components\Sms;
use service\components\Tools;
use service\models\common\CustomerException;

/**
 * Author: Jason Y. Wang
 * Class Light_Sms_Yp
 */
class SmsYp
{
    protected $_apiKey = '8cc9f60c4ca536191ef92f3477b2d732';
    protected $_apiMarketKey = '806797796fbe3fc5b8f60dafb32f23a1';
    protected $_sendUrl = 'http://yunpian.com/v1/sms/send.json';
    protected $_tplSendUrl = 'http://yunpian.com/v1/sms/tpl_send.json';
    protected $_voiceSendUrl = 'https://voice.yunpian.com/v1/voice/send.json';

    protected $_mapping = array(
        Sms::CUSTOMER_SMS_TYPE_REGISTER => 796827,
        Sms::CUSTOMER_SMS_TYPE_FORGET => 796837,
        Sms::CUSTOMER_SMS_TYPE_LOGIN => 796827,
        Sms::CUSTOMER_SMS_TYPE_CHANGE_BINDING_PHONE => 796827,
        Sms::CUSTOMER_SMS_TYPE_RECEIPT => 911521,//911521 796823
        Sms::CUSTOMER_SMS_TYPE_REGISTER_SUCCESS => 988713,
        Sms::CUSTOMER_SMS_TYPE_STAFF_FORGET_PASSWORD => 796837,
        //11为业务员短信
        Sms::CONTRACTOR_SMS_TYPE_LOGIN => 796827,
        Sms::CONTRACTOR_SMS_TYPE_REGISTER => 796827,
        Sms::CONTRACTOR_SMS_TYPE_PLACE_ORDER => 2132338, //验证码#code#，业务员#name#正在为您代下单，总额#price#元，货到付款。您有任何疑问，可联系他#phone#
        Sms::CONTRACTOR_SMS_TYPE_NOTICE_AFTER_ORDERED => 2132342, //业务员#name#正在为您代下单，总额#price#元，货到付款。您有任何疑问，可联系他#phone#
        Sms::DRIVER_SMS_TYPE_LOGIN => 796827,
        Sms::DRIVER_SMS_TYPE_ORDER_CANCELED => 1585992,
    );

    /**
     * Function: sendRequest
     * Author: Jason Y. Wang
     *
     * @param $data
     * @param int $urlType
     * @return mixed
     */
    public function sendRequest($data, $urlType = 1) {
        $ch = curl_init();
        switch ($urlType) {
            case 1:
                curl_setopt($ch, CURLOPT_URL, $this->_tplSendUrl);
                break;
            case 2:
                curl_setopt($ch, CURLOPT_URL, $this->_sendUrl);
                break;
            case 3:
                curl_setopt($ch, CURLOPT_URL, $this->_voiceSendUrl);
                break;
            default:
                curl_setopt($ch, CURLOPT_URL, $this->_tplSendUrl);
                break;
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:application/json', 'Content-Type:application/x-www-form-urlencoded;charset=utf-8'));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        Tools::log('yun pian:'.$result,'sms.log');
        return $result;
    }

    /**
     * Function: send
     * Author: Jason Y. Wang
     *
     * @param $to
     * @param $smsType
     * @param $data
     * @param bool|false $voice
     * @return bool|mixed
     */
    public function send($to, $smsType, $data, $voice = false) {
        $result = false;
        if($voice){
            $data = array(
                'apikey' => $this->_apiKey,
                'mobile' => $to,
                'code' => $data['code'],
            );
            $result = $this->sendRequest($data,3);
        }else{
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $data[$key] = sprintf('#%s#=%s', $key, $value);
                }
            }
            $tplId = $this->getTemplateByType($smsType);
            if ($tplId) {
                $data = array(
                    'apikey' => $this->_apiKey,
                    'mobile' => $to,
                    'tpl_id' => $tplId,
                    'tpl_value' => implode('&', $data),
                );
                $result = $this->sendRequest($data);
            } else {
                CustomerException::customerSystemError();
            }
        }
        return $result;
    }

    /**
     * 营销短信推送
     * @param array $to 发送号码，不超过100条
     * @param array $message 发送短信内容 (对应云片模板，智能匹配发送)
    */
    public function marketSend($to, $message) {
        if (empty($to) || empty($message)) {
            return false;
        }
        $data = array(
            'apikey' => $this->_apiMarketKey,
            'mobile' => (is_array($to) ? implode(',', $to) : $to),
            'text' => $message
        );
        $result = $this->sendRequest($data, 2);
        return $result;
    }

    /**
     * @param $type
     * @return bool
     */
    protected function getTemplateByType($type)
    {
        if (isset($this->_mapping[$type])) {
            return $this->_mapping[$type];
        }
        return false;
    }
}
