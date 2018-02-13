<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/25
 * Time: 13:35
 */

namespace service\models\common;


use yii\base\Exception;

class ContractorException extends Exception
{

    const SERVICE_NOT_AVAILABLE = 40001;
    const SERVICE_NOT_AVAILABLE_TEXT = '系统错误，请稍后重试！';

    const INVALID_PARAM = 40002;
    const INVALID_PARAM_TEXT = '非法参数！';
    const NO_PERMISSION = 40003;
    const NO_PERMISSION_TEXT = '您没有权限执行此操作！';

    const OPERATE_FAIL = 40005;
    const OPERATE_FAIL_TEXT = '操作失败！';

    const CONTRACTOR_NOT_FOUND = 42001;
    const CONTRACTOR_NOT_FOUND_TEXT = '业务员不存在';

    const CONTRACTOR_AUTH_TOKEN_EXPIRED = 42002;
    const CONTRACTOR_AUTH_TOKEN_EXPIRED_TEXT = '用户信息已过期，请重新登陆！';

    const CONTRACTOR_CODE_SEND_ERROR = 42003;
    const CONTRACTOR_CODE_SEND_ERROR_TEXT = '验证码发送错误！';

    const CONTRACTOR_PHONE_INVALID = 42004;
    const CONTRACTOR_PHONE_INVALID_TEXT = '手机号码有误！';

    const CONTRACTOR_STORE_INFO_FORBIDDEN = 42005;
    const CONTRACTOR_STORE_INFO_FORBIDDEN_TEXT = '无法查看该超市详情！';

    const CONTRACTOR_SMS_TYPE_INVALID = 42009;
    const CONTRACTOR_SMS_TYPE_INVALID_TEXT = '短信验证码类型错误!';

    const VERIFY_CODE_ERROR = 42011;
    const VERIFY_CODE_ERROR_TEXT = '验证码错误!';

    const VERIFY_CODE_ALREADY_SEND = 42016;
    const VERIFY_CODE_ALREADY_SEND_TEXT = '验证码已经发送';

    const VERIFY_CODE_ALREADY_EXPIRED = 42017;
    const VERIFY_CODE_ALREADY_EXPIRED_TEXT = '验证码已过期';



	const BUSINESS_LICENSE_NO_EXIST = 42100;
	const BUSINESS_LICENSE_NO_EXIST_TEXT = '此营业执照号已存在，无法重复添加！';
	const STORE_INTENTION_NOT_FOUND = 42101;
	const STORE_INTENTION_NOT_FOUND_TEXT = '未找到此意向店铺！';





    public function __construct($message, $code)
    {
        parent::__construct($message, $code);
    }

    public static function contractorPhoneInvalid()
    {
        throw new ContractorException(self::CONTRACTOR_PHONE_INVALID_TEXT, self::CONTRACTOR_PHONE_INVALID);
    }

    public static function contractorAuthTokenExpired(){
        throw new ContractorException(self::CONTRACTOR_AUTH_TOKEN_EXPIRED_TEXT, self::CONTRACTOR_AUTH_TOKEN_EXPIRED);
    }

    public static function contractorNotExist()
    {
        throw new ContractorException(self::CONTRACTOR_NOT_FOUND_TEXT, self::CONTRACTOR_NOT_FOUND);
    }

    const CONTRACTOR_FOUND_DISABLED = 42002;
    const CONTRACTOR_FOUND_DISABLED_TEXT = '您的账号已停用，请联系运营人员';

    public static function contractorDisabled()
    {
        throw new ContractorException(self::CONTRACTOR_FOUND_DISABLED_TEXT, self::CONTRACTOR_FOUND_DISABLED);
    }

    const CONTRACTOR_NOT_ALLOCATE_ROLE = 44009;
    const CONTRACTOR_NOT_ALLOCATE_ROLE_TEXT = '业务员未分配角色，请联系运营人员';

    public static function invalidParam()
    {
        throw new ContractorException(self::INVALID_PARAM_TEXT, self::INVALID_PARAM);
    }
    public static function noPermission()
    {
        throw new ContractorException(self::NO_PERMISSION_TEXT, self::NO_PERMISSION);
    }

