<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 18:31
 */

namespace service\resources\contractor\v1;

use common\components\ContractorSms;
use common\components\UserTools;
use common\models\LeContractor;
use common\models\VerifyCode;
use service\components\Tools;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\ContractorResponse;
use service\message\contractor\SmsLoginRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class smsLogin extends Contractor
{

    public function run($data)
    {
        /** @var SmsLoginRequest $request */
        $request = self::parseRequest($data);
        $contractor = LeContractor::findOne(['phone' => $request->getPhone()]);
        if (!$contractor) {
            ContractorException::contractorNotExist();
        }

        if ($contractor->status == 0) {
            ContractorException::contractorDisabled();
        }

        //超级验证码
        if ($request->getCode() != '1357') {
            /** @var  VerifyCode $verify */
            $verify = VerifyCode::find()->where(['phone' => $request->getPhone(),
                'verify_type' => ContractorSms::CONTRACTOR_SMS_TYPE_LOGIN])->orderBy(['created_at' => SORT_DESC])->one();

            if (!$verify) {
                ContractorException::verifyCodeError();
            }

            if (strtotime($verify['created_at']) + 60 < time()) {
                ContractorException::verifyCodeAlreadyExpired();
            }

            if ($request->getCode() != $verify['code']) {
                ContractorException::verifyCodeError();
            }
        }

        //id为7的用户不需要互剔
        if($contractor->entity_id != 7 && $contractor->entity_id != 17){
            $contractor->auth_token = UserTools::getRandomString(16);
            if (!$contractor->save()) {
                ContractorException::contractorSystemError();
            }
            Tools::getRedis()->hDel(LeContractor::CONTRACTOR_INFO_COLLECTION, $contractor->entity_id);
        }

        $request = new ContractorAuthenticationRequest();
        $request->setContractorId($contractor->entity_id);
        $request->setAuthToken($contractor->auth_token);
        $contractor = $this->initContractor($request);
        $response = $this->getContractorInfo($contractor, $this->role_permission);
        return $response;
    }

    public static function request()
    {
        return new SmsLoginRequest();
    }

    public static function response()
    {
        return new ContractorResponse();
    }

}