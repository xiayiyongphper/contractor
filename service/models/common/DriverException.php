<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/25
 * Time: 13:35
 */

namespace service\models\common;


use yii\base\Exception;

class DriverException extends Exception
{
    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }

    const DRIVER_NOT_FOUND = 52001;
    const DRIVER_NOT_FOUND_TEXT = '用户不存在';

    public static function driverNotExist()
    {
        throw new DriverException(self::DRIVER_NOT_FOUND_TEXT, self::DRIVER_NOT_FOUND);
    }

    const DRIVER_SMS_TYPE_INVALID = 52009;
    const DRIVER_SMS_TYPE_INVALID_TEXT = '短信验证码类型错误!';

    public static function driverSmsTypeInvalid()
    {
        throw new DriverException(self::DRIVER_SMS_TYPE_INVALID_TEXT, self::DRIVER_SMS_TYPE_INVALID);
    }

    const DRIVER_AUTH_TOKEN_EXPIRED = 52002;
    const DRIVER_AUTH_TOKEN_EXPIRED_TEXT = '用户信息已过期，请重新登陆！';

    public static function driverAuthTokenExpired(){
        throw new DriverException(self::DRIVER_AUTH_TOKEN_EXPIRED_TEXT, self::DRIVER_AUTH_TOKEN_EXPIRED);
    }

    const DRIVER_CODE_SEND_ERROR = 52003;
    const DRIVER_CODE_SEND_ERROR_TEXT = '验证码发送错误！';

    public static function driverCodeSendError()
    {
        throw new DriverException(self::DRIVER_CODE_SEND_ERROR_TEXT, self::DRIVER_CODE_SEND_ERROR);
    }

    const VERIFY_CODE_ALREADY_SEND = 52016;
    const VERIFY_CODE_ALREADY_SEND_TEXT = '验证码已经发送';

    public static function verifyCodeAlreadySend()
    {
        throw new DriverException(self::VERIFY_CODE_ALREADY_SEND_TEXT, self::VERIFY_CODE_ALREADY_SEND);
    }

    const SERVICE_NOT_AVAILABLE = 50001;
    const SERVICE_NOT_AVAILABLE_TEXT = '系统错误，请稍后重试！';

    public static function driverSystemError()
    {
        throw new DriverException(self::SERVICE_NOT_AVAILABLE_TEXT, self::SERVICE_NOT_AVAILABLE);
    }


    const VERIFY_CODE_ERROR = 52011;
    const VERIFY_CODE_ERROR_TEXT = '验证码错误!';

    public static function verifyCodeError()
    {
        throw new DriverException(self::VERIFY_CODE_ERROR_TEXT, self::VERIFY_CODE_ERROR);
    }

    const DRIVER_PASSWORD_ERROR = 52017;
    const DRIVER_PASSWORD_ERROR_TEXT = '密码错误，请重新输入';

    public static function driverPasswordError()
    {
        throw new DriverException(self::DRIVER_PASSWORD_ERROR_TEXT, self::DRIVER_PASSWORD_ERROR);
    }

    const ORDER_NOT_EXIST = 52020;
    const ORDER_NOT_EXIST_TEXT = '订单不存在';

    public static function orderNotExist()
    {
        throw new DriverException(self::ORDER_NOT_EXIST_TEXT, self::ORDER_NOT_EXIST);
    }

    const ORDER_INCREMENT_INVALID = 52021;
    const ORDER_INCREMENT_INVALID_TEXT = '订单号不能为空';

    public static function orderIncrementInvalid()
    {
        throw new DriverException(self::ORDER_INCREMENT_INVALID_TEXT, self::ORDER_INCREMENT_INVALID);
    }


    const DRIVER_ORDER_ID_INVALID = 52021;
    const DRIVER_ORDER_ID_INVALID_TEXT = '订单不能为空';

    public static function driverOrderIdInvalid()
    {
        throw new DriverException(self::DRIVER_ORDER_ID_INVALID_TEXT, self::DRIVER_ORDER_ID_INVALID);
    }

    const ORDER_CAN_NOT_DELIVERY_SUCCESS = 52022;
    const ORDER_CAN_NOT_DELIVERY_SUCCESS_TEXT = '该订单不能确认收货';

    public static function orderCanNotDeliverySuccess()
    {
        throw new DriverException(self::ORDER_CAN_NOT_DELIVERY_SUCCESS_TEXT, self::ORDER_CAN_NOT_DELIVERY_SUCCESS);
    }


    const ORDER_CAN_NOT_RESET = 52023;
    const ORDER_CAN_NOT_RESET_TEXT = '订单号不能重置';

    public static function orderCanNotReset()
    {
        throw new DriverException(self::ORDER_CAN_NOT_RESET_TEXT, self::ORDER_CAN_NOT_RESET);
    }

    const ORDER_NOT_BELONG_WHOLESALER = 52030;
    const ORDER_NOT_BELONG_WHOLESALER_TEXT = '该订单不属于您的供货商，无法添加到发货列表中';

    public static function orderNotBelongWholesaler()
    {
        throw new DriverException(self::ORDER_NOT_BELONG_WHOLESALER_TEXT, self::ORDER_NOT_BELONG_WHOLESALER);
    }

    const ORDER_ALREADY_EXIST = 52031;
    const ORDER_ALREADY_EXIST_TEXT = '该订单已在送货列表中，请勿重复添加';

    public static function orderAlreadyExist()
    {
        throw new DriverException(self::ORDER_ALREADY_EXIST_TEXT, self::ORDER_ALREADY_EXIST);
    }

    const ORDER_ALREADY_COMPLETED = 52032;
    const ORDER_ALREADY_COMPLETED_TEXT = '该订单已送达，请勿重复配送';
    public static function orderAlreadyCompleted()
    {
        throw new DriverException(self::ORDER_ALREADY_COMPLETED_TEXT, self::ORDER_ALREADY_COMPLETED);
    }

    const ORDER_ALREADY_CANCELED = 52033;
    const ORDER_ALREADY_CANCELED_TEXT = '该订单已取消,不能设为已送达';
    public static function orderAlreadyCanceled()
    {
        throw new DriverException(self::ORDER_ALREADY_CANCELED_TEXT, self::ORDER_ALREADY_CANCELED);
    }


    const ORDER_NOT_IN_THE_LIST = 52034;
    const ORDER_NOT_IN_THE_LIST_TEXT = '该订单不在您的送货列表中';
    public static function orderNotInTheList()
    {
        throw new DriverException(self::ORDER_NOT_IN_THE_LIST_TEXT, self::ORDER_NOT_IN_THE_LIST);
    }


}