    public static function operateFail()
    {
        throw new ContractorException(self::OPERATE_FAIL_TEXT, self::OPERATE_FAIL);
    }
    public static function contractorNotAllocateRole()
    {
        throw new ContractorException(self::CONTRACTOR_NOT_ALLOCATE_ROLE_TEXT, self::CONTRACTOR_NOT_ALLOCATE_ROLE);
    }

    public static function contractorSmsTypeInvalid()
    {
        throw new ContractorException(self::CONTRACTOR_SMS_TYPE_INVALID_TEXT, self::CONTRACTOR_SMS_TYPE_INVALID);
    }

    public static function verifyCodeAlreadySend()
    {
        throw new ContractorException(self::VERIFY_CODE_ALREADY_SEND_TEXT, self::VERIFY_CODE_ALREADY_SEND);
    }

    public static function verifyCodeAlreadyExpired()
    {
        throw new ContractorException(self::VERIFY_CODE_ALREADY_EXPIRED_TEXT, self::VERIFY_CODE_ALREADY_EXPIRED);
    }

    public static function verifyCodeError()
    {
        throw new ContractorException(self::VERIFY_CODE_ERROR_TEXT, self::VERIFY_CODE_ERROR);
    }

    public static function contractorSystemError()
    {
        throw new ContractorException(self::SERVICE_NOT_AVAILABLE_TEXT, self::SERVICE_NOT_AVAILABLE);
    }
    public static function contractorCodeSendError()
    {
        throw new ContractorException(self::CONTRACTOR_CODE_SEND_ERROR_TEXT, self::CONTRACTOR_CODE_SEND_ERROR);
    }
	public static function businessLicenseNoExist()
	{
		throw new ContractorException(self::BUSINESS_LICENSE_NO_EXIST_TEXT, self::BUSINESS_LICENSE_NO_EXIST);
	}
	public static function storeIntentionNotFound()
	{
		throw new ContractorException(self::STORE_INTENTION_NOT_FOUND_TEXT, self::STORE_INTENTION_NOT_FOUND);
	}

    public static function contractorStoreInfoForbidden()
    {
        throw new ContractorException(self::CONTRACTOR_STORE_INFO_FORBIDDEN_TEXT, self::CONTRACTOR_STORE_INFO_FORBIDDEN);
    }

    const MARK_PRICE_PRODUCT_NOT_FOUND = 40004;
    const MARK_PRICE_PRODUCT_NOT_FOUND_TEXT = '找不到该商品';

    public static function markPriceProductNotFound()
    {
        throw new ContractorException(self::MARK_PRICE_PRODUCT_NOT_FOUND_TEXT, self::MARK_PRICE_PRODUCT_NOT_FOUND);
    }

    const CONTRACTOR_INIT_ERROR = 39001;
    const CONTRACTOR_INIT_ERROR_TEXT = '业务员不存在';

    public static function contractorInitError()
    {
        throw new ContractorException(self::CONTRACTOR_INIT_ERROR_TEXT, self::CONTRACTOR_INIT_ERROR);
    }

    const CONTRACTOR_PERMISSION_ERROR = 39004;
    const CONTRACTOR_PERMISSION_ERROR_TEXT = '无权访问该模块';

    public static function contractorPermissionError()
    {
        throw new ContractorException(self::CONTRACTOR_PERMISSION_ERROR_TEXT, self::CONTRACTOR_PERMISSION_ERROR);
    }

    const CONTRACTOR_CITY_LIST_EMPTY = 39005;
    const CONTRACTOR_CITY_LIST_EMPTY_TEXT = '业务员名下城市为空';

    public static function contractorCityListEmpty()
    {
        throw new ContractorException(self::CONTRACTOR_CITY_LIST_EMPTY_TEXT, self::CONTRACTOR_CITY_LIST_EMPTY);
    }

    const FIELD_SPECIAL_CHARACTER = 39006;
    const FIELD_SPECIAL_CHARACTER_TEXT = '不能含有特殊字符';

    public static function fieldSpecialCharacter()
    {
        throw new ContractorException(self::FIELD_SPECIAL_CHARACTER_TEXT, self::FIELD_SPECIAL_CHARACTER);
    }

    const CONTRACTOR_CITY_EMPTY = 39007;
    const CONTRACTOR_CITY_EMPTY_TEXT = '请先选择城市';

    public static function contractorCityEmpty()
    {
        throw new ContractorException(self::CONTRACTOR_CITY_EMPTY_TEXT, self::CONTRACTOR_CITY_EMPTY);
    }

