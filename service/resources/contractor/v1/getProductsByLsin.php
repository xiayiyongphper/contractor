<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/12/4
 * Time: 10:53
 */

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use service\components\Tools;
use service\message\merchant\getProductsRequest;
use service\message\merchant\searchProductResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use common\models\Products;
use service\components\ProductHelper;

/**
 * Class getProductList
 * @package service\resources\contractor\v1
 */
class getProductsByLsin extends Contractor
{

    public function run($data)
    {
        /** @var getProductsRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
//        $contractor = $this->initContractor($request);

        $customerId = $request->getCustomerId();
        $lsin = $request->getLsin();
        if (empty($customerId) || empty($lsin)) {
            ContractorException::invalidParam();
        }

        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if (!$customer) {
            ContractorException::storeNotExist();
        }

        if ($request->getWholesalerId() > 0) {
            $wholesalerIds = [$request->getWholesalerId()];
        } else {
            $wholesalerIds = Tools::getWholesalerIdsByAreaId($customer->area_id);
        }


        $now = Tools::getDate()->date("Y-m-d H:i:s");
        $products = new Products($customer->city);
        $products = $products->find()
            ->alias('p')
            ->leftJoin(['s' => 'lelai_slim_merchant.le_merchant_store'], 's.entity_id = p.wholesaler_id')
            ->select(['p.*', 's.sort', "if(special_price > 0 and special_from_date < '" . $now . "' and special_to_date > '" . $now . "',special_price,price) as final_price"])
            ->where([
                'p.lsin' => $lsin,
                'p.wholesaler_id' => $wholesalerIds,
                'p.status' => Products::STATUS_ENABLED,
                'p.state' => Products::STATE_APPROVED
            ])->andWhere(['<', 'p.shelf_from_date', $now])
            ->andWhere(['>', 'p.shelf_to_date', $now])
            ->orderBy([
                'final_price' => SORT_ASC,
                'sort' => SORT_DESC,
                'entity_id' => SORT_ASC
            ]);

        Tools::log($products->createCommand()->rawSql, 'get_product.log');
        $products = $products->asArray()->all();

        $products = (new ProductHelper())->initWithProductArray($products, $customer->city, '388x388', $wholesalerIds)->getTags()->getLatestBuy(['customer_id' => $customerId])->getData();

        $wholesalerList = Tools::getStoreDetailBrief($wholesalerIds, $customer->area_id);
        foreach ($products as &$product) {
            $product['min_trade_amount'] = !empty($wholesalerList[$product['wholesaler_id']]['min_trade_amount']) ? $wholesalerList[$product['wholesaler_id']]['min_trade_amount'] : 0;
        }

        $result = [
            'product_list' => $products
        ];
        $response = $this->response();
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    public static function request()
    {
        return new getProductsRequest();
    }

    public static function response()
    {
        return new searchProductResponse();
    }
}