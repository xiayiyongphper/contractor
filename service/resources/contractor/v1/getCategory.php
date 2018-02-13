<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use service\components\ElasticSearchExt;
use service\components\Tools;
use service\message\merchant\categoryResponse;
use service\message\merchant\getAreaCategoryRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class getCategory extends Contractor
{
    public function run($data)
    {
        /** @var getAreaCategoryRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $contractor = $this->initContractor($request);
        $wholesaler_id = $request->getWholesalerId();
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($request->getCustomerId());

        if(!$customer){
            ContractorException::storeNotExist();
        }

        $city = $customer->city;
        $area_id = $customer->area_id;

        //供应商查询
        if ($wholesaler_id > 0) {
            $wholesaler_ids = [$wholesaler_id];
        } else {
            // 否则就查该区域的商家id
            $wholesaler_ids = Tools::getWholesalerIdsByAreaId($area_id);
        }
        $elasticSearch = new ElasticSearchExt($city);
        $category = $elasticSearch->getCategory($wholesaler_ids);

        $response = $this->response();

        $result['category'] = $category;

        $response->setFrom(Tools::pb_array_filter($result));

        return $response;
    }

    public static function request()
    {
        return new getAreaCategoryRequest();
    }

    public static function response()
    {
        return new categoryResponse();
    }
}