    const CUSTOMER_PHONE_ALREADY_REGISTERED = 12005;
    const CUSTOMER_PHONE_ALREADY_REGISTERED_TEXT = '该手机号码已注册！';

    public static function customerPhoneAlreadyRegistered()
    {
        throw new ContractorException(self::CUSTOMER_PHONE_ALREADY_REGISTERED_TEXT, self::CUSTOMER_PHONE_ALREADY_REGISTERED);
    }

    const CONTRACTOR_ROLE_ERROR = 39010;
    const CONTRACTOR_ROLE_ERROR_TEXT = '业务员角色错误';

    public static function contractorRoleError()
    {
        throw new ContractorException(self::CONTRACTOR_ROLE_ERROR_TEXT, self::CONTRACTOR_ROLE_ERROR);
    }

    const CONTRACTOR_ORDER_NOT_EXIST = 39404;
    const CONTRACTOR_ORDER_NOT_EXIST_TEXT = '订单不存在';

    public static function orderNotExist()
    {
        throw new ContractorException(self::CONTRACTOR_ORDER_NOT_EXIST_TEXT, self::CONTRACTOR_ORDER_NOT_EXIST);
    }

    const CONTRACTOR_STORE_NOT_EXIST = 39405;
    const CONTRACTOR_STORE_NOT_EXIST_TEXT = '超市不存在';

    public static function storeNotExist()
    {
        throw new ContractorException(self::CONTRACTOR_STORE_NOT_EXIST_TEXT, self::CONTRACTOR_STORE_NOT_EXIST);
    }

    const PASSED_MONTH_NOT_EDITABLE = 39406;
    const TARGET_ALREADY_SET = 39407;

    const SET_AT_LEAST_ONE_TASK = 39408;
    const SET_AT_LEAST_ONE_TASK_TEXT = '请设置至少一个指标';
    public static function setAtLeastOneTask()
    {
        throw new ContractorException(self::SET_AT_LEAST_ONE_TASK_TEXT, self::SET_AT_LEAST_ONE_TASK);
    }

    const VISITING_RECORD_ID_DOES_NOT_EXIST_NUM = 39409;
    const VISITING_RECORD_ID_DOES_NOT_EXIST_TXT = '拜访记录id不存在';

    public static function visitingRecordIdExist()
    {
        throw new ContractorException(self::VISITING_RECORD_ID_DOES_NOT_EXIST_TXT, self::VISITING_RECORD_ID_DOES_NOT_EXIST_NUM);
    }

    const RECORD_LIST_SAVE_FAILED = 39410;
    const RECORD_LIST_SAVE_FAILED_TEXT = '清单保存失败';

    public static function recordListSaveFailed()
    {
        throw new ContractorException(self::RECORD_LIST_SAVE_FAILED_TEXT, self::RECORD_LIST_SAVE_FAILED);
    }

    const RECORD_LIST_UPDATE_FAILED = 39411;
    const RECORD_LIST_UPDATE_FAILED_TEXT = '清单更新失败';

    public static function recordListUpdateFailed()
    {
        throw new ContractorException(self::RECORD_LIST_UPDATE_FAILED_TEXT, self::RECORD_LIST_UPDATE_FAILED);
    }

    const RECORD_LIST_NOT_EXIST = 39412;
    const RECORD_LIST_NOT_EXIST_TEXT = '清单不存在或已完成';

    public static function recordListNotExist()
    {
        throw new ContractorException(self::RECORD_LIST_NOT_EXIST_TEXT, self::RECORD_LIST_NOT_EXIST);
    }

    const RECORD_WHOLESALERS_SAVE_FAILED = 39413;
    const RECORD_WHOLESALERS_SAVE_FAILED_TEXT = '清单供货商保存失败';

    public static function recordWholesalersSaveFailed()
    {
        throw new ContractorException(self::RECORD_WHOLESALERS_SAVE_FAILED_TEXT, self::RECORD_WHOLESALERS_SAVE_FAILED);
    }

    const RECORD_PRODUCTS_SAVE_FAILED = 39414;
    const RECORD_PRODUCTS_SAVE_FAILED_TEXT = '清单商品保存失败';

    public static function recordProductsSaveFailed()
    {
        throw new ContractorException(self::RECORD_PRODUCTS_SAVE_FAILED_TEXT, self::RECORD_PRODUCTS_SAVE_FAILED);
    }

