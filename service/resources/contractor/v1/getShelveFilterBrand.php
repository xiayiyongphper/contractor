<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/12/3
 * Time: 10:24
 */

namespace service\resources\contractor\v1;

use service\components\Tools;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use service\message\contractor\GetShelveFilterBrandResponse;
use service\message\contractor\GetShelvesProductsRequest;
use common\models\CustomerShelvesProduct;
use yii\db\Expression;
use common\models\LeCustomers;
use yii\db\Query;

/**
 * Class getShelveFilterBrand
 * @package service\resources\contractor\v1
 */
class getShelveFilterBrand extends Contractor
{
    public function run($data)
    {
        /** @var GetShelvesProductsRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);

        $customerId = $request->getCustomerId();
        $firstCategoryId = $request->getFirstCategoryId();
        $thirdCategoryId = $request->getThirdCategoryId();
        if (empty($customerId)) {
            ContractorException::invalidParam();
        }
        if (empty($firstCategoryId) && empty($thirdCategoryId)) {
            ContractorException::invalidParam();
        }

        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if (!$customer) {
            ContractorException::storeNotExist();
        }

        $subQuery = CustomerShelvesProduct::find()
            ->alias('a')
            ->select([new Expression('distinct brand')])
            ->where(['customer_id' => $customerId]);

        if (!empty($firstCategoryId)) {
            $subQuery->andWhere(['first_category_id' => $firstCategoryId]);
        }
        if (!empty($thirdCategoryId)) {
            $subQuery->andWhere(['third_category_id' => $thirdCategoryId]);
        }

        // 供应商筛选
        if ($request->getWholesalerId() > 0) {
            $now = Tools::getDate()->date("Y-m-d H:i:s");
            $wholesalerIds = [$request->getWholesalerId()];

            $existQuery = (new Query())->select(['entity_id'])
                ->from(['p' => 'lelai_booking_product_a.products_city_' . $customer->city])
                ->where("p.lsin = a.lsin and p.wholesaler_id in (" . join(',', $wholesalerIds) . ") and p.status=1 and p.state=2 and shelf_from_date < '" . $now . "' and shelf_to_date > '" . $now . "'");
            $subQuery->andWhere(['exists', $existQuery]);
        }

        Tools::log($subQuery->createCommand()->rawSql, 'getShelveFilterBrand.log');

//        $subQuery = $subQuery->asArray()->all();
//        $brandArr = [];
//        foreach ($brands as $row){
//            $brandArr []= $row['brand'];
//        }

        $data = CustomerShelvesProduct::find()
            ->select(['brand', new Expression('sum(buy_count) as sort')])
            ->where([
                'customer_id' => $customerId,
                'brand' => $subQuery
            ])
            ->groupBy('brand')
            ->orderBy([
                'sort' => SORT_DESC,
                'brand' => SORT_ASC
            ]);
        Tools::log($data->createCommand()->rawSql, 'shelves.log');

        $data = $data->asArray()->all();
        Tools::log($data, 'shelves.log');
        $brands = [];
        foreach ($data as $row) {
            $brands [] = $row['brand'];
        }

        $result = ['brands' => $brands];
        Tools::log($result, 'shelves.log');
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    public static function request()
    {
        return new GetShelvesProductsRequest();
    }

    public static function response()
    {
        return new GetShelveFilterBrandResponse();
    }
}