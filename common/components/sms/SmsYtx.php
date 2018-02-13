<?php

namespace common\components\sms;



use common\components\Sms;
use service\components\Tools;


/**
 * Author: Jason Y. Wang
 * Class SmsYtx
 */
class SmsYtx
{

    protected $AccountSid = 'aaf98f894a188342014a19d264e60182';
    protected $AccountToken = 'cc83669ce27a40449ed4462625070481';
    protected $AppId = '8a48b5514a18861b014a1e45f0ee032f';
    protected $ServerIP = 'app.cloopen.com';
    protected $ServerPort = '8883';
    protected $SoftVersion = '2013-12-26';
    protected $Batch;  //时间戳
    protected $BodyType = "json";//包体格式，可填值：json 、xml
    protected $Handle;

    protected $_mapping = array(
        Sms::CUSTOMER_SMS_TYPE_REGISTER => 155084,
        Sms::CUSTOMER_SMS_TYPE_LOGIN => 155084,
        Sms::CUSTOMER_SMS_TYPE_FORGET => 155083,
        //11为业务员短信
        Sms::CONTRACTOR_SMS_TYPE_LOGIN => 155084,
        Sms::CONTRACTOR_SMS_TYPE_REGISTER => 155084,
        Sms::CONTRACTOR_SMS_TYPE_PLACE_ORDER => 227326, //验证码{1}，业务员{2}正在为您代下单，总额{3}元。您有任何疑问，可联系他{4}
        Sms::CONTRACTOR_SMS_TYPE_NOTICE_AFTER_ORDERED => 227328, //业务员{1}正在为您代下单，总额{2}元。您有任何疑问，可联系他{3}
        Sms::DRIVER_SMS_TYPE_LOGIN => 155084,
        Sms::DRIVER_SMS_TYPE_ORDER_CANCELED => 154571,
    );

    function __construct()
    {
        $this->Batch = date("YmdHis");
    }