    const RECORD_PRODUCTS_DELETE_FAILED = 39415;
    const RECORD_PRODUCTS_DELETE_FAILED_TEXT = '清单商品删除失败';

    public static function recordProductsDeleteFailed()
    {
        throw new ContractorException(self::RECORD_PRODUCTS_DELETE_FAILED_TEXT, self::RECORD_PRODUCTS_DELETE_FAILED);
    }

    const RECORD_WHOLESALERS_DELETE_FAILED = 39416;
    const RECORD_WHOLESALERS_DELETE_FAILED_TEXT = '清单供货商删除失败';

    public static function recordWholesalersDeleteFailed()
    {
        throw new ContractorException(self::RECORD_WHOLESALERS_DELETE_FAILED_TEXT, self::RECORD_WHOLESALERS_DELETE_FAILED);
    }

    const CONTRACTOR_CUSTOMER_NOT_MATCH= 39417;
    const CONTRACTOR_CUSTOMER_NOT_MATCH_TEXT= '该业务员未与该超市签约';

    public static function customerNotMatch()
    {
        throw new ContractorException(self::CONTRACTOR_CUSTOMER_NOT_MATCH_TEXT, self::CONTRACTOR_CUSTOMER_NOT_MATCH);
    }

    const GROUP_ID_EMPTY_NUM= 39418;
    const GROUP_ID_EMPTY_TXT= '路线规划信息错误或者不存在';

    public static function groupIdEmpty()
    {
        throw new ContractorException(self::GROUP_ID_EMPTY_TXT, self::GROUP_ID_EMPTY_NUM);
    }

    const NEED_ORDER_CODE = 39419;
    const NEED_ORDER_CODE_TXT= '请获取下单码';

    public static function needOrderCode()
    {
        throw new ContractorException(self::NEED_ORDER_CODE_TXT, self::NEED_ORDER_CODE);
    }

    const SAVE_FAILED = 39420;
    const SAVE_FAILED_TEXT = '保存失败';

    public static function SaveFailed()
    {
        throw new ContractorException(self::SAVE_FAILED_TEXT, self::SAVE_FAILED);
    }

    const SHELVES_PRODUCT_EXIST_NUM= 39421;
    const SHELVES_PRODUCT_EXIST_TXT= '商品已在货架上';
    public static function shelvesProductExist()
    {
        throw new ContractorException(self::SHELVES_PRODUCT_EXIST_TXT, self::SHELVES_PRODUCT_EXIST_NUM);
    }

    const INVALID_LSIN_NUM= 39422;
    const INVALID_LSIN_TXT= '无效的lsin';
    public static function invalidLsin()
    {
        throw new ContractorException(self::INVALID_LSIN_TXT, self::INVALID_LSIN_NUM);
    }

    const RECORD_NOT_ALLOW = 39423;
    const RECORD_NOT_ALLOW_TEXT = '非普通业务员不允许录单';

    public static function recordNotAllow()
    {
        throw new ContractorException(self::RECORD_NOT_ALLOW_TEXT, self::RECORD_NOT_ALLOW);
    }

    const WHOLESALER_NOT_DELIVERY = 39424;
    const WHOLESALER_NOT_DELIVERY_TEXT = '超市不在配送范围';

    public static function wholesalerNotDelivery()
    {
        throw new ContractorException(self::WHOLESALER_NOT_DELIVERY_TEXT, self::WHOLESALER_NOT_DELIVERY);
    }

    const CONTRACTOR_WHOLESALER_NOT_EXIST = 39425;
    const CONTRACTOR_WHOLESALER_NOT_EXIST_TEXT = '供应商不存在';

    public static function wholesalerNotExist()
    {
        throw new ContractorException(self::CONTRACTOR_WHOLESALER_NOT_EXIST_TEXT, self::CONTRACTOR_WHOLESALER_NOT_EXIST);
    }

    const CONTRACTOR_CREATE_ORDER_REPEAT = 39426;
    const CONTRACTOR_CREATE_ORDER_REPEAT_TEXT = '订单重复提交，请稍后重试！';

    public static function contractorCreateOrderRepeat()
    {
        throw new ContractorException(self::CONTRACTOR_CREATE_ORDER_REPEAT_TEXT, self::CONTRACTOR_CREATE_ORDER_REPEAT);
    }
}
