<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\CustomerTagRelation;
use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LeCustomersIntention;
use common\models\LeVisitPlan;
use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\components\Tools;
use service\message\contractor\searchStoresRequest;
use service\message\contractor\StoresResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

use yii\db\Expression;
use yii\db\Query;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-7-25
 * Time: 上午11:43
 * Email: henryzxj1989@gmail.com
 */

/**
 * Class searchStores
 * @package service\resources\contractor\v1
 */
class searchStores extends Contractor
{
    const PAGE_SIZE = 10;
    public $responseData;

    public function run($data)
    {
        /** @var searchStoresRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);

        $type = $request->getType();
        switch ($type) {
            case 1:
                $this->getCustomer($contractor, $request);
                break;
            case 2:
                $this->getReviewCustomer($contractor, $request);
                break;
            case 3:
                $this->getCustomer($contractor, $request, true);
                break;
            default:
                $this->getCustomer($contractor, $request);
                break;
        }

        $response->setFrom(Tools::pb_array_filter($this->responseData));

        return $response;
    }


    /**
     * @param LeContractor $contractor
     * @param searchStoresRequest $request
     * @param boolean $registration
     */
    private function getCustomer($contractor, $request, $registration = false)
    {
        $city = $request->getCity();

        $city_list = array_filter(explode('|', $contractor->city_list));

        if (empty($city_list)) {
            ContractorException::contractorCityListEmpty();
        }

        if ($city) {
            $city_list = $city;
        }

        $customer_intention = LeCustomersIntention::find()->select(['entity_id', 'province', 'city',
            'district', 'area_id', 'address', 'detail_address', 'store_name', 'business_license_no', 'business_license_img', 'lat', 'lng',
            'phone', 'status', 'contractor_id', 'contractor', 'created_at', 'type', 'level', new Expression('1 as intention'), new Expression('0 AS last_visited_at,0 AS last_place_order_at,0 AS last_place_order_total,0 AS storekeeper, 0 AS group_id')])
            ->andWhere(['status' => 0])->andWhere(['tag_id' => 1]);
        $customer_user = LeCustomers::find()->select(['entity_id', 'province', 'city',
            'district', 'area_id', 'address', 'detail_address', 'store_name', 'business_license_no', 'business_license_img', 'lat', 'lng',
            'phone', 'status', 'contractor_id', 'contractor', 'created_at', 'type', 'level', new Expression('0 as intention'), 'last_visited_at', 'last_place_order_at', 'last_place_order_total', 'storekeeper', 'group_id'])
            ->andWhere(['status' => 1])->andWhere(['tag_id' => 1]);
        //城市经理显示全部店铺
        if ($contractor->role == self::COMMON_CONTRACTOR) {
            $customer_intention = $customer_intention->andWhere(['contractor_id' => $contractor->entity_id]);
            $customer_user = $customer_user->andWhere(['contractor_id' => $contractor->entity_id]);
        } else {
            $customer_intention = $customer_intention->andWhere(['city' => $city_list]);
            $customer_user = $customer_user->andWhere(['city' => $city_list]);
        }

        // 仅查询注册超市
        if ($registration) {
            $customer_user = $customer_user->andWhere(['status' => 1]);
        }
        $customers = (new Query())->from(['temp' => $customer_user->union($customer_intention, true)]);
        $customers = $customers->andWhere(['city' => $city_list]);

        $keyword = trim($request->getKeyword());
        $customers->andWhere(['or', ['like', 'store_name', "$keyword"], ['like', 'phone', "$keyword"],
            ['like', 'storekeeper', "$keyword"]]);

        // 未路线规划超市的筛选 超市路线规划模块调用
        if (intval($request->getGrouped()) > 0) {
            $customers->andWhere(['<=', 'group_id', 0]);
        }
        if ($request->getFilterContractorId() > 0 && $contractor->role != self::COMMON_CONTRACTOR) {
            $customers->andWhere(['contractor_id' => $request->getFilterContractorId()]);
        }

        // 业务员拜访计划 新增超市时候的筛选条件 排除掉已经存在于业务员拜访计划中的超市和临时拜访过的超市
        if ($request->getFilterContractorId() > 0 && $request->getVisitPlan() == 1 && $request->getDate()) {
            $filterContractorId = $request->getFilterContractorId();
            $date = $request->getDate();
            // 先查询 该业务员已有的拜访计划内的超市id
            $visitPlanCustomer = LeVisitPlan::find()->alias('v')->select(['v.customer_id', 'c.store_name'])->leftJoin(['c' => LeCustomers::tableName()], 'c.entity_id = v.customer_id')->where(['c.contractor_id' => $filterContractorId, 'v.date' => $date])->andWhere(['in', 'v.action', [0, 1]])->asArray()->all();
            $customerHas = [];
            if (!empty($visitPlanCustomer)) {
                foreach ($visitPlanCustomer as $kc => $vc) {
                    $customerHas[] = $vc['customer_id'];
                }
            }
            // 若是不在拜访计划内但是已经临时拜访过 则也不能在列表内
            $visitRecordElse = VisitRecords::find()->select('DISTINCT(customer_id) as customer_id')->where(['contractor_id' => $filterContractorId])->andWhere(['>=', 'created_at', $date . ' 00:00:00'])->orderBy('created_at desc')->all();
            if ($visitRecordElse) {
                foreach ($visitRecordElse as $ke => $ve) {
                    $customerHas[] = $ve['customer_id'];
                }
            }
            $customerIn = array_unique($customerHas);
            if ($filterContractorId > 0) {
                $customers->andWhere(['contractor_id' => $filterContractorId]);
            }
            $customers->andWhere(['not in', 'entity_id', $customerIn]);
        }

        $totalCount = $customers->count('*', LeCustomers::getDb());

        $paginationRequest = $request->getPagination();

        if ($paginationRequest) {
            $page = $paginationRequest->getPage() ?: 1;
            $pageSize = $paginationRequest->getPageSize() ?: self::PAGE_SIZE;
        } else {
            $page = 1;
            $pageSize = self::PAGE_SIZE;
        }

        $pagination = new Pagination(['totalCount' => $totalCount]);
        $pagination->setPageSize($pageSize);
        $pagination->setCurPage($page);
        $customers = $customers->offset($pagination->getOffset())
            ->limit(self::PAGE_SIZE)
            ->all(LeCustomers::getDb());

        $this->responseData = [
            'pagination' => Tools::getPagination($pagination),
            'stores' => [],
        ];
//        Tools::log($customers,'xiayy.log');
        /** @var LeCustomers $customer */
        foreach ($customers as $customer) {
            $customer = Tools::ObjToArr($customer);
            if ($contractor->role != self::COMMON_CONTRACTOR || $contractor->entity_id == $customer['contractor_id']) {
                $is_visit = 1;
            } else {
                $is_visit = 0;
            }
            // 增加的三个参数
            $classify_ids = array_keys(Tools::$classifyArray);
            $classify = '';
            if ($customer['intention'] == 0) {
                //超市聚合tag 注册超市才有
                /** @var CustomerTagRelation $classifyModel */
                $classifyModel = CustomerTagRelation::find()->where(['customer_id' => $customer['entity_id']])->andWhere(['tag_id' => $classify_ids])->one();
                if ($classifyModel) {
                    $classify = isset(Tools::$classifyArray[$classifyModel->tag_id]) ? Tools::$classifyArray[$classifyModel->tag_id] : [];
                }
            }

            $curTimestamp = ToolsAbstract::getDate()->timestamp();
            $last_visit_label = '最近拜访：未拜访';
            $last_ordered_label = '最近下单：未下单';
            /* 1970-01-01 00:00:01之前的都是未拜访/未下单！！！！！ */
            if (($lastVisitedAt = strtotime($customer['last_visited_at'])) && $lastVisitedAt > 0) {
                $last_visit_label = '最近拜访：'
                    . round(($curTimestamp - $lastVisitedAt) / 3600 / 24) . '天前';
            }
            if (($lastOrderAt = strtotime($customer['last_place_order_at'])) && $lastOrderAt > 0) {
                $last_ordered_label = '最近下单：'
                    . round(($curTimestamp - $lastOrderAt) / 3600 / 24)
                    . '天前，￥' . $customer['last_place_order_total'];
            }

            $this->responseData['stores'][] = [
                'store_name' => $customer['store_name'],
                'customer_id' => $customer['entity_id'],
                'customer_style' => $customer['intention'],
                'address' => $customer['address'],
                'detail_address' => $customer['detail_address'],
                'storekeeper' => $customer['storekeeper'],
                'phone' => $customer['phone'],
                'classify_tag' => $classify,
                'last_visit_label' => $last_visit_label,
                'last_ordered_label' => $last_ordered_label,
                'is_visit' => $is_visit,
            ];
        }
    }

    /**
     * @param LeContractor $contractor
     * @param searchStoresRequest $request
     */
    private function getReviewCustomer($contractor, $request)
    {
        $city = $request->getCity();

        $city_list = array_filter(explode('|', $contractor->city_list));

        if (empty($city_list)) {
            ContractorException::contractorCityListEmpty();
        }

        if ($city) {
            $city_list = $city;
        }

        $reviewCustomer = LeCustomers::find()->select(['entity_id', 'province', 'city',
            'district', 'area_id', 'address', 'detail_address', 'store_name', 'business_license_no', 'business_license_img', 'lat', 'lng',
            'phone', 'status', 'contractor_id', 'contractor', 'created_at', 'type', 'level', new Expression('0 as intention')])
            ->andWhere(['status' => 0])->andWhere(['tag_id' => 1]);

        $customers = $reviewCustomer->andWhere(['city' => $city_list]);

        $keyword = trim($request->getKeyword());
        $customers->andWhere(['or', ['like', 'store_name', "$keyword"], ['like', 'phone', "$keyword"], ['like', 'store_name', "storekeeper"]]);

        $totalCount = $customers->count('*', LeCustomers::getDb());

        $paginationRequest = $request->getPagination();

        if ($paginationRequest) {
            $page = $paginationRequest->getPage() ?: 1;
            $pageSize = $paginationRequest->getPageSize() ?: self::PAGE_SIZE;
        } else {
            $page = 1;
            $pageSize = self::PAGE_SIZE;
        }

        $pagination = new Pagination(['totalCount' => $totalCount]);
        $pagination->setPageSize($pageSize);
        $pagination->setCurPage($page);
        $customers = $customers->offset($pagination->getOffset())
            ->limit(self::PAGE_SIZE)
            ->all(LeCustomers::getDb());

        $this->responseData = [
            'pagination' => Tools::getPagination($pagination),
            'stores' => [],
        ];
        /** @var LeCustomers $customer */
        foreach ($customers as $customer) {
            $this->responseData['stores'][] = [
                'store_name' => $customer['store_name'],
                'customer_id' => $customer['entity_id'],
                'customer_style' => $customer['intention'],
                'address' => $customer['address'],
                'detail_address' => $customer['detail_address'],
            ];
        }
    }


    public static function request()
    {
        return new searchStoresRequest();
    }

    public static function response()
    {
        return new StoresResponse();
    }
}