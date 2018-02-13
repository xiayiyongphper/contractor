<?php

namespace service\resources\contractor\v1;

use common\models\contractor\ContractorCoupon;
use common\models\contractor\ContractorCouponAmount;
use common\models\LeCustomers;
use framework\components\ProxyAbstract;
use service\components\Tools;
use service\message\common\Header;
use service\message\common\Protocol;
use service\message\common\SourceEnum;
use service\message\contractor\getContractorCouponListRequest;
use service\message\contractor\getContractorCouponListResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Created by PhpStorm.
 * User: xiayiyong
 * Date: 17-12-20
 * Time: 下午17:00
 */

/**
 * Class getContractorTarget
 * 获取业务员所有可发优惠券
 * @package service\resources\contractor\v1
 */
class getContractorCouponList extends Contractor
{
    public function run($data)
    {
        /** @var getContractorCouponListRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);
        /** @var LeCustomers $customer */
        $customer = LeCustomers::find()->where(['entity_id' => $request->getCustomerId()])->one();

        if (!$customer) {
            ContractorException::storeNotExist();
        }

        // 首先判断该超市是否属于该业务员
        if ($contractor->role == self::COMMON_CONTRACTOR) {
            if ($customer->contractor_id != $contractor->entity_id) {
                throw new ContractorException('该超市不属于该业务员,不能查看该超市的可发放优惠券', 40000);
            }
            $contractor_id = $contractor->entity_id;
        } else {
            $contractor_id = $customer->contractor_id;
        }

        // 查询出可用的优惠券
        $customerBelongGroup = Tools::getCustomerBelongGroup($request->getCustomerId());// 用户所属分群id数组
        $contractorCoupons = ContractorCoupon::find()->where(['contractor_id' => $contractor_id, 'status' => 1])->asArray()->all();

//        Tools::log($customerBelongGroup, 'xiayy.log');
//        Tools::log($request->getCustomerId(), 'xiayy.log');

        // 循环查找该用户所属的分群和优惠券包含的分群的交集
        $ruleArr = [];
        foreach ($contractorCoupons as $k => $v) {
            if (!empty(array_filter(explode('|', $v['group_ids'])))) {
                if (!empty(array_intersect(array_filter(explode('|', $v['group_ids'])), $customerBelongGroup))) {
                    $ruleArr[] = $v['rule_id'];
                }
            } else {
                $ruleArr[] = $v['rule_id'];
            }
        }
//        $ruleArr[] = 153;
        // 再查询出对应的实际优惠券信息 内部调用swoole的core模块的sales.getContractorRulesListByJson方法
        $header = new Header();
        $header->setProtocol(Protocol::JSON);
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute('sales.getContractorRulesListByJson');
        $wholesalerIds = Tools::getWholesalerIdsByAreaId($customer->area_id);
        $coupon_list = ProxyAbstract::sendRequest($header, ['ruleIds' => $ruleArr, 'customerId' => $request->getCustomerId(), 'wholesalerIds' => $wholesalerIds])->getPackageBody();
        $coupon_list = json_decode($coupon_list, true);
//        Tools::log($coupon_list, 'xiayy.log');

        // 查询该业务员的发券额度是否已经用完
        // 查询用户本月的额度
        $mothQuota = ContractorCouponAmount::find()->where(['contractor_id' => $contractor->entity_id, 'month' => date('Y-m')])->one();
        $hasQuota = 0;// 业务员本月额度
        if ($mothQuota) {
            $hasQuota = $mothQuota->amount;
        }
        // 从redis中取出业务员已发放额度
        $redis = Tools::getRedis();
        $redisQuota = 0;
        if ($redis->exists('|' . $contractor->entity_id . '|' . date('Y-m') . '|')) {
            $redisQuota = $redis->get('|' . $contractor->entity_id . '|' . date('Y-m') . '|');
        }
        $surplus_quota = $hasQuota - $redisQuota;

        $response->setFrom(Tools::pb_array_filter([
            'coupon_list' => $coupon_list,
            'surplus_quota' => $surplus_quota
        ]));

        return $response;
    }

    public static function request()
    {
        return new getContractorCouponListRequest();
    }

    public static function response()
    {
        return new getContractorCouponListResponse();
    }
}