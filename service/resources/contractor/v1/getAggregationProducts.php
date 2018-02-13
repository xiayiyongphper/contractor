<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2017/9/20
 * Time: 13:35
 */

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use service\components\ProductHelper;
use service\components\Tools;
use service\message\merchant\getProductRequest;
use service\message\merchant\getProductResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Author: Jason Y. Wang
 * Class getAggregationProducts
 * @package service\resources\merchant\v1
 */
class getAggregationProducts extends Contractor
{
    /**
     * 获取聚合商品
     * @param string $data
     * @return mixed
     */
    public function run($data)
    {
        /** @var getProductRequest $request */
        $request = self::parseRequest($data);
        $response = $this->response();

        $contractor = $this->initContractor($request);
        $customer = LeCustomers::findOne(['entity_id' => $request->getCustomerId()]);

        if (!$customer) {
            ContractorException::storeNotExist();
        }

        if ($customer->contractor_id != $contractor->entity_id) {
            ContractorException::customerNotMatch();
        }

        $productIds = $request->getProductIds();
        $products = (new ProductHelper($customer->area_id))->initWithProductIds($productIds, $customer->city)
            ->getTags()->correctMinTradeAmount($customer->entity_id)->getData();
        Tools::log($products, 'getAggregationProducts.log');

        $response->setFrom(Tools::pb_array_filter(['product_list' => $products]));
        return $response;
    }

    public static function request()
    {
        return new getProductRequest();
    }

    public static function response()
    {
        return new getProductResponse();
    }

}