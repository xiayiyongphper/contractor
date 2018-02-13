<?php

namespace common\components;

use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\VerifyCode;
use service\message\customer\GetSmsRequest;
use service\models\common\ContractorException;
use service\models\common\CustomerException;

/**
 * Author: Jason Y. Wang
 * Class ContractorSms
 * @package common\components
 */
class ContractorSms extends Sms
{

    /**
     * Function: sendMessage
     * Author: Jason Y. Wang
     *
     * @param GetSmsRequest $request
     * @return string
     * @throws ContractorException
     */
    public static function sendMessage(GetSmsRequest $request)
    {
        $voice = false;
        $type = 1;

        if (!preg_match('/1[34578]{1}\d{9}$/', $request->getPhone())) {
            ContractorException::contractorPhoneInvalid();
        }
        switch ($request->getType()) {
            case 1:  //快捷登录
                if (!LeContractor::findByPhone($request->getPhone())) {
                    ContractorException::contractorNotExist();
                }
                $type = self::CONTRACTOR_SMS_TYPE_LOGIN;
                $voice = false;
                break;
            case 2:  //快捷注册超市
                if (LeCustomers::findByPhone($request->getPhone())) {
                    CustomerException::customerPhoneAlreadyRegistered();
                }
                $type = self::CONTRACTOR_SMS_TYPE_REGISTER;
                $voice = false;
                break;
            case 3:  //代下单  收货号码手机号，不用判断用户是否存在
                $type = self::CONTRACTOR_SMS_TYPE_PLACE_ORDER;
                $voice = false;
                break;
            default:
                ContractorException::contractorSmsTypeInvalid();
        }
        /** @var  VerifyCode $verify */
        $verify = VerifyCode::find()->where(['phone' => $request->getPhone(), 'verify_type' => $type])->orderBy(['created_at' => SORT_DESC])->one();
        //60秒内已经发送过验证码
        if ($verify && strtotime($verify['created_at']) + 60 > time()) {
            ContractorException::verifyCodeAlreadySend();
        }

        $code = strrev(rand(1000, 9999));

        if ($type == self::CONTRACTOR_SMS_TYPE_PLACE_ORDER) {  //业务员下单码一些特殊字段
            $contractor_id = $request->getContractorId();
            $contractor = LeContractor::findOne(['entity_id' => $contractor_id]);
            $grand_total = $request->getGrandTotal();
            if (!$contractor) {
                ContractorException::contractorNotExist();
            }
            self::send($request->getPhone(), $type, array('code' => $code, 'name' => $contractor->name,
                'price' => $grand_total, 'phone' => $contractor->phone), $voice);

        } else {
            self::send($request->getPhone(), $type, array('code' => $code, 'minute' => 1), $voice);
        }

        //发送后保存验证码
        $verify = new VerifyCode();
        $verify->phone = $request->getPhone();
        $verify->code = $code;
        $verify->verify_type = $type;
        $verify->created_at = date('Y-m-d H:i:s');
        $verify->count = 1;
        $verify->save();

        return $code;
    }

    public static function sendNoticeMessageAfterOrdered($phone, $contractor_name, $grand_total,$contractor_phone)
    {
        $type = $type = self::CONTRACTOR_SMS_TYPE_NOTICE_AFTER_ORDERED;
        $voice = false;
        self::send($phone, $type, array('name' => $contractor_name,
            'price' => $grand_total, 'phone' => $contractor_phone), $voice);
    }
}