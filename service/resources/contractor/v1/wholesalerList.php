<?php

namespace service\resources\contractor\v1;

use common\models\ContractorVisitWholesaler;
use common\models\LeMerchantStore;
use service\components\Tools;
use framework\data\Pagination;
use service\message\contractor\WholesalerListRequest;
use service\message\contractor\WholesalerListResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


/**
 * Class contractorList
 * 供应商列表
 * @package service\resources\contractor\v1
 * 此接口适配APP版本 1.7.x xiayiyong
 */
class wholesalerList extends Contractor
{
    const PAGE_SIZE = 10;

    public function run($data)
    {
        /** @var WholesalerListRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $city = $request->getCity();
        $page = $request->getPage() ?: 1;
        $pageSize = $request->getPageSize() ?: self::PAGE_SIZE;

        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        $wholesalerList = LeMerchantStore::find()->select('entity_id as wholesaler_id,store_name as wholesaler_name,contact_phone as customer_service_phone,store_address as address,status')->where(['city' => $city]);

        $pagination = new Pagination(['totalCount' => $wholesalerList->count()]);
        $pagination->setPageSize($pageSize);
        $pagination->setCurPage($page);
        $records = $wholesalerList->offset($pagination->getOffset())
            ->limit($pageSize)
            ->asArray()
            ->all();

        // 再查询出最近拜访时间点
        if (!empty($records)) {
            foreach ($records as $k => $v) {
                $lastVisit = ContractorVisitWholesaler::find()->select('visited_at')->where(['customer_id' => $v['wholesaler_id']])->orderBy('visited_at desc')->asArray()->one();
                if (empty($lastVisit)) {
                    $records[$k]['recent_visits_days'] = -1;
                } else {
                    $records[$k]['recent_visits_days'] = ceil((time() - strtotime($lastVisit['visited_at'])) / (24 * 3600));
                }
            }
        }


        $responseData = [
            'pagination' => Tools::getPagination($pagination),
            'wholesaler_list' => $records,
        ];

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;

    }

    public static function request()
    {
        return new WholesalerListRequest();
    }

    public static function response()
    {
        return new WholesalerListResponse();
    }
}