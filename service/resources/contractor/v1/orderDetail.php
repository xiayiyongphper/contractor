<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/25
 * Time: 11:31
 */

namespace service\resources\contractor\v1;


use common\models\driver\Order;
use framework\components\ProxyAbstract;
use framework\components\ToolsAbstract;
use service\components\ContractorPermission;
use service\message\common\Header;
use service\message\contractor\ContractorOrderDetailRequest;
use service\message\driver\DriverOrder;
use service\message\sales\DriverOrderDetailRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use service\models\common\CustomerException;

class orderDetail extends Contractor
{
    /**
     * Function: run
     * Author: Jason Y. Wang
     *
     * @param $data
     * @return mixed
     * @throws CustomerException
     */
    public function run($data)
    {
        /** @var ContractorOrderDetailRequest $request */
        $request = self::parseRequest($data);

        $contractor = $this->initContractor($request);

        if(!ContractorPermission::contractorOrderDetailPermission($this->role_permission)){
            ContractorException::contractorPermissionError();
        }

        $condition = [];


        if ($request->getOrderId() > 0) {
            $condition['order_id'] = $request->getOrderId();
        }

        if ($request->getIncrementId()) {
            $condition['increment_id'] = $request->getIncrementId();
        }

        if (count($condition) == 0) {
            ContractorException::orderNotExist();
        }

        /** @var Order $driverOrder */
        $driverOrder = Order::findOne($condition);
        $orderDetailRequest = new DriverOrderDetailRequest();
        if (!$driverOrder) {
            $driverOrder = new Order();
            $orderDetailRequest->setOrderId($request->getOrderId());
            $orderDetailRequest->setIncrementId($request->getIncrementId());
        } else {
            $orderDetailRequest->setOrderId($driverOrder->order_id);
            $orderDetailRequest->setIncrementId($driverOrder->increment_id);
        }

        $header = new Header();
        $header->setRoute('sales.contractorOrderDetail');
        $header->setSource($this->getSource());
        $header->setAppVersion($this->getAppVersion());
        $message = ProxyAbstract::sendRequest($header, $orderDetailRequest);
        $order = new \service\message\common\Order();
        $order->parseFromString($message->getPackageBody());
        $date = ToolsAbstract::getDate();
        $response = self::response();
        $data = [
            'driver_order_id' => $driverOrder->entity_id,
            'driver_id' => $driverOrder->driver_id,
            'driver_name' => $driverOrder->driver_name,
            'driver_phone' => $driverOrder->driver_phone,
            'state' => $driverOrder->state,
            'status' => $driverOrder->status ? $driverOrder->status : Order::STATUS_PENDING,
            'status_label' => $driverOrder->getStatusLabel() ? $driverOrder->getStatusLabel() : '待送货',
            'created_at' => $date->date('Y-m-d H:i:s', $driverOrder->created_at),
            'delivery_time' => $driverOrder->delivery_time,
            'completed_at' => $date->date('Y-m-d H:i:s', $driverOrder->completed_at),
            'lat' => $driverOrder->lat,
            'lng' => $driverOrder->lng,
            'wholesaler_id' => $driverOrder->wholesaler_id,
            'wholesaler_name' => $driverOrder->wholesaler_name,
            'customer_id' => $driverOrder->customer_id,
            'customer_name' => $driverOrder->customer_name,
            'customer_phone' => $driverOrder->customer_phone,
            'increment_id' => $driverOrder->increment_id,
            'order_id' => $driverOrder->order_id,
        ];
        $response->setFrom(ToolsAbstract::pb_array_filter($data));
        $response->setOrder($order);
        return $response;
    }

    public static function request()
    {
        return new ContractorOrderDetailRequest();
    }

    public static function response()
    {
        return new DriverOrder();
    }

}