<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/25
 * Time: 11:31
 */

namespace service\resources\contractor\v1;

use common\components\ContractorSms;
use service\components\Tools;
use service\message\customer\GetSmsRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class sms extends Contractor
{
    /**
     * Function: run
     * Author: Jason Y. Wang
     *
     * @param $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var GetSmsRequest $request */
        $request = self::parseRequest($data);
        Tools::log($request->toArray(), 'sms.log');
        //判断发短信token
        if ($request->getToken() != ContractorSms::SMS_TOKEN) {
            ContractorException::contractorAuthTokenExpired();
        }
        //发送短信
        $result = ContractorSms::sendMessage($request);
        //验证短信发送结果
        if (!$result) {
            ContractorException::contractorCodeSendError();
        }
        return true;
    }

    public static function request()
    {
        return new GetSmsRequest();
    }

    public static function response()
    {
        return true;
    }
}