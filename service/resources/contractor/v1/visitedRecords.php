<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LeCustomersIntention;
use framework\data\Pagination;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\visitedRecordsRequest;
use service\message\contractor\visitedRecordsResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-7-25
 * Time: 上午11:43
 * Email: henryzxj1989@gmail.com
 */

/**
 * Class visitedRecords
 * 拜访记录列表
 * @package service\resources\contractor\v1
 */
class visitedRecords extends Contractor
{
    const PAGE_SIZE = 10;

    public function run($data)
    {

        /** @var visitedRecordsRequest $request */
        $request = self::parseRequest($data);
        $contractor_id = $request->getContractorId();
        $response = self::response();
        Tools::log($request->toArray(), 'visitedRecords.log');
        $contractor = $this->initContractor($request);
//        $city_list = array_filter(explode('|', $contractor->city_list));
        if (!ContractorPermission::contractorVisitStoreListCreatePermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }
        $city = $request->getCity();
        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        if ($request->getCustomerId() > 0) {
            $whereArray['contractor_visit_records.customer_id'] = $request->getCustomerId();
        } else {
            if ($contractor->role == self::COMMON_CONTRACTOR) {
                //该为只要是业务员的超市，其就能看到所有拜访记录
                list($customerIds, $customerIntentionIds) = LeContractor::getVisitedCustomerIds($contractor_id);
                $whereArray = [
                    'or',
                    ['and', ['in', 'contractor_visit_records.customer_id', $customerIds], ['is_intended' => 0]],
                    ['and', ['in', 'contractor_visit_records.customer_id', $customerIntentionIds], ['is_intended' => 1]],
                ];
            } else {
                $contractor_ids = LeContractor::find()->where(['city' => $city])->column();
                $whereArray = [
                    'contractor_visit_records.contractor_id' => $contractor_ids,
                ];
            }
        }

        $records = VisitRecords::find()
            ->where($whereArray)
            ->addSelect(['contractor_visit_records.*', 'c.store_front_img', 'ic.store_front_img as intend_store_front_img'])
            ->leftJoin(['c' => LeCustomers::tableName()], "c.entity_id=contractor_visit_records.customer_id")
            ->leftJoin(['ic' => LeCustomersIntention::tableName()], "ic.entity_id=contractor_visit_records.customer_id")
            ->orderBy('contractor_visit_records.entity_id desc');
//        Tools::log($records->createCommand()->getRawSql(), 'wangyang.log');
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
            ->limit(self::PAGE_SIZE)
            ->asArray()
            ->all();

        $responseData = [
            'pagination' => Tools::getPagination($pagination),
            'records' => [],
        ];
        /** @var VisitRecords $record */
        foreach ($records as $record) {
            $responseData['records'][] = $this->convertVisitRecordArray($record);
        }

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new visitedRecordsRequest();
    }

    public static function response()
    {
        return new visitedRecordsResponse();
    }
}