<?php

namespace service\resources\contractor\v1;

use common\components\ContractorSms;
use common\models\LeCustomers;
use common\models\LeMerchantStore;
use common\models\RecordProducts;
use common\models\RecordWholesalers;
use common\models\VerifyCode;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\Order;
use service\message\sales\CreateOrdersRequest;
use service\message\sales\CreateOrdersResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Class createOrders
 * @package service\resources\sales\v1
 */
class createOrders extends Contractor
{

    public function run($data)
    {
        //屏蔽同一订单提交多次的情况
        $redis = Tools::getRedis();
        $key = 'contractor_create_order_' . md5($data);
        if ($redis->exists($key)) {
            ContractorException::contractorCreateOrderRepeat();
        } else {
            $redis->setex($key, 30, 1);
        }

        /** @var CreateOrdersRequest $request */
        $request = self::request();
        $request->parseFromString($data);

        $contractor = $this->initContractor($request);

        //判断下单码  如果该清单已经验证过，则直接转发到core。如果没有验证过，则抛出异常，业务员弹出输入下单码按钮
        $recordId = $request->getRecordId();
        $record_wholesalers_id = $request->getRecordWholesalersId();

        if (!$recordId || !$record_wholesalers_id) {
            ContractorException::recordListNotExist();
        }

        $record = \common\models\RecordList::findOne(['entity_id' => $recordId]);
        //判断是否还有商品
        /** @var RecordWholesalers $record_wholesalers */
        $record_wholesalers = RecordWholesalers::find()->where(['entity_id' => $record_wholesalers_id])->one();

        if (!$record || !$record_wholesalers) {
            ContractorException::recordListNotExist();
        }

        $customer = LeCustomers::findByCustomerId($record->customer_id);

        if (!$customer) {
            ContractorException::storeNotExist();
        }

        $wholesaler = LeMerchantStore::findOne(['entity_id' => $record_wholesalers->wholesaler_id]);

        if (!$wholesaler) {
            ContractorException::wholesalerNotExist();
        }


        //已输入过验证码，开始转发
        $request->setAuthToken($customer->auth_token);
        $response = $this->createOrderByCore($request, $record_wholesalers);
        $orders = $response->getOrder();
        /** @var Order $order */
        $order = array_pop($orders);
        $grand_total = $order->getGrandTotal();
        //发送通知短信
        ContractorSms::sendNoticeMessageAfterOrdered($customer->phone, $contractor->name, $grand_total, $contractor->phone);

        return $response;
    }

    /**
     * @param CreateOrdersRequest $request
     * @param RecordWholesalers $record_wholesalers
     * @return CreateOrdersRequest|CreateOrdersResponse
     */
    private function createOrderByCore($request, $record_wholesalers)
    {
        /** @var CreateOrdersRequest $response */
        $orderResponse = Proxy::sendRequest('sales.createOrders1', $request);
        $response = self::response();
        $response->parseFromString($orderResponse->getPackageBody());
        Tools::log($response->toArray(), 'createOrder.log');
        $order_ids = $response->getOrderId();
        $order_id = array_pop($order_ids);
        //将清单中商家的状态变成代下单
        $record_wholesalers->status = 1; //变为已代下单状态
        $record_wholesalers->order_id = $order_id; //记录代下单订单id
        $record_wholesalers->updated_at = Tools::getDate()->date();
        $record_wholesalers->save();
        return $response;
    }

    public static function request()
    {
        return new CreateOrdersRequest();
    }

    public static function response()
    {
        return new CreateOrdersResponse();
    }
}
