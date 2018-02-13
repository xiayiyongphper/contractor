<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 25/1/2016
 * Time: 11:19 AM
 */

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use service\components\Tools;
use service\message\merchant\getStoresByAreaIdsRequest;
use service\message\merchant\getStoresByAreaIdsResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class getStoreListByAreaIds extends Contractor
{
    public function run($data)
    {
        /** @var getStoresByAreaIdsRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        $response = $this->response();

        $customerId = $request->getCustomerId();

        if (!$customerId) {
            return $response;
        }

        $customer = LeCustomers::findByCustomerId($customerId);
        Tools::log('customer_id:' . $customerId, 'getStoreListByAreaIds.log');
        if (!$customer) {
            ContractorException::storeNotExist();
        }
        $areaId = $customer->area_id;
        //获取所有区域内店铺列表ID
        $wholesalerIds = Tools::getWholesalerIdsByAreaId($areaId);
        $wholesalerArray = Tools::getStoreDetailBrief($wholesalerIds, $areaId, 'sort desc');

        $responseData = [
            'wholesaler_list' => $wholesalerArray,
        ];
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new getStoresByAreaIdsRequest();
    }

    public static function response()
    {
        return new getStoresByAreaIdsResponse();
    }
}