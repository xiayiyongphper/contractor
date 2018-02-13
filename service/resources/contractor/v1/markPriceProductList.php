<?php
/**
 * Created by Jason Y. wang
 * User: wangyang
 * Date: 16-7-21
 * Time: 下午5:29
 */

namespace service\resources\contractor\v1;


use common\models\contractor\ContractorMarkPriceHistory;
use common\models\contractor\MarkPriceProduct;
use common\models\LeContractor;
use framework\data\Pagination;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\MarkPriceProductListRequest;
use service\message\contractor\MarkPriceProductListResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class markPriceProductList extends Contractor
{
    public function run($data)
    {
        /** @var MarkPriceProductListRequest $request */
        $request = self::parseRequest($data);
        $page = $request->getPage() ?: 1;
        $pageSize = $request->getPageSize() ?: 10;
        $category_id = $request->getCategoryId() ?: 0;
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);

        if (!ContractorPermission::contractorMarkPriceListPermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }

//        $city_list = array_filter(explode('|', $contractor->city_list));
        $city = $request->getCity();
        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        //商品总数量
        if ($category_id == 0) {
            $productList = MarkPriceProduct::find()->where(['city' => $city])->andWhere(['status' => 1]);
        } else {
            $productList = MarkPriceProduct::find()->where(['city' => $city, 'first_category_id' => $category_id]);
        }
        $productList = $productList->andWhere(['status' => 1]);
        $count = $productList->count();
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setCurPage($page);
        $pages->setPageSize($pageSize);


        $productList = $productList->offset($pages->getOffset())
            ->limit($pages->getLimit())->all();

        $products = [];
        if ($productList && count($productList)) {
            /** @var MarkPriceProduct $item */
            foreach ($productList as $item) {
                $product = [];
                $product['product_id'] = $item->entity_id;
                $product['name'] = $item->name;
                $product['image'] = $item->image;
                $priceHistory = ContractorMarkPriceHistory::getLatMarkPriceInfo($item->entity_id);
                if ($priceHistory) {
                    $product['price'] = $priceHistory->price;
                    $product['contractor_name'] = $priceHistory->contractor_name;
                    $product['last_marked_time'] = date('Y-m-d', strtotime($priceHistory->created_at));
                } else {
                    $product['price'] = -1;
                }
                $product['barcode'] = $item->barcode;
                $product['first_category_id'] = $item->first_category_id;

                array_push($products, $product);
            }
        }

        $result = [
            'product_list' => $products,
            'pages' => [
                'total_count' => $pages->getTotalCount(),
                'page' => $pages->getCurPage(),
                'last_page' => $pages->getLastPageNumber(),
                'page_size' => $pages->getPageSize(),
            ],
        ];
        //Tools::log($result,'wangyang.log');
        $response = $this->response();
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    public static function request()
    {
        return new MarkPriceProductListRequest();
    }

    public static function response()
    {
        return new MarkPriceProductListResponse();
    }
}