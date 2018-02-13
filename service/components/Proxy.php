<?php
namespace service\components;


use common\models\LeCustomers;
use framework\components\ProxyAbstract;
use framework\components\es\Console;
use framework\components\es\Timeline;

use service\message\common\Header;
use service\message\common\SourceEnum;
use framework\message\Message;
use service\message\core\CouponReceiveListRequest;
use service\message\core\CouponReceiveListResponse;
use service\message\merchant\SaleRuleRequest;
use service\message\merchant\SaleRuleResponse;
use service\message\sales\OrderCollectionRequest;
use service\message\sales\OrderNumberResponse;


/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/25
 * Time: 11:02
 */
class Proxy extends ProxyAbstract
{

    const ROUTE_SALES_COUPON_RECEIVE_LIST = 'sales.couponReceiveList';
    const ROUTE_SALES_RULE = 'sales.saleRule';

    /**
     * Function: initHeader
     * Author: Jason Y. Wang
     *
     * @param $route
     * @return Header
     */
    protected static function initHeader($route)
    {
        if ($route) {
            $header = new Header();
            $header->setVersion(1);
            $header->setRoute($route);
            $header->setSource(SourceEnum::CONTRACTOR);
            return $header;
        } else {
            return null;
        }
    }

    /**
     * @param String $route
     * @param $request
     * @return Message
     * @throws \Exception
     */
    public static function sendRequest($route, $request)
    {
        $timeStart = microtime(true);

        list($ip, $port) = self::getRoute($route);
        $client = self::getClient($ip, $port);
        $header = self::initHeader($route);
        try {
            $client->send(Message::pack($header, $request));
            $result = $client->recv();
        } catch (\Exception $e) {
            $timeEnd = microtime(true);
            $elapsed = $timeEnd - $timeStart;
            $code = $e->getCode() > 0 ? $e->getCode() : 999;
            Timeline::get()->report($header->getRoute(), 'sendRequest', Logger::SOURCE, $elapsed, $code, $header->getTraceId(), $header->getRequestId());
            Console::get()->logException($e);
            throw $e;
        }
        // swoole 1.8.1有bug,close之后此task也退出了. https://github.com/swoole/swoole-src/issues/522
        //$client->close();
        $message = new Message();
        $message->unpackResponse($result);
        $timeEnd = microtime(true);
        $elapsed = $timeEnd - $timeStart;
        if ($message->getHeader()->getCode() > 0) {
            $e = new \Exception($message->getHeader()->getMsg(), $message->getHeader()->getCode());
            Console::get()->logException($e);
            throw $e;
        }
        Timeline::get()->report($header->getRoute(), 'sendRequest', Logger::SOURCE, $elapsed, 0, $header->getTraceId(), $header->getRequestId());
        return $message;
    }


    /**
     * @param int $location
     * @param int $rule_id
     * @param int $wholesaler_id
     * Author Jason Y. wang
     *
     * @return Proxy|CouponReceiveListResponse
     */
    public static function getCouponReceiveList($location = 0, $rule_id = 0, $wholesaler_id = 0)
    {
        $request = new CouponReceiveListRequest();
        $data = [
            'location' => $location,
            'rule_id' => $rule_id,
            'wholesaler_id' => $wholesaler_id,
        ];

        $request->setFrom(Tools::pb_array_filter($data));
        $header = new Header();
        $header->setSource(SourceEnum::MERCHANT);
        $header->setRoute(self::ROUTE_SALES_COUPON_RECEIVE_LIST);
        $message = self::sendRequest($header, $request);
        $response = [];
        if ($message->getPackageBody()) {
            $response = new CouponReceiveListResponse();
            $response->parseFromString($message->getPackageBody());
        }

        return $response;
    }

    /**
     * @param int $rule_id
     * @param int $wholesaler_id
     * @return bool|SaleRuleResponse
     * @throws \Exception
     */
    public static function getSaleRule($rule_id = 0, $wholesaler_id = 0)
    {
        if (empty($rule_id) && empty($wholesaler_id)) {
            return false;
        }
        if (!is_array($rule_id)) {
            $rule_id = [$rule_id];
        }

        if (!is_array($wholesaler_id)) {
            $wholesaler_id = [$wholesaler_id];
        }

        $request = new SaleRuleRequest();
        $data = [
            'rule_id' => $rule_id,
            'wholesaler_id' => $wholesaler_id,
        ];

        $request->setFrom(Tools::pb_array_filter($data));

        $message = self::sendRequest(self::ROUTE_SALES_RULE, $request);
        if (!$message->getPackageBody()) {
            return false;
        }
        /** @var SaleRuleResponse $response */
        $response = new SaleRuleResponse();
        $response->parseFromString($message->getPackageBody());

        return $response;
    }

    /**
     * @param LeCustomers $customer
     * @param $wholesaler_id
     * @return integer
     * @throws \Exception
     */
    public static function getOrderCountToday($customer, $wholesaler_id)
    {
        $request = new OrderCollectionRequest();
        $request->setCustomerId($customer->entity_id);
        $request->setAuthToken($customer->auth_token);
        $request->setWholesalerId($wholesaler_id);

        $message = self::sendRequest('sales.orderCountToday', $request);
        $count = 0;
        if ($message->getPackageBody()) {
            /** @var OrderNumberResponse $response */
            $response = new OrderNumberResponse();
            $response->parseFromString($message->getPackageBody());
            $count = $response->getAll();
        }

        return $count;
    }

}
