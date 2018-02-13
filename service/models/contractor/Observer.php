<?php

namespace service\models\contractor;

use common\components\UserTools;
use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTaskHistory;
use common\models\ContractorStatisticsData;
use framework\components\Date;
use framework\components\ToolsAbstract;
use framework\db\readonly\models\core\SalesFlatOrder;
use framework\mq\MQAbstract;
use service\components\Tools;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/29
 * Time: 17:23
 */
class Observer
{
    const ACTION_ORDER_CREATE = 1;
    const ACTION_ORDER_CANCEL = 2;

    //订单状态
    const STATUS_COMPLETE = 'complete';//完成
    const STATUS_CLOSED = 'closed';//关闭
    const STATUS_REJECTED_CLOSED = 'rejected_closed';//关闭
    const STATUS_CANCELED = 'canceled';//已取消
    const STATUS_HOLDED = 'holded';//挂起状态
    const STATUS_PENDING = 'pending';//新订单
    const STATUS_PROCESSING = 'processing';//待商家确认
    const STATUS_PROCESSING_RECEIVE = 'processing_receive';//商家已接单
    const STATUS_PROCESSING_SHIPPING = 'processing_shipping';//商家已发货
    const STATUS_PENDING_COMMENT = 'pending_comment';//待评论

    private static function valid_order_state()
    {
        return [
            self::STATUS_PROCESSING,
            self::STATUS_PROCESSING_RECEIVE,
            self::STATUS_PROCESSING_SHIPPING,
            self::STATUS_COMPLETE,
            self::STATUS_PENDING_COMMENT,
        ];
    }

    public static function updateContractorStatisticsHistory($body)
    {
        //只能普通超市在普通供应商下的单才会被统计
        $customer_tag_id = isset($body['customer_tag_id']) ? $body['customer_tag_id'] : 1;
        $merchant_type_id = isset($body['merchant_type_id']) ? $body['merchant_type_id'] : 1;
        if ($customer_tag_id == 1 && $merchant_type_id == 1) {
            $grand_total = $body['grand_total'];
            $first_order = $body['is_first_order'];
            $city = $body['city'];
            $contractor_id = $body['contractor_id'];
            //utc加8个小时，转成prc时间
            $date = date('Y-m-d', strtotime('+8 hours', strtotime($body['created_at'])));
            /** @var ContractorStatisticsData $contractor_statistics_data */
            $contractor_statistics_data = ContractorStatisticsData::find()
                ->where(['city' => $city, 'date' => $date, 'contractor_id' => $contractor_id])->one();
            if (!$contractor_statistics_data) {
                $contractor_statistics_data = new ContractorStatisticsData();
                $contractor_statistics_data->city = $city;
                $contractor_statistics_data->date = $date;
                $contractor_statistics_data->contractor_id = $contractor_id;
            }
            $contractor_statistics_data->sales_total += $grand_total;
            if ($first_order == 1) {
                $contractor_statistics_data->first_users += 1;
            }

            $contractor_statistics_data->orders_count += 1;
            $contractor_statistics_data->save();
            Tools::log($contractor_statistics_data->errors, 'onMQProcess.log');
        }
    }

