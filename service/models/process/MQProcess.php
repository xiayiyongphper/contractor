<?php

namespace service\models\process;

use framework\components\es\Console;
use framework\components\ToolsAbstract;
use framework\core\ProcessInterface;
use framework\core\SWServer;
use framework\mq\MQAbstract;
use PhpAmqpLib\Message\AMQPMessage;
use service\components\Tools;
use service\models\contractor\Observer;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-6-2
 * Time: 上午11:12
 */

/**
 * Class MQProcess
 * @package service\models\process
 */
class MQProcess implements ProcessInterface
{
    public function run(SWServer $SWServer, \swoole_process $process)
    {
        try {
            ToolsAbstract::getMQ(true)->consume(function ($msg) {
                /** @var  AMQPMessage $msg */
                Console::get()->log($msg->body, null, [__METHOD__]);
                $body = json_decode($msg->body, true);
                Tools::log($body, 'onMQProcess.log');
                $tags = [];
                $key = ToolsAbstract::arrayGetString($body, 'key');
                switch ($key) {
                    case MQAbstract::MSG_ORDER_NEW:
                        $tags[] = MQAbstract::MSG_ORDER_NEW;
                        Observer::updateContractorStatisticsHistory($body['value']['order']);
                        Observer::updateTaskHistory($body['value']['order']);
                        Observer::updateGmvStat($body['value']['order'], MQAbstract::MSG_ORDER_NEW);
                        Observer::updateOrderCountStatisticsFromMQ($body['value']['order'], MQAbstract::MSG_ORDER_NEW);
                        break;
                    case MQAbstract::MSG_ORDER_CANCEL:
                        //订单取消
                        Observer::updateTaskHistory($body['value']['order']);
                        Observer::updateGmvStat($body['value']['order'], MQAbstract::MSG_ORDER_CANCEL);
                        Observer::updateOrderCountStatisticsFromMQ($body['value']['order'], MQAbstract::MSG_ORDER_CANCEL);
                        break;
                    case MQAbstract::MSG_ORDER_CLOSED:
                        //供货商拒单事件
                        Observer::updateTaskHistory($body['value']['order']);
                        Observer::updateGmvStat($body['value']['order'], MQAbstract::MSG_ORDER_CLOSED);
                        Observer::updateOrderCountStatisticsFromMQ($body['value']['order'], MQAbstract::MSG_ORDER_CLOSED);
                        break;
                    case MQAbstract::MSG_ORDER_AGREE_CANCEL:
                        //同意订单取消事件
                        Observer::updateTaskHistory($body['value']['order']);
                        Observer::updateGmvStat($body['value']['order'], MQAbstract::MSG_ORDER_AGREE_CANCEL);
                        Observer::updateOrderCountStatisticsFromMQ($body['value']['order'], MQAbstract::MSG_ORDER_AGREE_CANCEL);
                        break;
                    case MQAbstract::MSG_ORDER_REJECTED_CLOSED:
                        //用户拒单
                        Observer::updateTaskHistory($body['value']['order']);
                        Observer::updateGmvStat($body['value']['order'], MQAbstract::MSG_ORDER_REJECTED_CLOSED);
                        Observer::updateOrderCountStatisticsFromMQ($body['value']['order'], MQAbstract::MSG_ORDER_REJECTED_CLOSED);
                        break;
                    default:
                        $tags[] = MQAbstract::MSG_INVALID_KEY;
                        break;
                }
                Console::get()->log($msg, null, $tags);
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            });
        } catch (\Exception $e) {
            ToolsAbstract::logException($e);
        } catch (\Error $e) {
            ToolsAbstract::logException($e);
        }
    }
}