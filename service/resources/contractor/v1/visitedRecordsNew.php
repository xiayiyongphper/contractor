<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\ContractorVisitWholesaler;
use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LeCustomersIntention;
use common\models\LeMerchantStore;
use framework\data\Pagination;
use service\components\Tools;
use service\message\contractor\visitedRecordsRequestNew;
use service\message\contractor\visitedRecordsResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use service\components\ContractorPermission;

/**
 * Created by PhpStorm.
 * User: hongliang
 * Date: 17-3-29
 * Time: 上午11:43
 * 各个版本通用
 */

/**
 * Class visitedRecords
 * 拜访记录列表
 * @package service\resources\contractor\v1
 */
class visitedRecordsNew extends Contractor
{
    const PAGE_SIZE = 30;

    public function run($data)
    {
        /** @var visitedRecordsRequestNew $request */
        $request = self::parseRequest($data);

        $contractor_id = $request->getContractorId();
        $response = self::response();
        $contractor = $this->initContractor($request);
        $city = $request->getCity();
        $customer_id = $request->getCustomerId();
        if ($contractor->role != self::COMMON_CONTRACTOR && empty($customer_id) && $city <= 0) {
            //如果不是普通业务员，且不是查询单个超市的拜访记录，必须选择城市
            ContractorException::contractorCityEmpty();
        }
        if (!ContractorPermission::contractorVisitStoreListCreatePermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }

        // 根据参数 判断是查询的供货商wholesaler的拜访记录还是超市customer的拜访记录
        if ($request->getRole() == 0) {
            $records = VisitRecords::find();
            $tableName = 'contractor_visit_records';
        } else {
            $records = ContractorVisitWholesaler::find();
            $tableName = 'contractor_visit_wholesaler';
        }

        $chosen_contractor_ids = $request->getChosenContractorId();
        //剔除$chosen_contractor_ids中id为0的项
        $chosen_contractor_ids = array_filter($chosen_contractor_ids, function ($v) {
            return !empty($v);
        });


        if ($request->getRole() == 0) {
            if ($customer_id > 0) {
                //查看单个超市的拜访记录
                $whereArray = [$tableName . '.customer_id' => $customer_id];
            } else {
                if ($contractor->role == self::COMMON_CONTRACTOR) {
                    //业务员只查看属于自己的超市的拜访记录
                    list($customerIds, $customerIntentionIds) = LeContractor::getVisitedCustomerIds($contractor_id);
                    $whereArray = [
                        'or',
                        ['and', ['in', $tableName . '.customer_id', $customerIds], ['is_intended' => 0]],
                        ['and', ['in', $tableName . '.customer_id', $customerIntentionIds], ['is_intended' => 1]],
                    ];
                    //$records->where($whereArray);
                } elseif (!empty($chosen_contractor_ids)) {
                    list($customerIds, $customerIntentionIds) = LeContractor::getBatchVisitedCustomerIds($chosen_contractor_ids);
                    $whereArray = [
                        'or',
                        ['and', ['in', $tableName . '.customer_id', $customerIds], ['is_intended' => 0]],
                        ['and', ['in', $tableName . '.customer_id', $customerIntentionIds], ['is_intended' => 1]],
                    ];
                    //$records->where($whereArray);
                } else {
                    //$contractor_ids = LeContractor::find()->where(['city' => $city])->column();
                    $customerIds = LeCustomers::find()->where(['city' => $city])->column();
                    $customerIntentionIds = LeCustomersIntention::find()->where(['city' => $city])->column();
                    $whereArray = [
                        'or',
                        ['and', ['in', $tableName . '.customer_id', $customerIds], ['is_intended' => 0]],
                        ['and', ['in', $tableName . '.customer_id', $customerIntentionIds], ['is_intended' => 1]],
                    ];
                }
            }
            $records->where($whereArray);
        } else {
            $customerIds = LeMerchantStore::find()->where(['city' => $city])->column();
            if (!empty($customerIds)) {
                $records->andWhere(['in', $tableName . '.customer_id', $customerIds]);
            }
            if ($customer_id > 0) {
                $records->andWhere([$tableName . '.customer_id' => $customer_id]);
            }

            // 普通业务员只能查看自己的拜访记录
            if ($contractor->role == self::COMMON_CONTRACTOR) {
                $records->andWhere([$tableName . '.contractor_id' => $contractor->entity_id]);
            } else {
                if (!empty($request->getChosenContractorId())) {
                    $records->andWhere(['in', $tableName . '.contractor_id', $request->getChosenContractorId()]);
                }
            }
        }


        $visitPurposeArr = $request->getVisitPurpose();
        //剔除空值
        $visitPurposeArr = array_filter($visitPurposeArr, function ($v) {
            return !empty($v);
        });
        if (!empty($visitPurposeArr)) {
            $records->andWhere([$tableName . '.visit_purpose' => $visitPurposeArr]);
        }

        $visitWayArr = $request->getVisitWay();
        //剔除空值
        $visitWayArr = array_filter($visitWayArr, function ($v) {
            return !empty($v);
        });
        if (!empty($visitWayArr)) {
            $records->andWhere([$tableName . '.visit_way' => $visitWayArr]);
        }
        if (!empty($request->getVisitDateStart())) {
            $records->andWhere(['>', $tableName . '.visited_at', $request->getVisitDateStart() . " 00:00:00"]);
        }
        if (!empty($request->getVisitDateEnd())) {
            $records->andWhere(['<=', $tableName . '.visited_at', $request->getVisitDateEnd() . " 23:59:59"]);
        }

        // 拜访时间筛选
        if (!empty($request->getVisitTimeStart())) {
            $records->andWhere(['>=', 'ceil((UNIX_TIMESTAMP(' . $tableName . '.leave_time) - UNIX_TIMESTAMP(' . $tableName . '.arrival_time))/60)', $request->getVisitTimeStart()]);
        }
        if (!empty($request->getVisitTimeEnd())) {
            $records->andWhere(['<=', 'ceil((UNIX_TIMESTAMP(' . $tableName . '.leave_time) - UNIX_TIMESTAMP(' . $tableName . '.arrival_time))/60)', $request->getVisitTimeEnd()]);
        }
        // 到达距离筛选
        if (!empty($request->getArrivalDistanceStart())) {
            $records->andWhere(['>=', $tableName . '.arrival_distance', $request->getArrivalDistanceStart()]);
        }
        if (!empty($request->getArrivalDistanceEnd())) {
            $records->andWhere(['<=', $tableName . '.arrival_distance', $request->getArrivalDistanceEnd()]);
        }
        // 离开距离筛选
        if (!empty($request->getLeaveDistanceStart())) {
            $records->andWhere(['>=', $tableName . '.leave_distance', $request->getLeaveDistanceStart()]);
        }
        if (!empty($request->getLeaveDistanceEnd())) {
            $records->andWhere(['<=', $tableName . '.leave_distance', $request->getLeaveDistanceEnd()]);
        }
        // 拜访状态筛选 0拜访中 1已拜访
        if (!empty($request->getVisitStatus())) {
            $records->andWhere([$tableName . '.status' => $request->getVisitStatus()]);
        }


        if ($request->getRole() == 0) {
            $records->addSelect([$tableName . '.*', 'c.store_front_img', 'c.last_place_order_at', 'c.last_place_order_total', 'c.last_place_order_id', 'ic.store_front_img as intend_store_front_img'])
                ->leftJoin(['c' => LeCustomers::tableName()], "c.entity_id=" . $tableName . ".customer_id")
                ->leftJoin(['ic' => LeCustomersIntention::tableName()], "ic.entity_id=" . $tableName . ".customer_id")
                ->orderBy($tableName . '.entity_id desc');
        } else {
            $records->addSelect([$tableName . '.*', 'c.shop_images as store_front_img'])
                ->leftJoin(['c' => 'lelai_slim_merchant.' . LeMerchantStore::tableName()], "c.entity_id=" . $tableName . ".customer_id")
                ->orderBy($tableName . '.entity_id desc');
        }
        $totalCount = $records->count();
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
        $records = $records->offset($pagination->getOffset())
            ->limit($pageSize)
            ->asArray()
            ->all();

        $responseData = [
            'pagination' => Tools::getPagination($pagination),
            'records' => [],
        ];
        /** @var VisitRecords $record */
        foreach ($records as $record) {
            // 循环修改返回的数值的样式
            $record['use_minutes'] = intval(ceil((strtotime($record['leave_time']) - strtotime($record['arrival_time'])) / 60));
            $record['arrival_time'] = substr($record['arrival_time'], 11, 5);
            $record['leave_time'] = substr($record['leave_time'], 11, 5);
            $record['visit_status'] = decbin($record['visit_status']);
            $visit_result = str_pad(decbin($record['visit_result']), 4, 0);
            Tools::log($record['visit_result'], 'xiayy.log');
            $record['see_boss'] = $visit_result[0];
            $record['install_app'] = $visit_result[1];
            $record['place_order'] = $visit_result[2];
            $record['convey_promotion'] = $visit_result[3];
            // 查询超市、供应商的经纬度
            if ($request->getRole() == 0) {
                $storeModel = new LeCustomers();
            } else {
                $storeModel = new LeMerchantStore();
            }
            $storeInfo = $storeModel::find()->where(['entity_id' => $record['customer_id']])->one();
            $record['store_lat'] = $storeInfo->lat;
            $record['store_lng'] = $storeInfo->lng;

            $responseData['records'][] = $this->convertVisitRecordArray($record);
        }

        //拜访记录标序号
        $i = count($responseData['records']);
        foreach ($responseData['records'] as $k => $item) {
            $responseData['records'][$k]['serial_number'] = $i;
            $i--;
        }

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new visitedRecordsRequestNew();
    }

    public static function response()
    {
        return new visitedRecordsResponse();
    }
}