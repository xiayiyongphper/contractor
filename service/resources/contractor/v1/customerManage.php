<?php
/**
 * Created by PhpStorm.
 * Date: 2017/3/29
 * Time: 20:33
 */

namespace service\resources\contractor\v1;


use common\models\contractor\ContractorTaskHistory;
use common\models\ContractorStatisticsData;
use service\components\Tools;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\ManageResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class customerManage extends Contractor
{
    public function run($data)
    {
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor_id = $request->getContractorId();

        $contractor = $this->initContractor($request);

        $city = $request->getCity();
        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 Day'));

        if ($contractor->role == self::COMMON_CONTRACTOR) {

            $first_users_today = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $today, 'owner_id' => $contractor_id, 'metric_id' => 3])->sum('value');

            $orders_count_today = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $today, 'owner_id' => $contractor_id, 'metric_id' => 5])->sum('value');

            $dau_today = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $today, 'owner_id' => $contractor_id, 'metric_id' => 6])->sum('value');

            $first_users_yesterday = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $yesterday, 'owner_id' => $contractor_id, 'metric_id' => 3])->sum('value');

            $orders_count_yesterday = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $yesterday, 'owner_id' => $contractor_id, 'metric_id' => 5])->sum('value');

            $dau_yesterday = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $yesterday, 'owner_id' => $contractor_id, 'metric_id' => 6])->sum('value');

        } else {
            $first_users_today = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $today, 'metric_id' => 3])->sum('value');

            $orders_count_today = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $today, 'metric_id' => 5])->sum('value');

            $dau_today = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $today, 'metric_id' => 6])->sum('value');

            $first_users_yesterday = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $yesterday, 'metric_id' => 3])->sum('value');

            $orders_count_yesterday = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $yesterday, 'metric_id' => 5])->sum('value');

            $dau_yesterday = ContractorTaskHistory::find()->where(['city' => $city,
                'date' => $yesterday, 'metric_id' => 6])->sum('value');
        }

        $responseData = [
            'stat_data' => [
                [
                    'title' => '今日',
                    'order_stat' => [
                        [
                            'name' => '今日首单',
                            'value' => $first_users_today
                        ],
                        [
                            'name' => '今日下单',
                            'value' => $orders_count_today
                        ],
                        [
                            'name' => 'DAU',
                            'value' => $dau_today
                        ],

                    ]
                ],
                [
                    'title' => '昨日',
                    'order_stat' => [
                        [
                            'name' => '今日首单',
                            'value' => $first_users_yesterday
                        ],
                        [
                            'name' => '今日下单',
                            'value' => $orders_count_yesterday
                        ],
                        [
                            'name' => 'DAU',
                            'value' => $dau_yesterday
                        ],
                    ]
                ],
            ]
        ];

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
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