    /**
     * 发起HTTPS请求
     * @param $url
     * @param $data
     * @param $header
     * @param int $post
     * @return mixed|string
     */
    function curl_post($url, $data, $header, $post = 1)
    {
        //初始化curl
        $ch = curl_init();
        //参数设置
        $res = curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, $post);
        if ($post)
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        $result = curl_exec($ch);
        //连接失败
        if ($result == FALSE) {
            if ($this->BodyType == 'json') {
                $result = "{\"statusCode\":\"172001\",\"statusMsg\":\"网络错误\"}";
            } else {
                $result = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><Response><statusCode>172001</statusCode><statusMsg>网络错误</statusMsg></Response>";
            }
        }
        curl_close($ch);
        return $result;
    }

    /**
     * @param $to
     * @param $smsType
     * @param $data
     * @param bool $voice
     * @return mixed|SimpleXMLElement|stdClass
     */
    public function send($to, $smsType, $data, $voice = false)
    {
        if (!$voice) {
            $tplId = $this->getTemplateByType($smsType);
            $result = $this->sendTemplateSMS($to, $data, $tplId);
        } else {
            $result = $this->sendVoiceVerifyTemplate($to, current($data));
        }
        return $result;
    }

    /**
     * 发送模板短信
     * @param string $to 短信接收彿手机号码集合,用英文逗号分开
     * @param mixed $datas 内容数据
     * @param int $tempId 模板Id
     * @return mixed|SimpleXMLElement|stdClass
     */
    function sendTemplateSMS($to, $datas, $tempId)
    {
        //主帐号鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        // 拼接请求包体
        if ($this->BodyType == "json") {
            $data = "";
            for ($i = 0; $i < count($datas); $i++) {
                $data = $data . "'" . $datas[$i] . "',";
            }
            $body = "{'to':'$to','templateId':'$tempId','appId':'$this->AppId','datas':[" . $data . "]}";
        } else {
            $data = "";
            for ($i = 0; $i < count($datas); $i++) {
                $data = $data . "<data>" . $datas[$i] . "</data>";
            }
            $body = "<TemplateSMS>
                    <to>$to</to>
                    <appId>$this->AppId</appId>
                    <templateId>$tempId</templateId>
                    <datas>" . $data . "</datas>
                  </TemplateSMS>";
        }
        // 大写的sig参数
        $sig = strtoupper(md5($this->AccountSid . $this->AccountToken . $this->Batch));
        // 生成请求URL
        $url = "https://$this->ServerIP:$this->ServerPort/$this->SoftVersion/Accounts/$this->AccountSid/SMS/TemplateSMS?sig=$sig";
        // 生成授权：主帐户Id + 英文冒号 + 时间戳。
        $authen = base64_encode($this->AccountSid . ":" . date('YmdHis'));
        // 生成包头
        $header = array("Accept:application/$this->BodyType", "Content-Type:application/$this->BodyType;charset=utf-8", "Authorization:$authen");
        // 发送请求
        $result = $this->curl_post($url, $body, $header);
        Tools::log('yun tong xun:'.$result,'sms.log');
        if ($this->BodyType == "json") {//JSON格式
            $datas = json_decode($result);
        } else { //xml格式
            $datas = simplexml_load_string(trim($result, " \t\n\r"));
        }
        //重新装填数据
        if ($datas->statusCode == 0) {
            if ($this->BodyType == "json") {
                $datas->TemplateSMS = $datas->templateSMS;
                unset($datas->templateSMS);
            }
        }
        return $datas;
    }

    /**
     * @param $phone
     * @param $verifyCode
     * @return mixed|SimpleXMLElement|stdClass
     */
    public function sendVoiceVerifyTemplate($phone, $verifyCode)
    {
        //主帐号鉴权信息验证，对必选参数进行判空。
        $auth = $this->accAuth();
        if ($auth != "") {
            return $auth;
        }
        // 拼接请求包体
        if ($this->BodyType == "json") {
            $body = "{'to':'$phone','appId':'$this->AppId','verifyCode':$verifyCode,'playTimes':3,'displayNum':'4008949580'}";
        } else {
            $body = "<VoiceVerify>
                    <appId>$this->AppId</appId>
                    <verifyCode>$verifyCode</verifyCode>
                    <playTimes>3</playTimes>
                    <to>$phone</to>
                  <VoiceVerify>";
        }
        // 大写的sig参数
        $sig = strtoupper(md5($this->AccountSid . $this->AccountToken . $this->Batch));
        // 生成请求URL
        $url = "https://$this->ServerIP:$this->ServerPort/$this->SoftVersion/Accounts/$this->AccountSid/Calls/VoiceVerify?sig=$sig";

        // 生成授权：主帐户Id + 英文冒号 + 时间戳。
        $authen = base64_encode($this->AccountSid . ":" . date('YmdHis'));
        // 生成包头
        $header = array("Accept:application/$this->BodyType", "Content-Type:application/$this->BodyType;charset=utf-8", "Authorization:$authen");
        // 发送请求
        $result = $this->curl_post($url, $body, $header);
        if ($this->BodyType == "json") {//JSON格式
            $datas = json_decode($result);
        } else { //xml格式
            $datas = simplexml_load_string(trim($result, " \t\n\r"));
        }

        //重新装填数据
        if ($datas->statusCode == 0) {
            if ($this->BodyType == "json") {
                $datas->TemplateSMS = $datas->templateSMS;
                unset($datas->templateSMS);
            }
        }
        return $datas;
    }

    /**
     * 主帐号鉴权
     * @return stdClass|string
     */
    protected function accAuth()
    {
        if ($this->ServerIP == "") {
            $data = new stdClass();
            $data->statusCode = '172004';
            $data->statusMsg = 'IP为空';
            return $data;
        }
        if ($this->ServerPort <= 0) {
            $data = new stdClass();
            $data->statusCode = '172005';
            $data->statusMsg = '端口错误（小于等于0）';
            return $data;
        }
        if ($this->SoftVersion == "") {
            $data = new stdClass();
            $data->statusCode = '172013';
            $data->statusMsg = '版本号为空';
            return $data;
        }
        if ($this->AccountSid == "") {
            $data = new stdClass();
            $data->statusCode = '172006';
            $data->statusMsg = '主帐号为空';
            return $data;
        }
        if ($this->AccountToken == "") {
            $data = new stdClass();
            $data->statusCode = '172007';
            $data->statusMsg = '主帐号令牌为空';
            return $data;
        }
        if ($this->AppId == "") {
            $data = new stdClass();
            $data->statusCode = '172012';
            $data->statusMsg = '应用ID为空';
            return $data;
        }
        return '';
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