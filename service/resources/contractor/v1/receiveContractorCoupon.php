<?php

namespace service\resources\contractor\v1;

use common\models\contractor\ContractorCouponAmount;
use common\models\LeCustomers;
use service\components\Proxy;
use service\components\Tools;
use service\message\contractor\ReceiveContractorCouponRequest;
use service\message\core\ReceiveCouponRequest;
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
 * 业务员给商家发送优惠券
 * @package service\resources\contractor\v1
 */
class receiveContractorCoupon extends Contractor
{
    public function run($data)
    {
        /** @var ReceiveContractorCouponRequest $request */
        $request = self::parseRequest($data);
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

        // 查询该业务员的发券额度是否已经用完
        // 查询用户本月的额度
        $mothQuota = ContractorCouponAmount::find()->where(['contractor_id' => $contractor_id, 'month' => date('Y-m')])->one();
        $hasQuota = 0;// 业务员本月额度
        if ($mothQuota) {
            $hasQuota = $mothQuota->amount;
        }
        // 从redis中取出业务员已发放额度
        $redis = Tools::getRedis();
        $redisQuota = 0;
        if ($redis->exists('|' . $contractor_id . '|' . date('Y-m') . '|')) {
            $redisQuota = $redis->get('|' . $contractor_id . '|' . date('Y-m') . '|');
        }
        // 本次发放金额
        $thisQuota = 0;
        foreach ($request->getCouponList() as $kc => $vc) {
            $thisQuota = $vc->getDiscount();
        }
        if ($hasQuota < $redisQuota + $thisQuota) {
            $surplus = ($hasQuota - $redisQuota) > 0 ? ($hasQuota - $redisQuota) : 0;
            throw new ContractorException('发送失败，额度仅剩' . $surplus . '元', 40000);
        }


        // 开始循环发券
        $customer = LeCustomers::findByCustomerId($request->getCustomerId());

        foreach ($request->getCouponList() as $v) {
            // 用新的PB文件
            $newRequest = new ReceiveCouponRequest();
            $newRequest->setCustomerId($request->getCustomerId());
            $newRequest->setAuthToken($customer->auth_token);// 超市的token
            $newRequest->setRuleId($v->getRuleId());
            $newRequest->setContractorId($request->getContractorId());
            Proxy::sendRequest('sales.receiveContractorCoupon', $newRequest);
        }

        // 发送成功 就设置新的已发券金额
        $redis->set('|' . $contractor_id . '|' . date('Y-m') . '|', ($redisQuota + $thisQuota), strtotime(date('Y-m-t', strtotime('+1 month', time()))) - time());// 一个月过期时间

        return true;
    }

    public static function request()
    {
        return new ReceiveContractorCouponRequest();
    }

    public static function response()
    {
        return true;
    }
}