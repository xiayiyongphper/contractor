<?php

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use framework\db\readonly\models\core\SalesFlatOrder;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\Order;
use service\message\common\OrderAction;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use service\resources\Exception;

/**
 * Class orderCancel
 * @package service\resources\sales\v1
 * 业务员1.8新增
 */
class orderCancel extends Contractor
{
    public function run($data)
    {
        /** @var OrderAction $request */
        $request = self::parseRequest($data);
        $this->initContractor($request);
        $order = SalesFlatOrder::findOne(['entity_id' => $request->getOrderId()]);
        if (!$order) {
            ContractorException::orderNotExist();
        }
        $customer = LeCustomers::findByCustomerId($order->customer_id);
        if (!$customer) {
            Exception::customerNotExist();
        }
        $request->setCustomerId($order->customer_id);
        $request->setCancelReason('业务员代取消订单');
        //超市的token
        $request->setAuthToken($customer->auth_token);
        Tools::log($request->toArray(), 'orderCancel.log');
        /** @var Order $response */
        $orderResponse = Proxy::sendRequest('sales.cancel', $request);
        $response = self::response();
        $response->parseFromString($orderResponse->getPackageBody());
        return $response;

    }

    public static function request()
    {
        return new OrderAction();
    }

    public static function response()
    {
        return new Order();
    }
}