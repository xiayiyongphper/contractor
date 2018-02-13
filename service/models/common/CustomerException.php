<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/25
 * Time: 13:35
 */

namespace service\models\common;


use yii\base\Exception;

class CustomerException extends Exception
{
    const SERVICE_NOT_AVAILABLE = 10001;
    const SERVICE_NOT_AVAILABLE_TEXT = '系统错误，请稍后重试！';
    const SYSTEM_MAINTENANCE = 10002;
    const SYSTEM_MAINTENANCE_TEXT = '系统维护中，请稍后重试';
    const SYSTEM_NOT_FOUND = 10003;
    const SYSTEM_NOT_FOUND_TEXT = '找不到相关信息';
    const RESOURCE_NOT_FOUND = 10004;
    const RESOURCE_NOT_FOUND_TEXT = '找不到相关资源';
    const INVALID_REQUEST_ROUTE = 10005;
    const INVALID_REQUEST_ROUTE_TEXT = '非法的请求';
    const INVALID_PARAMS = 10006;
    const INVALID_PARAMS_TEXT = '参数错误';

    /**
     * customer not found
     */
    const CUSTOMER_NOT_FOUND = 12001;
    const CUSTOMER_NOT_FOUND_TEXT = '用户不存在';
    /**
     * customer auth token expired
     */
    const CUSTOMER_AUTH_TOKEN_EXPIRED = 12002;
    const CUSTOMER_AUTH_TOKEN_EXPIRED_TEXT = '用户信息已过期，请重新登陆！';
    const CUSTOMER_SHOPPING_CART_EMPTY = 12003;
    const CUSTOMER_SHOPPING_CART_EMPTY_TEXT = '购物车为空，请先添加商品！';
    const CUSTOMER_PHONE_INVALID = 12004;
    const CUSTOMER_PHONE_INVALID_TEXT = '手机号码有误！';
    const CUSTOMER_PHONE_ALREADY_REGISTERED = 12005;
    const CUSTOMER_PHONE_ALREADY_REGISTERED_TEXT = '该手机号码已注册！';
    const CUSTOMER_PHONE_NOT_REGISTERED = 12006;
    const CUSTOMER_PHONE_NOT_REGISTERED_TEXT = '该手机号码未注册！';
    const CUSTOMER_USERNAME_ALREADY_REGISTERED = 12007;
    const CUSTOMER_USERNAME_ALREADY_REGISTERED_TEXT = '该用户名已注册！';
    const CUSTOMER_PHONE_ALREADY_BINDING = 12008;
    const CUSTOMER_PHONE_ALREADY_BINDING_TEXT = '该手机号码已被绑定！';
    const CUSTOMER_SMS_TYPE_INVALID = 12009;
    const CUSTOMER_SMS_TYPE_INVALID_TEXT = '短信验证码类型错误!';
    const CUSTOMER_REGISTER_FAILED = 12010;
    const CUSTOMER_REGISTER_FAILED_TEXT = '注册失败!';
    const VERIFY_CODE_ERROR = 12011;
    const VERIFY_CODE_ERROR_TEXT = '验证码错误!';
    const CUSTOMER_REGISTER_NUMERIC = 12012;
    const CUSTOMER_REGISTER_NUMERIC_TEXT = '用户名不能为数字!';
    const CHANGE_BANDING_PHONE_ERROR = 12013;
    const CHANGE_BANDING_PHONE_ERROR_TEXT = '修改绑定手机失败!';
    const VERIFY_CODE_EXPIRED = 12014;
    const VERIFY_CODE_EXPIRED_TEXT = '验证码已过期';
    const CUSTOMER_ORIGINAL_PASSWORD_ERROR = 12015;
    const CUSTOMER_ORIGINAL_PASSWORD_ERROR_TEXT = '旧密码错误';
    const VERIFY_CODE_ALREADY_SEND = 12016;
    const VERIFY_CODE_ALREADY_SEND_TEXT = '验证码已经发送';
    const CUSTOMER_PASSWORD_ERROR = 12017;
    const CUSTOMER_PASSWORD_ERROR_TEXT = '密码错误，请重新输入';
    const PRODUCT_QTY_LOW = 13002;
    const UPDATE_CART_PARAM_ERROR= 13003;
    const UPDATE_CART_PARAM_ERROR_TEXT= '更新购物车参数异常';
    const FLUSH_CART_PARAM_ERROR= 13004;
    const FLUSH_CART_PARAM_ERROR_TEXT= '更新购物车参数异常';

    const CUSTOMER_INFO_EMPTY = 12018;
    const CUSTOMER_INFO_EMPTY_TEXT = '用户所在省市为空！';

