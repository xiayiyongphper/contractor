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

class markPriceProductList2 extends Contractor
{
    public function run($data)
    {
        /** @var MarkPriceProductListRequest $request */
        $request = self::parseRequest($data);
        $page = $request->getPage() ?: 1;
        $pageSize = $request->getPageSize() ?: 10;
        $category_id = $request->getCategoryId() ?: 0;
        $city = $request->getCity();
        $check_type = $request->getCheckType();
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);

        if (!ContractorPermission::contractorMarkPriceListPermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }

        $city_list = array_filter(explode('|', $contractor->city_list));
        if (!in_array($city,$city_list)) {
            ContractorException::noPermission();
        }

        //商品总数量
        $productList = MarkPriceProduct::find()->where(['city' => $city])->andWhere(['status' => MarkPriceProduct::MARK_PRICE_PRODUCT_STATUS_SHOW]);
        if ($category_id > 0) {
            $productList->andWhere(['first_category_id' => $category_id]);
        }
        if($check_type == 1){
            $productList->andWhere(['contractor_id' => $contractor->entity_id]);
        }
        $count = $productList->count();
        $pages = new Pagination(['totalCount' => $count]);
        $pages->setCurPage($page);
        $pages->setPageSize($pageSize);

        $productList = $productList->orderBy('entity_id asc')
            ->offset($pages->getOffset())
            ->limit($pages->getLimit())->all();

        $products = [];
        if ($productList && count($productList)) {
            /** @var MarkPriceProduct $item */
            foreach ($productList as $item) {
                $product = [];
                $product['product_id'] = $item->entity_id;
                $product['name'] = $item->name;
                $product['image'] = $item->image;
                $product['barcode'] = $item->barcode;
                $product['first_category_id'] = $item->first_category_id;

                if($check_type == 1){
                    $priceHistory = ContractorMarkPriceHistory::getMarkPriceHistoryByProductId($item->entity_id,$contractor->entity_id);
                }else{
                    $priceHistory = ContractorMarkPriceHistory::getMarkPriceHistoryByProductId($item->entity_id);
                }

                if ($priceHistory) {
                    $product['last_marked_time'] = date('Y-m-d', strtotime($priceHistory[0]->created_at));
                    $product['count'] = count($priceHistory);

                    $history_list = [];
                    foreach ($priceHistory as $history){
                        $v = array(
                            'history_id' => $history->entity_id,
                            'contractor_name' => $history->contractor_name,
                            'price' => $history->price,
                            'created_at' => date('Y-m-d', strtotime($history->created_at)),
                            'source' => $history->source
                        );
                        $history_list []= $v;
                    }
                    $product['history'] = $history_list;
                } else {
                    $product['last_marked_time'] = '暂无更新';
                    $product['count'] = 0;
                }

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

        //Tools::log($result,'markPriceProductList.log');
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