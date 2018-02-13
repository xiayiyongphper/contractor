<?php
/**
 * Created by Jason Y. wang
 * User: wangyang
 * Date: 16-7-21
 * Time: 下午6:02
 */

namespace service\resources\contractor\v1;


use common\components\UserTools;
use common\models\contractor\ContractorMarkPriceHistory;
use common\models\contractor\MarkPriceProduct;
use common\models\contractor\VisitRecords;
use common\models\LeContractor;
use common\models\LeCustomers;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\HomeResponse;
use service\models\common\Contractor;

class home extends Contractor
{
    public function run($data)
    {
        /** @var ContractorAuthenticationRequest $request */
        $request = self::parseRequest($data);
        $response = new HomeResponse();
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);
        $city_list = array_filter(explode('|',$contractor->city_list));
        //本月店铺统计
        $dayFrom = date('Y-m-01') . ' 00:00:00';
        $dayEnd = date('Y-m-d 23:59:59');

        //今日业绩  模块是否展示
        if (ContractorPermission::homeContractorStaticsPermission($this->role_permission)) {

            $orderData = UserTools::getContractorHomeDataByProxy($contractor);
            if ($orderData) {
                $response->setContractorStatics($orderData);
            }
        }

        //订单跟踪  模块是否展示
        if (ContractorPermission::homeContractorOrderTrackingPermission($this->role_permission)) {
            //订单跟踪
            $orderTrackingData = UserTools::getContractorOrderTrackingByProxy($contractor);
            $responseData['order_tracking'] = $orderTrackingData['order_tracking'];
        }

        //首页显示店铺列表
        if (ContractorPermission::homeContractorStorePermission($this->role_permission)) {
            if($contractor->role == self::COMMON_CONTRACTOR){
                $stores = LeCustomers::find()->where(['status' => 0, 'city' => $city_list])
                    ->andWhere(['contractor_id' => $contractor->entity_id])
                    ->orderBy('created_at desc')->limit(20)->all();
            }else{
                $stores = LeCustomers::find()->where(['status' => 0, 'city' => $city_list])
                    ->orderBy('created_at desc')->limit(20)->all();
            }

            if ($stores) {
                $storeInfoData = [];
                /** @var LeCustomers $store */
                foreach ($stores as $store) {
                    $storeInfo['customer_id'] = $store->entity_id;
                    $storeInfo['store_name'] = $store->store_name;
                    $storeInfo['created_at'] = $store->created_at;
                    $storeInfoData[] = $storeInfo;
                }
                $responseData['stores'] = $storeInfoData;
            }
        }

        //注册审核店铺汇总
        if (ContractorPermission::homeContractorRegisterAuditPermission($this->role_permission)) {

            //审核超市
            $waiting_review_count = LeCustomers::find()->where(['status' => 0])
                ->andWhere(['city' => $city_list])
                ->count();

            $already_review_count = LeCustomers::find()->where(['status' => 1])
                ->andWhere(['city' => $city_list])
                ->andWhere(['between', 'apply_at', $dayFrom, $dayEnd]);

            if($contractor->role == self::COMMON_CONTRACTOR){
                $already_review_count = $already_review_count->andWhere(['contractor_id' => $contractor->entity_id]);
            }else{
                $already_review_count = $already_review_count->andWhere(['city' => $city_list]);
            }

            $already_review_count = $already_review_count->count();
            $responseData['review_info'] = [
                [
                    'key' => '待审核超市',
                    'value' => $waiting_review_count,
                ],
                [
                    'key' => '本月已审核超市',
                    'value' => $already_review_count,
                ]
            ];

        }


        if (ContractorPermission::homeContractorStoreListPermission($this->role_permission)) {
            //注册超市
            $register_count_all = LeCustomers::find()->where(['city' => $city_list])
                ->andWhere(['status' => 1]);
            $register_count_month = LeCustomers::find()->where(['status' => 1])->andWhere(['between', 'created_at', $dayFrom, $dayEnd]);

            if($contractor->role == self::COMMON_CONTRACTOR){
                $register_count_all = $register_count_all->andWhere(['contractor_id' => $contractor->entity_id]);
                $register_count_month = $register_count_month->andWhere(['contractor_id' => $contractor->entity_id]);

            }else{
                $register_count_all = $register_count_all->andWhere(['city' => $city_list]);
                $register_count_month = $register_count_month->andWhere(['city' => $city_list]);
            }

            $register_count_all = $register_count_all->count();
            $register_count_month = $register_count_month->count();

            $responseData['customer_info'] = [
                [
                    'key' => '已注册超市数',
                    'value' => $register_count_all,
                ],
                [
                    'key' => '本月注册超市数',
                    'value' => $register_count_month,
                ]
            ];
        }

        if (ContractorPermission::homeContractorVisitRecordPermission($this->role_permission)) {

            $visited = VisitRecords::find()
                ->leftJoin(['c' => LeCustomers::tableName()], "c.entity_id=contractor_visit_records.customer_id")
                ->where(['between', 'visited_at', $dayFrom, $dayEnd]);
            $visitStore = VisitRecords::find()
                ->leftJoin(['c' => LeCustomers::tableName()], "c.entity_id=contractor_visit_records.customer_id")
                ->where(['between', 'visited_at', $dayFrom, $dayEnd])->groupBy('customer_id');

            if($contractor->role == self::COMMON_CONTRACTOR){
                $visited = $visited->andWhere(['contractor_visit_records.contractor_id' => $contractor->entity_id]);
                $visitStore = $visitStore->andWhere(['contractor_visit_records.contractor_id' => $contractor->entity_id]);
            }else{
                $visited = $visited->andWhere(['c.city' => $city_list]);
                $visitStore = $visitStore->andWhere(['c.city' => $city_list]);
            }

            //Tools::log($visitStore->createCommand()->getRawSql(),'wangyang.log');
            $visitedCount = $visited->count();
            $visitStoreCount = $visitStore->count();
            $responseData['visited'] = [
                [
                    'key' => '本月拜访次数',
                    'value' => $visitedCount,
                ],
                [
                    'key' => '本月拜访超市数',
                    'value' => $visitStoreCount,
                ]
            ];
        }


        if (ContractorPermission::homeContractorMarkPricedPermission($this->role_permission)) {
            $mark_price_products_count_all = MarkPriceProduct::find()
                ->where(['city' => $city_list, 'status' => 1])
                ->count();
            $marked_price_products_count_all = ContractorMarkPriceHistory::find()
                ->joinWith('product')
                ->where(['contractor_mark_price_product_list.city' => $city_list])
                ->andWhere(['status' => 1])
                ->groupBy('mark_price_product_id')->count();

            $responseData['mark_price_info'] = [
                [
                    'key' => '已录入价格商品数',
                    'value' => $marked_price_products_count_all,
                ],
                [
                    'key' => '全部商品数',
                    'value' => $mark_price_products_count_all,
                ]
            ];
        }

        $responseData['more_url'] = 'http://data-stats.lelai.com/site/login';

        $response->setFrom(Tools::pb_array_filter($responseData));

        return $response;
    }

    public static function request()
    {
        return new ContractorAuthenticationRequest();
    }

    public static function response()
    {
        return new HomeResponse();
    }

}