    const CUSTOMER_INTENTION_NOT_EXIST= 14001;
    const CUSTOMER_INTENTION_NOT_EXIST_TEXT= '意向超市不存在';


    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }

    public static function invalidParams()
    {
        throw new CustomerException(self::INVALID_PARAMS_TEXT, self::INVALID_PARAMS);
    }

    public static function productQtyLow($message)
    {
        throw new CustomerException($message, self::PRODUCT_QTY_LOW);
    }

    public static function updateCartParamError()
    {
        throw new CustomerException(self::UPDATE_CART_PARAM_ERROR_TEXT, self::UPDATE_CART_PARAM_ERROR);
    }

    public static function flushCartParamError()
    {
        throw new CustomerException(self::FLUSH_CART_PARAM_ERROR_TEXT, self::FLUSH_CART_PARAM_ERROR);
    }

    public static function customerNotExist()
    {
        throw new CustomerException(self::CUSTOMER_NOT_FOUND_TEXT, self::CUSTOMER_NOT_FOUND);
    }

    public static function customerInfoEmpty()
    {
        throw new CustomerException(self::CUSTOMER_INFO_EMPTY_TEXT, self::CUSTOMER_INFO_EMPTY);
    }

    public static function customerPasswordError()
    {
        throw new CustomerException(self::CUSTOMER_PASSWORD_ERROR_TEXT, self::CUSTOMER_PASSWORD_ERROR);
    }

    public static function verifyCodeAlreadySend()
    {
        throw new CustomerException(self::VERIFY_CODE_ALREADY_SEND_TEXT, self::VERIFY_CODE_ALREADY_SEND);
    }

    public static function customerSystemMaintenance()
    {
        throw new CustomerException(self::SYSTEM_MAINTENANCE_TEXT, self::SYSTEM_MAINTENANCE);
    }

    public static function customerSystemError()
    {
        throw new CustomerException(self::SERVICE_NOT_AVAILABLE_TEXT, self::SERVICE_NOT_AVAILABLE);
    }

    public static function customerOriginalPasswordError()
    {
        throw new CustomerException(self::CUSTOMER_ORIGINAL_PASSWORD_ERROR_TEXT, self::CUSTOMER_ORIGINAL_PASSWORD_ERROR);
    }

    public static function resourceNotFound()
    {
        throw new CustomerException(self::RESOURCE_NOT_FOUND_TEXT, self::RESOURCE_NOT_FOUND);
    }

    public static function customerPhoneInvalid()
    {
        throw new CustomerException(self::CUSTOMER_PHONE_INVALID_TEXT, self::CUSTOMER_PHONE_INVALID);
    }

    public static function customerPhoneAlreadyRegistered()
    {
        throw new CustomerException(self::CUSTOMER_PHONE_ALREADY_REGISTERED_TEXT, self::CUSTOMER_PHONE_ALREADY_REGISTERED);
    }

    public static function customerIntentionNotExist()
    {
        throw new CustomerException(self::CUSTOMER_INTENTION_NOT_EXIST_TEXT, self::CUSTOMER_INTENTION_NOT_EXIST);
    }

    public static function customerUsernameAlreadyRegistered()
    {
        throw new CustomerException(self::CUSTOMER_USERNAME_ALREADY_REGISTERED_TEXT, self::CUSTOMER_USERNAME_ALREADY_REGISTERED);
    }

    public static function customerPhoneNotRegistered()
    {
        throw new CustomerException(self::CUSTOMER_PHONE_NOT_REGISTERED_TEXT, self::CUSTOMER_PHONE_NOT_REGISTERED);
    }

    public static function customerPhoneAlreadyBinding()
    {
        throw new CustomerException(self::CUSTOMER_PHONE_ALREADY_BINDING_TEXT, self::CUSTOMER_PHONE_ALREADY_BINDING);
    }

    public static function customerSmsTypeInvalid()
    {
        throw new CustomerException(self::CUSTOMER_SMS_TYPE_INVALID_TEXT, self::CUSTOMER_SMS_TYPE_INVALID);
    }

    public static function systemNotFound()
    {
        throw new CustomerException(self::SYSTEM_NOT_FOUND_TEXT, self::SYSTEM_NOT_FOUND);
    }

    public static function customerAuthTokenExpired()
    {
        throw new CustomerException(self::CUSTOMER_AUTH_TOKEN_EXPIRED_TEXT, self::CUSTOMER_AUTH_TOKEN_EXPIRED);
    }

    public static function customerRegisterFailed()
    {
        throw new CustomerException(self::CUSTOMER_REGISTER_FAILED_TEXT, self::CUSTOMER_REGISTER_FAILED);
    }

    public static function customerRegisterUsernameNumeric()
    {
        throw new CustomerException(self::CUSTOMER_REGISTER_NUMERIC_TEXT, self::CUSTOMER_REGISTER_NUMERIC);
    }

    public static function verifyCodeError()
    {
        throw new CustomerException(self::VERIFY_CODE_ERROR_TEXT, self::VERIFY_CODE_ERROR);
    }

    public static function verifyCodeExpired()
    {
        throw new CustomerException(self::VERIFY_CODE_EXPIRED_TEXT, self::VERIFY_CODE_EXPIRED);
    }

    public static function invalidRequestRoute()
    {
        throw new CustomerException(self::INVALID_REQUEST_ROUTE_TEXT, self::INVALID_REQUEST_ROUTE);
    }

    public static function changeBindingPhoneError()
    {
        throw new CustomerException(self::CHANGE_BANDING_PHONE_ERROR_TEXT, self::CHANGE_BANDING_PHONE_ERROR);
    }


}