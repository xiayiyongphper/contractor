<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use common\models\SecKillActivity;
use common\models\SeckillHelper;
use common\models\SpecialProduct;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\merchant\searchProductRequest;
use service\message\merchant\SecKillActivityResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class secKillProduct extends Contractor
{

    public function run($data)
    {
        /** @var searchProductRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
        $customer = LeCustomers::findOne(['entity_id' => $request->getCustomerId()]);

        if (!$customer || $customer->contractor_id != $contractor->entity_id) {
            ContractorException::customerNotMatch();
        }

        $activityList = SecKillActivity::getCityNearList($customer->city, SeckillHelper::IS_CACHE);
        if (!$activityList)
            return null;

        $seckillHelper = new SeckillHelper($customer);
        $activities = [];
        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        $curDateTime = date('Y-m-d H:i:s', $curTimestamp);

        foreach ($activityList as $k => $activity) {
            list($pages, $products) = $seckillHelper->getProducts($activity['entity_id']);
            if (!$products) {
                continue;
            }

            if ($activity['start_time'] <= $curDateTime && $activity['end_time'] >= $curDateTime) {
                $status = 2;
                $statusStr = '已开抢';
                $leftToEnd = strtotime($activity['end_time']) - $curTimestamp;
            }else{
                continue;
            }

            /* 库存状态 */
            $productIds = array_keys($products);
            $productStocks = ToolsAbstract::getSecKillProductsStocks($activity['entity_id'], $productIds);

            /* 增加秒杀相关状态和倒计时 */
            $formatProducts = [];
            foreach ($products as $productId => $product) {
                if ($activity['status'] == SecKillActivity::INT_STATUS_END) {
                    $product['seckill_status'] = SpecialProduct::STATUS_END;
                } elseif ($activity['status'] == SecKillActivity::INT_STATUS_PREPARED) {
                    $product['seckill_status'] = SpecialProduct::STATUS_PREPARED;
                } else {
                    if (!empty($productStocks[$productId])) {
                        $product['seckill_status'] = SpecialProduct::STATUS_STARTED_HAS_STOCK;
                    } else {
                        $product['seckill_status'] = SpecialProduct::STATUS_STARTED_NO_STOCK;
                    }
                }
                $product['seckill_status_str'] = SpecialProduct::getStatusStr($product['seckill_status']);
                $formatProducts[] = $product;
            }

            $this->addToActArray($activities, $activity, $status, $statusStr, $leftToEnd, $formatProducts);
        }

        $rulesText = ToolsAbstract::getRedis()->get('sk_rules_text');
        $rulesText = $rulesText === false ? '' : $rulesText;
        $respData['rules_text'] = $rulesText; // rules_text活动规则文本，如果设置了rules_url，忽略本字段。
        $respData['rules_url'] = '';
        $respData['activities'] = $activities;

        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($respData));
        return $response;
    }

    /**
     * @param array $arr
     * @param array $activity
     */
    private function addToActArray(&$arr, $activity, $status, $statusStr, $leftToEnd, $product)
    {
        array_push($arr, [
            'id' => $activity['entity_id'],
            'time' => substr($activity['start_time'], 5, 11),
            'end_time' => substr($activity['end_time'], 5, 11),
            'status' => $status,
            'status_str' => $statusStr,
            'left_to_end' => $leftToEnd,
            'products' => $product,
        ]);
    }

    public static function request()
    {
        return new searchProductRequest();
    }

    /**
     * @return SecKillActivityResponse
     */
    public static function response()
    {
        return new SecKillActivityResponse();
    }
}