    public static function updateGmvStat($data, $action)
    {
        $gmv_metric_id = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::METRIC_IDENTIFIER_MONTH_ORDER_GMV);
        $contractor_id = $data['contractor_id'];
        $city = $data['city'];
        $subtotal = $data['subtotal'];
        $date_obj = new Date();
        $date = $date_obj->date("Y-m-d", $data['created_at']);//创建订单的日期
        Tools::log('gmv', 'onMQProcess.log');
        Tools::log($subtotal, 'onMQProcess.log');
        Tools::log($city, 'onMQProcess.log');
        Tools::log('gmv', 'onMQProcess.log');
        switch ($action) {
            case  MQAbstract::MSG_ORDER_NEW:
                self::updateTaskHistoryValue($date, $gmv_metric_id, $contractor_id, $city, $subtotal);
                self::updateTaskHistoryValue($date, $gmv_metric_id, 0, $city, $subtotal);
                break;
            case MQAbstract::MSG_ORDER_CANCEL:
            case MQAbstract::MSG_ORDER_CLOSED:
            case MQAbstract::MSG_ORDER_AGREE_CANCEL:
            case MQAbstract::MSG_ORDER_REJECTED_CLOSED:
                self::updateTaskHistoryValue($date, $gmv_metric_id, $contractor_id, $city, -$subtotal);
                self::updateTaskHistoryValue($date, $gmv_metric_id, 0, $city, -$subtotal);
                break;
            default:
                break;
        }
    }

    public static function updateOrderCountStatisticsFromMQ($data, $action)
    {
        try {
            ToolsAbstract::log('updateOrderCountStatisticsFromMQ-start', 'onMQProcess.log');
            $metric_id = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::ID_ORDER_COUNT);
            $contractor_id = $data['contractor_id'];
            $orderId = $data['entity_id'];
            $incrementId = $data['increment_id'];
            $city = $data['city'];
            $date_obj = new Date();
            $date = $date_obj->date("Y-m-d", $data['created_at']);//创建订单的日期
            ToolsAbstract::log($orderId, 'onMQProcess.log');
            ToolsAbstract::log($incrementId, 'onMQProcess.log');
            ToolsAbstract::log($metric_id, 'onMQProcess.log');
            ToolsAbstract::log($contractor_id, 'onMQProcess.log');
            ToolsAbstract::log($city, 'onMQProcess.log');
            ToolsAbstract::log($date, 'onMQProcess.log');
            ToolsAbstract::log($action, 'onMQProcess.log');
            switch ($action) {
                case  MQAbstract::MSG_ORDER_NEW:
                    self::updateTaskHistoryValue($date, $metric_id, $contractor_id, $city, 1);
                    self::updateTaskHistoryValue($date, $metric_id, 0, $city, 1);
                    break;
                case MQAbstract::MSG_ORDER_CANCEL:
                case MQAbstract::MSG_ORDER_CLOSED:
                case MQAbstract::MSG_ORDER_AGREE_CANCEL:
                case MQAbstract::MSG_ORDER_REJECTED_CLOSED:
                    self::updateTaskHistoryValue($date, $metric_id, $contractor_id, $city, -1);
                    self::updateTaskHistoryValue($date, $metric_id, 0, $city, -1);
                    break;
                default:
                    break;
            }
            ToolsAbstract::log('updateOrderCountStatisticsFromMQ-finish', 'onMQProcess.log');
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
            ToolsAbstract::log('updateOrderCountStatisticsFromMQ-exception', 'onMQProcess.log');
        }
    }

    public static function updateTaskHistory($data)
    {
        $customer_id = $data['customer_id'];
        $wholesaler_id = $data['wholesaler_id'];
        $order_id = $data['entity_id'];
        $contractor_id = $data['contractor_id'];
        $city = $data['city'];
        $status = $data['state'];
        //$exclude_customer = [1021,1206,1208,1215,1245,2299,2376,2476,1942,1650,2541];//要排除的用户id
        //$exclude_customer = [0];
        $exclude_customer = SalesFlatOrder::excludeCustomerIds();
        $exclude_wholesaler = SalesFlatOrder::excludeWholesalerIds();

        if (isset($data['customer_tag_id']) && $data['customer_tag_id'] != 1) {
            return;
        }
        if (!$customer_id || in_array($customer_id, $exclude_customer)) {
            return;
        }
        if (in_array($wholesaler_id, $exclude_wholesaler)) {
            return;
        }

        $action = in_array($status, self::valid_order_state()) ? self::ACTION_ORDER_CREATE : self::ACTION_ORDER_CANCEL;
        $date_obj = new Date();
        $date = $date_obj->date("Y-m-d", $data['created_at']);//创建订单的日期

        //---------------------更新首单用户数---------------------------
        /*@var Order $first_order*/
        $first_order = UserTools::getCustomerFirstOrderByProxy($customer_id);
        $first_order_metric_id = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::METRIC_IDENTIFIER_FIRST_ORDER_CUSTOMER_COUNT);//任务维度id
        //创建订单
        if ($action == self::ACTION_ORDER_CREATE && $first_order->getOrderId() == $order_id) {//此订单是该用户首单，更新统计数据
            //更新业务员任务记录
            if ($contractor_id) {
                self::updateTaskHistoryValue($date, $first_order_metric_id, $contractor_id, $city, 1);

            }
            //更新城市任务记录
            self::updateTaskHistoryValue($date, $first_order_metric_id, 0, $city, 1);

        }

        //取消订单
        if ($action == self::ACTION_ORDER_CANCEL) {
            if (empty($first_order->getOrderId())) {//没有首单，那么这个订单原来是首单，而且用户只有这一个订单
                //订单创建日，业务员和城市的任务记录要减1
                if ($contractor_id) {
                    self::updateTaskHistoryValue($date, $first_order_metric_id, $contractor_id, $city, -1);
                }
                self::updateTaskHistoryValue($date, $first_order_metric_id, 0, $city, -1);
            } elseif (strtotime($first_order->getCreatedAt()) > $date_obj->timestamp($data['created_at'])) {
                //如果存在首单，且首单的创建时间晚于当前订单，说明当前订单原来是首单
                $first_order_date = substr($first_order->getCreatedAt(), 0, 10);//首单的下单日期

                //订单创建日，业务员和城市的任务记录要减1
                if ($contractor_id) {
                    self::updateTaskHistoryValue($date, $first_order_metric_id, $contractor_id, $city, -1);
                }
                self::updateTaskHistoryValue($date, $first_order_metric_id, 0, $city, -1);

                //新首单的创建日，业务员和城市的任务记录要加1
                if ($contractor_id) {
                    self::updateTaskHistoryValue($first_order_date, $first_order_metric_id, $contractor_id, $city, 1);
                }
                self::updateTaskHistoryValue($first_order_date, $first_order_metric_id, 0, $city, 1);
            }
        }

        //-------------------------更新月下单用户数------------------------------
        $start_date = $date_obj->date("Y-m-01", $data['created_at']);//下单月份1号
        $end_date = date("Y-m-d", strtotime("+1 month", strtotime($start_date)) - 1);//下单月份最后一天

        $metric_id = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::METRIC_IDENTIFIER_MONTH_ORDER_CUSTOMER_COUNT);//任务维度id

        //更新业务员任务记录
        if ($contractor_id) {
            //获取该用户在该业务员处的本月首单
            /*@var Order $month_first_order*/
            $month_first_order = UserTools::getCustomerFirstOrderByProxy($customer_id, $start_date, $end_date, $contractor_id, $city);

            if ($action == Observer::ACTION_ORDER_CREATE && $month_first_order->getOrderId() == $order_id) {
                //此订单是该用户在该业务员处本月首单，更新统计数据
                self::updateTaskHistoryValue($date, $metric_id, $contractor_id, $city, 1);
            }

            if ($action == Observer::ACTION_ORDER_CANCEL) {
                if (empty($month_first_order->getOrderId())) {//没有新首单，那么这个订单原来是首单，而且用户只有这一个订单
                    //订单创建日，业务员任务减1
                    self::updateTaskHistoryValue($date, $metric_id, $contractor_id, $city, -1);
                } elseif (strtotime($month_first_order->getCreatedAt()) > $date_obj->timestamp($data['created_at'])) {
                    //新首单的创建时间晚于当前订单，说明当前订单原来是首单
                    self::updateTaskHistoryValue($date, $metric_id, $contractor_id, $city, -1);
                    $first_order_date = substr($month_first_order->getCreatedAt(), 0, 10);//新首单的下单日期
                    self::updateTaskHistoryValue($first_order_date, $metric_id, $contractor_id, $city, 1);
                }
            }
        }

        //更新城市任务记录
        /*@var Order $first_order*/
        $month_first_order = UserTools::getCustomerFirstOrderByProxy($customer_id, $start_date, $end_date, null, $city);//获取该用户在该城市的本月首单
        if ($action == Observer::ACTION_ORDER_CREATE && $month_first_order->getOrderId() == $order_id) {
            //此订单是该用户在该城市本月首单，更新统计数据
            self::updateTaskHistoryValue($date, $metric_id, 0, $city, 1);
        }

        if ($action == Observer::ACTION_ORDER_CANCEL) {
            if (empty($month_first_order->getOrderId())) {//没有新首单，那么这个订单原来是首单，而且用户只有这一个订单
                //订单创建日，业务员任务减1
                self::updateTaskHistoryValue($date, $metric_id, 0, $city, -1);
            } elseif (strtotime($month_first_order->getCreatedAt()) > $date_obj->timestamp($data['created_at'])) {
                //新首单的创建时间晚于当前订单，说明当前订单原来是首单
                self::updateTaskHistoryValue($date, $metric_id, 0, $city, -1);
                $first_order_date = substr($month_first_order->getCreatedAt(), 0, 10);//新首单的下单日期
                self::updateTaskHistoryValue($first_order_date, $metric_id, 0, $city, 1);
            }
        }


        Tools::log("------------update end----------------", 'observer.log');
    }

    /**
     * 改变一条任务记录的值
     * @param $date //日期
     * @param $metric_id //维度id
     * @param $owner_id //业务员id
     * @param $city //城市
     * @param $added_value //增加的值，如果是减少则为负数
     * @return boolean
     */
    private static function updateTaskHistoryValue($date, $metric_id, $owner_id, $city, $added_value)
    {
        $date_obj = new Date();
        $task_history = ContractorTaskHistory::findOne([
            'date' => $date,
            'metric_id' => $metric_id,
            'owner_id' => $owner_id,
            'city' => $city
        ]);

        if ($task_history) {
            //Tools::log($task_history,'observer.log');
            Tools::log($added_value, 'observer.log');
            $task_history->value = floatval($task_history->value) + $added_value;
            $task_history->updated_at = $date_obj->date();
            $task_history->save();
            if (!empty($task_history->getErrors())) {
                Tools::log($task_history->getErrors(), 'exception.log');
                return false;
            }
        }

        return true;
    }

}