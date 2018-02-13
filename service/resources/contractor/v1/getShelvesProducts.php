<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/11/7
 * Time: 10:23
 */

namespace service\resources\contractor\v1;

use common\models\LeContractor;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\GetShelvesProductsResponse;
use service\message\contractor\GetShelvesProductsRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use common\models\LeCustomers;
use common\models\CustomerShelvesProduct;
use common\models\BestSellingLsin7Days;
use common\models\ShelvesProductCategoryMap;
use framework\data\Pagination;
use common\models\Products;
use yii\db\Expression;
use yii\db\Query;
use service\components\ProductHelper;

/**
 * Class getShelvesProducts
 * @package service\resources\contractor\v1
 */
class getShelvesProducts extends Contractor
{
    const PAGE_SIZE = 40;
    protected $_customerId;
    protected $_shelvesCategoryId;
    protected $_firstCategoryId;
    protected $_thirdCategoryId;
    protected $_brands = [];
    protected $_buyNumOrder;
    protected $_customer;
    /** @var Pagination */
    protected $_paginationRequest;
    /** @var Pagination */
    protected $_pagination;
    protected $_productList = [];
    protected $_totalCount;
    protected $_wholesalerIds;
    protected $_city;
    protected $_pageSize;
    protected $_page;
    protected $_now;

