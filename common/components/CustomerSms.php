<?php
namespace common\components;



use common\models\LeCustomers;
use common\models\VerifyCode;

use service\message\customer\GetSmsRequest;
use service\models\common\CustomerException;

/**
 * Author: Jason Y. Wang
 * Class CustomerSms
 * @package common\components
 */
class CustomerSms extends Sms
{

    /**
     * Function: sendMessage
     * Author: Jason Y. Wang
     *
     * @param GetSmsRequest $request
     * @return string
     * @throws CustomerException
     */
    public static function sendMessage(GetSmsRequest $request)
    {
        $voice = false;
        $type = 1;

        if (!preg_match('/1[34578]{1}\d{9}$/', $request->getPhone())) {
            CustomerException::customerPhoneInvalid();
        }
        switch ($request->getType()) {
            case 1:  //注册
                if (LeCustomers::findByPhone($request->getPhone())) {
                    CustomerException::customerPhoneAlreadyRegistered();
                }
                $type = self::CUSTOMER_SMS_TYPE_REGISTER;
                $voice = false;
                break;
            case 2:  //忘记密码
                //验证手机有没有注册超市
                if (!LeCustomers::findByPhone($request->getPhone())) {
                    CustomerException::customerPhoneNotRegistered();
                }
                $type = self::CUSTOMER_SMS_TYPE_FORGET;
                $voice = false;
                break;
            case 3:
                $type = self::CUSTOMER_SMS_TYPE_LOGIN;
                $voice = false;
                break;
            case 7: //修改绑定手机号
                //验证手机有没有绑定超市
                if (LeCustomers::findByPhone($request->getPhone())) {
                    CustomerException::customerPhoneAlreadyBinding();
                }
                $type = self::CUSTOMER_SMS_TYPE_CHANGE_BINDING_PHONE;
                $voice = false;
                break;
            case 8: //收银系统，员工忘记密码专用
                $type = self::CUSTOMER_SMS_TYPE_STAFF_FORGET_PASSWORD;
                $voice = false;
                break;
            case 11:
                if (LeCustomers::findByPhone($request->getPhone())) {
                    CustomerException::customerPhoneAlreadyRegistered();
                }
                $type = self::CUSTOMER_SMS_TYPE_REGISTER;
                $voice = true;
                break;
            case 21:
                //验证手机有没有注册超市
                if (!LeCustomers::findByPhone($request->getPhone())) {
                    CustomerException::customerPhoneNotRegistered();
                }
                $type = self::CUSTOMER_SMS_TYPE_FORGET;
                $voice = true;
                break;
            case 31:
                $type = self::CUSTOMER_SMS_TYPE_LOGIN;
                $voice = true;
                break;
            case 71:
                //验证手机有没有绑定超市
                if (LeCustomers::findByPhone($request->getPhone())) {
                    CustomerException::customerPhoneAlreadyBinding();
                }
                $type = self::CUSTOMER_SMS_TYPE_CHANGE_BINDING_PHONE;
                $voice = true;
                break;
            case 81: //收银系统，员工忘记密码专用
                $type = self::CUSTOMER_SMS_TYPE_STAFF_FORGET_PASSWORD;
                $voice = true;
                break;
            default:
                CustomerException::customerSmsTypeInvalid();
        }
        /** @var  VerifyCode $verify */
        $verify = VerifyCode::find()->where(['phone' => $request->getPhone(), 'verify_type' => $request->getType()])->orderBy(['created_at' => SORT_DESC])->one();
        if ($verify && strtotime($verify['created_at']) + 50 > time()) {
            CustomerException::verifyCodeAlreadySend();
        }else{
            $phone = $request->getPhone();
            $verify_type = $request->getType();
            $code = strrev(rand(1000, 9999));
            $verify = new VerifyCode();
            $verify->phone = $phone;
            $verify->code = $code;
            $verify->verify_type = $verify_type;
            $verify->created_at = date('Y-m-d H:i:s');
            $verify->count = 1;
            $verify->save();

            self::send($request->getPhone(), $type, array('code' => $code, 'minute' => 6), $voice);

        }
        return $code;
    }

}