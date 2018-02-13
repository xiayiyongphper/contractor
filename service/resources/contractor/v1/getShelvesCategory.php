<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/12/1
 * Time: 17:59
 */

namespace service\resources\contractor\v1;

use service\components\Tools;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use service\message\merchant\categoryResponse;
use service\message\merchant\getAreaCategoryRequest;
use common\models\CustomerShelvesProduct;
use common\models\ShelvesProductCategoryMap;
use yii\db\Expression;
use common\models\LeCustomers;
use yii\db\Query;

/**
 * Class getShelvesCategory
 * @package service\resources\contractor\v1
 */
class getShelvesCategory extends Contractor
{
    public function run($data)
    {
        /** @var getAreaCategoryRequest $request */
        $request = $this->request();
        $request->parseFromString($data);
        $contractor = $this->initContractor($request);

        $customerId = $request->getCustomerId();
        $shelves_category_id = $request->getShelvesCategoryId();
        if (empty($customerId) || empty($shelves_category_id)) {
            ContractorException::invalidParam();
        }

        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if (!$customer) {
            ContractorException::storeNotExist();
        }

        $thirdCategoryIds = [];
        if ($request->getWholesalerId() > 0) {
            $wholesalerIds = [$request->getWholesalerId()];
        } else {
            $wholesalerIds = Tools::getWholesalerIdsByAreaId($customer->area_id);
        }

        Tools::log($wholesalerIds, 'shelves.log');

        if (!empty($wholesalerIds)) {
            $now = Tools::getDate()->date("Y-m-d H:i:s");

            $subQuery = ShelvesProductCategoryMap::find()
                ->select(['first_category_id'])
                ->where(['shelves_category_id' => $shelves_category_id]);

            $existQuery = (new Query())->select(['entity_id'])
                ->from(['p' => 'lelai_booking_product_a.products_city_' . $customer->city])
                ->where("p.lsin = a.lsin and p.wholesaler_id in (" . join(',', $wholesalerIds) . ") and p.status=1 and p.state=2 and shelf_from_date < '" . $now . "' and shelf_to_date > '" . $now . "'");

            $data = CustomerShelvesProduct::find()
                ->alias('a')
                ->select(new Expression('distinct third_category_id as third_category_id'))
                ->where([
                    'customer_id' => $customerId,
                    'first_category_id' => $subQuery
                ])->andWhere(['exists', $existQuery]);
            Tools::log($data->createCommand()->rawSql, 'shelves.log');
            $data = $data->asArray()->all();

            foreach ($data as $row) {
                $thirdCategoryIds [] = $row['third_category_id'];
            }
            Tools::log($thirdCategoryIds, 'shelves.log');
        }

        $categoryData = Tools::getCategoryByThirdCategoryIds($thirdCategoryIds);
        $recommend_category = [
            'id' => -1,
            'parent_id' => 1,
            'name' => '热门推荐',
            'level' => '1',
            'child_category' => []
        ];
        array_unshift($categoryData['child_category'], $recommend_category);

        $result = [
            'category' => $categoryData
        ];

//        Tools::log($result,'shelves.log');
        $response = $this->response();
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