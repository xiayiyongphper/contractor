<?php
/**
 * Created by PhpStorm.
 * User: Jason Y. wang
 * Date: 16-12-28
 * Time: 下午5:43
 */

namespace service\components;


class ContractorPermission
{
    //业务员首页今日业绩
    const HOME_CONTRACTOR_STATICS = 'home/home-contractor-statics';
    //业务员首页订单跟踪
    const HOME_CONTRACTOR_ORDER_TRACKING = 'home/home-contractor-order-tracking';
    //业务员首页注册审核
    const HOME_CONTRACTOR_REGISTER_AUDIT = 'home/home-contractor-register-audit';
    //业务员首页超市列表
    const HOME_CONTRACTOR_STORE_LIST = 'home/home-contractor-store-list';
    //业务员回访记录
    const HOME_CONTRACTOR_VISIT_RECORD = 'home/home-contractor-visit-record';
    //业务员标的商品
    const HOME_CONTRACTOR_MARK_PRICE = 'home/home-contractor-mark-price';
    //业务员首页展示店铺列表
    const HOME_CONTRACTOR_STORE = 'home/home-contractor-store';
    //订单详情页
    const CONTRACTOR_ORDER_DETAIL = 'order/contractor-order-detail';
    //未审核超市列表
    const CONTRACTOR_REVIEW_STORE_LIST = 'store/contractor-review-store-list';
    //超市详情
    const CONTRACTOR_EDIT_STORE = 'store/contractor-edit-store';
    //搜索超市
    const CONTRACTOR_STORE_SEARCH = 'store/contractor-store-search';
    //新增意向超市
    const CONTRACTOR_STORE_INTENTION_CREATE = 'store/contractor-store-intention-create';
    //超市详情
    const CONTRACTOR_STORE_DETAIL = 'store/contractor-store-detail';
    //拜访记录列表
    const CONTRACTOR_VISIT_STORE_LIST = 'store/contractor-visit-store-list';
    //新增拜访记录
    const CONTRACTOR_STORE_VISIT_NEW = 'store/contractor-store-visit-new';
    //新增简单拜访记录
    const CONTRACTOR_STORE_VISIT_BRIEF_NEW = 'store/contractor-store-visit-brief-new';
    //标的价格商品列表
    const CONTRACTOR_MARK_PRICE_LIST = 'store/contractor-mark-price-list';
    //标的价格商品列表
    const CONTRACTOR_MARK_PRICE_PRODUCT_DETAIL = 'store/contractor-mark-price-product-detail';
    //订单跟踪列表
    const ORDER_TRACKING_COLLECTION = 'order/order-tracking-collection';
    //订单跟踪列表
    const ORDER_STATUS_COLLECTION = 'order/order-status-collection';

    public static function contractorStoreVisitBriefCreatePermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_STORE_VISIT_BRIEF_NEW, $role_permission)) {
                return true;
            }
        }
        return false;
    }


    public static function contractorMarkPriceProductDetailPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_MARK_PRICE_PRODUCT_DETAIL, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorMarkPriceListPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_MARK_PRICE_LIST, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorStoreVisitNewCreatePermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_STORE_VISIT_NEW, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorVisitStoreListCreatePermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_VISIT_STORE_LIST, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorStoreDetailCreatePermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_STORE_DETAIL, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorStoreIntentionCreatePermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_STORE_INTENTION_CREATE, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorStoreSearchPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_STORE_SEARCH, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorEditStorePermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_EDIT_STORE, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorReviewStoreListPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_REVIEW_STORE_LIST, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function contractorOrderDetailPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::CONTRACTOR_ORDER_DETAIL, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function homeContractorStorePermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::HOME_CONTRACTOR_STORE, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function homeContractorStaticsPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::HOME_CONTRACTOR_STATICS, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function homeContractorOrderTrackingPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::HOME_CONTRACTOR_ORDER_TRACKING, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function homeContractorRegisterAuditPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::HOME_CONTRACTOR_REGISTER_AUDIT, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function homeContractorStoreListPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::HOME_CONTRACTOR_STORE_LIST, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function homeContractorMarkPricedPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::HOME_CONTRACTOR_MARK_PRICE, $role_permission)) {
                return true;
            }
        }
        return false;
    }

    public static function homeContractorVisitRecordPermission($role_permission)
    {
        if (is_array($role_permission)) {
            if (in_array('*', $role_permission) || in_array(self::HOME_CONTRACTOR_VISIT_RECORD, $role_permission)) {
                return true;
            }
        }
        return false;
    }

}