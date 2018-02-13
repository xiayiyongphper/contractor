<?php
/**
 * Created by Jason Y. wang
 * User: wangyang
 * Date: 16-7-21
 * Time: 下午5:29
 */

namespace service\resources\contractor\v1;


use framework\components\ToolsAbstract;
use framework\db\readonly\models\core\SalesFlatOrder;
use service\components\Tools;
use service\message\contractor\GetStoreInfoRequest;
use service\message\contractor\GetStoreStatisticalResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class getStoreStatistics extends Contractor
{
    public function run($data)
    {
        /** @var GetStoreInfoRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();

        Tools::log(microtime(true), 'xiayy.log');
        $customer_id = $request->getCustomerId();

        // GMV筛选项
        $responseData['gmv_option'] = [
            [
                'key' => '30',
                'value' => '近30天'
            ],
            [
                'key' => '60',
                'value' => '近60天'
            ],
            [
                'key' => '180',
                'value' => '近半年'
            ],
        ];

        // 若是为空 则不返回数据不刷新页面
        if ($request->getGmvDay()) {
            $getGmvDay = $request->getGmvDay();
            $GmvDayArr = [-1, 30, 60, 180];
            if (!in_array($getGmvDay, $GmvDayArr)) {
                throw new ContractorException('查看GMV天数' . $getGmvDay . '不在范围内', 401);
            }
            if ($getGmvDay == -1) {
                $getGmvDay = 30;
            }
            $gmv_date = ToolsAbstract::getDate()->date('Y-m-d', strtotime("-" . intval($getGmvDay) . " day"));// gmv筛选天数

            // 总GMV
            $sql_1 = <<<SQL
        SELECT SUM(`i`.`row_total`) AS all_amount FROM  `lelai_slim_core`.`sales_flat_order` AS `o` 
        LEFT JOIN `lelai_slim_core`.`sales_flat_order_item` AS `i` ON `i`.`order_id` = `o`.`entity_id` 
        WHERE `o`.`customer_id` = '$customer_id' 
        AND `o`.`status` in ('processing', 'processing_receive', 'processing_shipping', 'complete', 'pending_comment') 
        AND `o`.`state` not in ('canceled', 'closed') 
        AND `i`.`parent_id` = 0 
        AND `o`.`created_at` >= '$gmv_date' 
SQL;
            /** @var \yii\db\Connection $coreReadOnlyDb */
            $coreReadOnlyDb = \Yii::$app->coreReadOnlyDb;
            $queryData_1 = $coreReadOnlyDb->createCommand($sql_1)->queryOne();


            // 零食GMV
            $sql_2 = <<<SQL
        SELECT SUM(`i`.`row_total`) AS all_amount FROM  `lelai_slim_core`.`sales_flat_order` AS `o` 
        LEFT JOIN `lelai_slim_core`.`sales_flat_order_item` AS `i` ON `i`.`order_id` = `o`.`entity_id` 
        WHERE `o`.`customer_id` = '$customer_id' 
        AND ( `i`.`first_category_id` in (484,485,486,487,488,489,492,493,494) OR (`i`.`second_category_id` IN (513,514)) OR `i`.`first_category_id` IN (31,103,127,413,2,161,269,213) )
        AND `i`.`parent_id` = 0 
        AND `o`.`status` in ('processing', 'processing_receive', 'processing_shipping', 'complete', 'pending_comment') 
        AND `o`.`state` not in ('canceled', 'closed') AND `o`.`created_at` >= '$gmv_date' 
SQL;
            /** @var \yii\db\Connection $coreReadOnlyDb */
            $coreReadOnlyDb = \Yii::$app->coreReadOnlyDb;
            $queryData_2 = $coreReadOnlyDb->createCommand($sql_2)->queryOne();

            // 自营零食GMV
            $sql_3 = <<<SQL
        SELECT SUM(`i`.`row_total`) AS all_amount FROM  `lelai_slim_core`.`sales_flat_order` AS `o` 
        LEFT JOIN `lelai_slim_core`.`sales_flat_order_item` AS `i` ON `i`.`order_id` = `o`.`entity_id` 
        WHERE `o`.`customer_id` = '$customer_id' 
        AND `o`.`wholesaler_id` in  (SELECT `entity_id` from `lelai_slim_merchant`.`le_merchant_store` WHERE `store_type`=6) 
        AND ( `i`.`first_category_id` IN (484,485,486,487,488,489,492,493,494) OR (`i`.`second_category_id` in (513,514)) OR `i`.`first_category_id`IN (31,103,127,413,2,161,269,213) ) 
        AND `i`.`parent_id` = 0 
        AND `o`.`status` in ('processing', 'processing_receive', 'processing_shipping', 'complete', 'pending_comment') AND `o`.`state` not in ('canceled', 'closed') AND `o`.`created_at` >= '$gmv_date' 
SQL;
            /** @var \yii\db\Connection $coreReadOnlyDb */
            $coreReadOnlyDb = \Yii::$app->coreReadOnlyDb;
            $queryData_3 = $coreReadOnlyDb->createCommand($sql_3)->queryOne();

            // 上次订货信息
            /** @var  SalesFlatOrder $lastOrder */
            $lastOrder = SalesFlatOrder::find()->where(['customer_id' => $customer_id])
                ->andWhere(['in', 'status', SalesFlatOrder::VALID_ORDER_STATUS()])
                ->andWhere(['not in', 'state', SalesFlatOrder::INVALID_ORDER_STATE()])
                ->orderBy('entity_id desc')->one();

            $lastDay = 0;
            if ($lastOrder) {
                $today = ToolsAbstract::getDate()->gmtTimestamp(); //今天时间戳
                $lastDay = round(($today - strtotime($lastOrder->created_at)) / (3600 * 24));
            }

            // 组合数据
            $responseData['gmv_date'] = [
                [
                    'key' => '总GMV',
                    'value' => $queryData_1['all_amount'] ? '￥' . $queryData_1['all_amount'] : '￥0'
                ],
                [
                    'key' => '零食GMV',
                    'value' => $queryData_2['all_amount'] ? '￥' . $queryData_2['all_amount'] : '￥0'
                ],
                [
                    'key' => '自营零食GMV',
                    'value' => $queryData_3['all_amount'] ? '￥' . $queryData_3['all_amount'] : '￥0'
                ],
            ];

            // 上次订货
            $responseData['last_order'] = [
                [
                    'key' => '上次订货',
                    'value' => $lastDay . '天前'
                ],
                [
                    'key' => '上次订货金额',
                    'value' => $lastOrder ? '￥' . $lastOrder->grand_total : '￥0'
                ],
                [
                    'key' => '上次订货商品',
                    'value' => $lastOrder ? 'lelaibd://order/detail?order_id=' . $lastOrder->entity_id : '暂无'
                ],
            ];
        }

        // 自营零食订单筛选项
        $responseData['order_option'] = [
            [
                'key' => '15',
                'value' => '近15天'
            ],
            [
                'key' => '30',
                'value' => '近30天'
            ],
            [
                'key' => '60',
                'value' => '近60天'
            ],
        ];

        // 若是为空 则不返回数据不刷新页面
        if ($request->getOrderDay()) {
            $getOrderDay = $request->getOrderDay();
            $orderDayArr = [-1, 15, 30, 60];
            if (!in_array($getOrderDay, $orderDayArr)) {
                throw new ContractorException('查看订单天数' . $getOrderDay . '不在范围内', 401);
            }
            if ($getOrderDay == -1) {
                $getOrderDay = 15;
            }
            $order_date = ToolsAbstract::getDate()->date('Y-m-d', strtotime("-" . intval($getOrderDay) . " day"));// 自营零食订单筛选天数
            // 自营零食订单数
            $sql_order = <<<SQL
        SELECT SUM(`i`.`row_total`) AS all_amount,`o`.`created_at`,COUNT(DISTINCT(`o`.`entity_id`)) AS total_num FROM  `lelai_slim_core`.`sales_flat_order` AS `o` 
        LEFT JOIN `lelai_slim_core`.`sales_flat_order_item` AS `i` ON `i`.`order_id` = `o`.`entity_id` 
        WHERE `o`.`customer_id` = '$customer_id' 
        AND `o`.`wholesaler_id` in  (SELECT `entity_id` from `lelai_slim_merchant`.`le_merchant_store` WHERE `store_type`=6) 
        AND ( `i`.`first_category_id` IN (484,485,486,487,488,489,492,493,494) OR (`i`.`second_category_id` in (513,514)) OR `i`.`first_category_id`IN (31,103,127,413,2,161,269,213) ) 
        AND `i`.`parent_id` = 0 
        AND `o`.`status` in ('processing', 'processing_receive', 'processing_shipping', 'complete', 'pending_comment') AND `o`.`state` not in ('canceled', 'closed') AND `o`.`created_at` >= '$order_date' GROUP BY FROM_UNIXTIME(UNIX_TIMESTAMP(`o`.`created_at`),'%Y-%m-%d') ORDER BY `o`.`created_at` ASC 
SQL;
            /** @var \yii\db\Connection $coreReadOnlyDb */
            $coreReadOnlyDb = \Yii::$app->coreReadOnlyDb;
            $queryData_order = $coreReadOnlyDb->createCommand($sql_order)->queryAll();
            $dateArr = [];

            foreach ($queryData_order as $key => $item) {
                $date = ToolsAbstract::getDate()->date('Y-m-d', strtotime($item['created_at']));
                $dateArr[$date] = $item;
            }


            for ($i = 0; $i < $getOrderDay; $i++) {
                $date2 = ToolsAbstract::getDate()->date('Y-m-d', strtotime("+" . $i . " day", strtotime($order_date)));
                if (isset($dateArr[$date2])) {
                    $responseData['order_num'][] = ['key' => substr($date2, 5), 'value' => intval($dateArr[$date2]['total_num'])];
                    $responseData['order_amount'][] = ['key' => substr($date2, 5), 'value' => intval($dateArr[$date2]['all_amount'])];
                } else {
                    $responseData['order_num'][] = ['key' => substr($date2, 5), 'value' => '0'];
                    $responseData['order_amount'][] = ['key' => substr($date2, 5), 'value' => '0'];
                }
            }
        }
//        Tools::log(Tools::pb_array_filter($responseData), 'xiayy.log');
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new GetStoreInfoRequest();
    }

    public static function response()
    {
        return new GetStoreStatisticalResponse();
    }

}