    public function run($data)
    {
        /** @var GetShelvesProductsRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);

        $this->_now = Tools::getDate()->date("Y-m-d H:i:s");
        $this->_customerId = $request->getCustomerId();
        $this->_shelvesCategoryId = $request->getShelvesCategoryId();
        $this->_firstCategoryId = $request->getFirstCategoryId();
        $this->_thirdCategoryId = $request->getThirdCategoryId();
        $brand = $request->getBrand();
        $this->_buyNumOrder = $request->getBuyNumOrder();

        $this->_brands = array_filter(explode('|', $brand));
        if (empty($this->_customerId) || empty($this->_shelvesCategoryId)) {
            ContractorException::invalidParam();
        }
        if (empty($this->_firstCategoryId) && empty($this->_thirdCategoryId)) {
            ContractorException::invalidParam();
        }
        if (!empty($this->_buyNumOrder)) {
            $this->_buyNumOrder = strtolower($this->_buyNumOrder);
            if (!in_array($this->_buyNumOrder, ['asc', 'desc'])) {
                ContractorException::invalidParam();
            }
        }

        /** @var LeCustomers $customer */
        $this->_customer = LeCustomers::findByCustomerId($this->_customerId);
        if (empty($this->_customer)) {
            ContractorException::storeNotExist();
        }
        
        // 供货商筛选
        if ($request->getWholesalerId() > 0) {
            $this->_wholesalerIds = [$request->getWholesalerId()];
        } else {
            $this->_wholesalerIds = Tools::getWholesalerIdsByAreaId($this->_customer->area_id);
        }

        $this->_city = $this->_customer->city;

        $this->_paginationRequest = $request->getPagination();

        if ($this->_paginationRequest) {
            $this->_page = $this->_paginationRequest->getPage() ?: 1;
//            $this->_pageSize = $this->_paginationRequest->getPageSize() ?: self::PAGE_SIZE;
            //page_size写死40条
            $this->_pageSize = self::PAGE_SIZE;
        } else {
            $this->_page = 1;
            $this->_pageSize = self::PAGE_SIZE;
        }

        if ($this->_firstCategoryId == -1) {
            $this->_getRecommendProducts();
        } else {
            $this->_getProducts();
        }

        $result = [
            'pagination' => [
                'total_count' => $this->_pagination->getTotalCount(),
                'page' => $this->_pagination->getCurPage(),
                'last_page' => $this->_pagination->getLastPageNumber(),
                'page_size' => $this->_pagination->getPageSize(),
            ],
            'product_list' => $this->_productList
        ];
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    private function _getRecommendProducts()
    {
        if (empty($this->_wholesalerIds)) {
            $this->_setPagination(0);
            $this->_productList = [];
            return;
        }

        //子查询查出货架分类下的所有一级分类
        $subQuery1 = ShelvesProductCategoryMap::find()
            ->select(['first_category_id'])
            ->where(['shelves_category_id' => $this->_shelvesCategoryId]);

        $existQuery = (new Query())->select(['entity_id'])
            ->from(['p' => 'lelai_booking_product_a.products_city_' . $this->_city])
            ->where("p.lsin = a.lsin and p.wholesaler_id in (" . join(',', $this->_wholesalerIds) . ") and p.status=1 and p.state=2 and shelf_from_date < '" . $this->_now . "' and shelf_to_date > '" . $this->_now . "'");

        $data = BestSellingLsin7Days::find()
            ->alias('a')
            ->leftJoin(['b' => CustomerShelvesProduct::tableName()], 'b.lsin=a.lsin and b.customer_id=' . $this->_customerId)
            ->select(['a.lsin', 'b.latest_buy_time', 'b.latest_buy_num', 'b.buy_count', 'b.out_of_stock'])
            ->where(['a.first_category_id' => $subQuery1])
            ->andWhere(['>', 'a.order_num', 0])
            ->andWhere(['exists', $existQuery]);

//        $totalCount = $data->count();
//        $totalCount = $totalCount > 40 ? 40 : $totalCount;
        $totalCount = 40;
        $this->_setPagination($totalCount);

        $data = $data->orderBy('order_num desc')->offset($this->_pagination->getOffset())->limit($this->_pageSize);
//        Tools::log($data->createCommand()->rawSql,'shelves.log');
        $data = $data->asArray()->all();

        $this->_fillData($data);
    }

    private function _getProducts()
    {
        if (empty($this->_wholesalerIds)) {
            $this->_setPagination(0);
            $this->_productList = [];
            return;
        }

        $existQuery = (new Query())->select(['entity_id'])
            ->from(['p' => 'lelai_booking_product_a.products_city_' . $this->_city])
            ->where("p.lsin = a.lsin and p.wholesaler_id in (" . join(',', $this->_wholesalerIds) . ") and p.status=1 and p.state=2 and shelf_from_date < '" . $this->_now . "' and shelf_to_date > '" . $this->_now . "'");

        $data = CustomerShelvesProduct::find()
            ->alias('a')
            ->select(['a.lsin', 'a.latest_buy_time', 'a.latest_buy_num', 'a.buy_count', 'a.out_of_stock'])
            ->where(['a.customer_id' => $this->_customerId]);

        if (!empty($this->_firstCategoryId)) {
            $data = $data->andWhere(['first_category_id' => $this->_firstCategoryId]);
        }
        if (!empty($this->_thirdCategoryId)) {
            $data = $data->andWhere(['a.third_category_id' => $this->_thirdCategoryId]);
        }
//        Tools::log($this->_brands,'shelves.log');
        if (!empty($this->_brands)) {
            $data = $data->andWhere(['a.brand' => $this->_brands]);
        }
        $data = $data->andWhere(['exists', $existQuery]);

        //目前没要求分页,这里pagesize设大点
        $this->_pageSize = 200;
        $totalCount = $data->count();
        $this->_setPagination($totalCount);

        $order = [];
        switch ($this->_buyNumOrder) {
            case 'asc':
                $order['a.buy_count'] = SORT_ASC;
                break;
            case 'desc':
                $order['a.buy_count'] = SORT_DESC;
                break;
            default:
        }

        $order['a.buy_cycle_proportion'] = SORT_DESC;
        $data = $data->orderBy($order);
        Tools::log($data->createCommand()->rawSql, 'shelves.log');
        $data = $data->offset($this->_pagination->getOffset())
            ->limit($this->_pageSize)
            ->asArray()
            ->all();

        $this->_fillData($data);
    }

    private function _setPagination($totalCount)
    {
        $this->_pagination = new Pagination(['totalCount' => $totalCount]);
        $this->_pagination->setPageSize($this->_pageSize);
        $this->_pagination->setCurPage($this->_page);
    }

    private function _fillData($data)
    {
        $lsinArr = [];
        foreach ($data as $row) {
            $lsinArr [] = $row['lsin'];
        }
//        Tools::log($lsinArr,'shelves.log');

        //子查询查出lsin符合条件的所有商品的实际价格和供应商权重
        $model = new Products($this->_city);
        $subQuery2 = (new Query())
            ->from(['p' => 'lelai_booking_product_a.products_city_' . $this->_city])
            ->leftJoin(['s' => 'lelai_slim_merchant.le_merchant_store'], 's.entity_id = p.wholesaler_id')
            ->select(['p.entity_id', 'p.lsin', 's.sort', "if(special_price > 0 and special_from_date < '" . $this->_now . "' and special_to_date > '" . $this->_now . "',special_price,price) as final_price"])
            ->where([
                'p.lsin' => $lsinArr,
                'p.status' => Products::STATUS_ENABLED,
                'p.state' => Products::STATE_APPROVED
            ])->andWhere(['<=', 'p.shelf_from_date', $this->_now])
            ->andWhere(['>', 'p.shelf_to_date', $this->_now])
            ->andWhere(['p.wholesaler_id' => $this->_wholesalerIds]);

        //按lsin聚合，聚合内取按价格和供应商权重排序后的第一个商品id
        $productIdsArr = (new Query)
            ->select([new Expression("group_concat(entity_id order by final_price asc,sort desc,entity_id desc) as entity_id"), 'lsin'])
            ->from(['sub' => $subQuery2])
            ->groupBy(['lsin'])
            ->createCommand($model->getDb());

        Tools::log($productIdsArr->rawSql, 'shelves.log');
        $productIdsArr = $productIdsArr->queryAll();

        $productIds = [];
        $aggregationIdsMap = [];
        foreach ($productIdsArr as $row) {
            if (!empty($row['entity_id'])) {
                $ids = array_filter(explode(',', $row['entity_id']));
                if (!empty($ids)) {
                    $productIds [] = current($ids);
                    $aggregationIdsMap[$row['lsin']] = $ids;
                }
            }
        }
        unset($productIdsArr);
        Tools::log($productIds, 'shelves.log');

        //获取商品信息
        $products = (new ProductHelper())->initWithProductIds($productIds, $this->_city, $this->_wholesalerIds)->getTags()->getData();
        $wholesalerList = Tools::getStoreDetailBrief($this->_wholesalerIds, $this->_customer->area_id);

        $productMap = [];
        foreach ($products as &$product) {
            $product['min_trade_amount'] = !empty($wholesalerList[$product['wholesaler_id']]['min_trade_amount']) ? $wholesalerList[$product['wholesaler_id']]['min_trade_amount'] : 0;
            $productMap[$product['lsin']] = $product;
        }
        unset($products);

        $today = strtotime(ToolsAbstract::getDate()->date('Y-m-d'));
        foreach ($data as $row) {
            if (!isset($productMap[$row['lsin']])) continue;

            $pro = $productMap[$row['lsin']];
            if (isset($aggregationIdsMap[$row['lsin']])) {
                $pro['aggregation_product_ids'] = $aggregationIdsMap[$row['lsin']];
                $pro['aggregation_num'] = count($pro['aggregation_product_ids']);
            } else {
                $pro['aggregation_product_ids'] = [];
                $pro['aggregation_num'] = 0;
            }

            $pro['on_shelves'] = 0;
            if (!is_null($row['latest_buy_time'])) {//在用户货架上
                $pro['on_shelves'] = 1;

                if (strtotime($row['latest_buy_time']) > 0) {//有购买历史
                    $days = ($today - strtotime(substr($row['latest_buy_time'], 0, 10))) / 3600 / 24;
                    $pro['latest_buy_time'] = $days > 0 ? $days . '天前' : '今天';
                    $pro['latest_buy_num'] = $row['latest_buy_num'];
                    $pro['buy_count'] = $row['buy_count'];
                }

                if ($row['out_of_stock']) {//急需备货，加标签，在标签列表最前面
                    $tag = [
                        'short' => '急需补货',
                        'color' => '666666'
                    ];
                    array_unshift($pro['tags'], $tag);
                }
            }

            $this->_productList [] = $pro;
        }
    }

    public static function request()
    {
        return new GetShelvesProductsRequest();
    }

    public static function response()
    {
        return new GetShelvesProductsResponse();
    }
}