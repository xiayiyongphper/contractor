<?php
/**
 * Created by PhpStorm.
 * Date: 2017/3/29
 * Time: 20:33
 */

namespace service\resources\contractor\v1;


use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTaskHistory;
use service\components\Tools;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\ManageResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class orderManage extends Contractor
{
    public function run($data)
    {
        /** @var ContractorAuthenticationRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor_id = $request->getContractorId();

        $contractor = $this->initContractor($request);

        $gmv = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::ID_VALID_GMV);
        $orderCount = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::ID_ORDER_COUNT);

        if (!$contractor) {
            ContractorException::contractorInitError();
        }

        $city = $request->getCity();
        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        if ($contractor->role == self::COMMON_CONTRACTOR) {
            $conditions = ['city' => $city, 'owner_id' => $contractor_id,];
        } else {
            $conditions = ['city' => $city, 'owner_id' => 0];
        }

        $toDay = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 Day'));
        $thisMonth = date('Y-m') . '-01';
        /** @var ContractorTaskHistory $statGmvToday */
        $statGmvToday = ContractorTaskHistory::find()->select('value sales_total')
            ->where($conditions + ['date' => $toDay])
            ->andWhere(['metric_id' => $gmv])
            ->one();

        $statGmvToday = $statGmvToday ? $statGmvToday->sales_total : 0;

        /** @var ContractorTaskHistory $statOrderCountToday */
        $statOrderCountToday = ContractorTaskHistory::find()->select('value orders_count')
            ->where($conditions + ['date' => $toDay])
            ->andWhere(['metric_id' => $orderCount])
            ->one();

        $statOrderCountToday = $statOrderCountToday ? (int)$statOrderCountToday->orders_count : 0;


        /** @var ContractorTaskHistory $statGmvYesterday */
        $statGmvYesterday = ContractorTaskHistory::find()->select('value sales_total')
            ->where($conditions + ['date' => $yesterday])
            ->andWhere(['metric_id' => $gmv])
            ->one();
        $statGmvYesterday = $statGmvYesterday ? $statGmvYesterday->sales_total : 0;

        /** @var ContractorTaskHistory $statOrderCountYesterday */
        $statOrderCountYesterday = ContractorTaskHistory::find()->select('value orders_count')
            ->where($conditions + ['date' => $yesterday])
            ->andWhere(['metric_id' => $orderCount])
            ->one();

        $statOrderCountYesterday = $statOrderCountYesterday ? (int)$statOrderCountYesterday->orders_count : 0;
        /** @var ContractorTaskHistory $statGmvThisMonth */
        $statGmvThisMonth = ContractorTaskHistory::find()->select('sum(value) sales_total')
            ->where(['and', $conditions, ['>=', 'date', $thisMonth]])
            ->andWhere(['metric_id' => $gmv])
            ->one();

        $statGmvThisMonth = $statGmvThisMonth ? $statGmvThisMonth->sales_total : 0;
        /** @var ContractorTaskHistory $statOrderCountMonth */
        $statOrderCountMonth = ContractorTaskHistory::find()->select('sum(value) orders_count')
            ->where(['and', $conditions, ['>=', 'date', $thisMonth]])
            ->andWhere(['metric_id' => $orderCount])
            ->one();
        $statOrderCountMonth = $statOrderCountMonth ? (int)$statOrderCountMonth->orders_count : 0;
//        Tools::log(ContractorStatisticsData::find()->select('sum(sales_total) sales_total, sum(orders_count) orders_count')->where(['and', $conditions, ['>=', 'date', $thisMonth]])->createCommand()->getRawSql(), 'jun.log');

        $responseData = [
            'stat_data' => [
                [
                    'title' => '今日',
                    'order_stat' => [
                        [
                            'name' => '今日销售额',
                            'value' => '¥' . $statGmvToday
                        ],
                        [
                            'name' => '今日订单数',
                            'value' => $statOrderCountToday
                        ],
                        [
                            'name' => '今日客单价',
                            'value' => $statOrderCountToday ? number_format($statGmvToday / $statOrderCountToday, 2) : 0
                        ]
                    ]
                ],
                [
                    'title' => '昨日',
                    'order_stat' => [
                        [
                            'name' => '昨日销售额',
                            'value' => '¥' . $statGmvYesterday
                        ],
                        [
                            'name' => '昨日订单数',
                            'value' => $statOrderCountYesterday
                        ],
                        [
                            'name' => '昨日客单价',
                            'value' => $statOrderCountYesterday ? (number_format($statGmvYesterday / $statOrderCountYesterday, 2)) : 0
                        ]
                    ]
                ],
                [
                    'title' => '本月',
                    'order_stat' => [
                        [
                            'name' => '月销售额',
                            'value' => $this->formatSalesTotal($statGmvThisMonth),
                        ],
                        [
                            'name' => '月订单数',
                            'value' => $statOrderCountMonth,
                        ],
                        [
                            'name' => '月客单价',
                            'value' => $statOrderCountMonth ? (number_format($statGmvThisMonth / $statOrderCountMonth, 2)) : 0
                        ]
                    ]
                ],
            ]
        ];
//        Tools::log($responseData, 'orderManage.log');
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    private function formatSalesTotal($sales)
    {
        if (intval($sales) > 10000) {
            $value = number_format((intval($sales) / 10000), 2) . '万';
        } else {
            $value = number_format($sales, 2);
        }
        return '¥' . $value;
    }

    public static function request()
    {
        return new ContractorAuthenticationRequest();
    }

    public static function response()
    {
        return new ManageResponse();
    }
}
