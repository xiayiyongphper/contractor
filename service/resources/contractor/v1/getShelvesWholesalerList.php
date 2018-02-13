<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/11/7
 * Time: 10:23
 */

namespace service\resources\contractor\v1;

use common\models\LeContractor;
use service\components\Tools;
use service\message\contractor\GetShelvesProductsRequest;
use service\message\contractor\getShelvesWholesalerListRequest;
use service\message\contractor\getShelvesWholesalerListResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use common\models\LeCustomers;
use common\models\CustomerShelvesProduct;
use common\models\BestSellingLsin7Days;
use common\models\ShelvesProductCategoryMap;
use yii\db\Expression;

/**
 * Class getShelvesProducts
 * @package service\resources\contractor\v1
 */
class getShelvesWholesalerList extends Contractor
{
    protected $_customerId;
    protected $_wholesalerIds;
    protected $_shelvesCategoryId;
    protected $_customer;
    protected $_city;
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

        /** @var LeCustomers $customer */
        $this->_customer = LeCustomers::findByCustomerId($this->_customerId);
        if (empty($this->_customer)) {
            ContractorException::storeNotExist();
        }
        // 若是该地区没有供应商 则直接返回空
        $this->_wholesalerIds = Tools::getWholesalerIdsByAreaId($this->_customer->area_id);
        if (empty($this->_wholesalerIds)) {
            throw new ContractorException('该超市所在的地区没有供应商', 40000);
        }
        $this->_city = $this->_customer->city;

        // 查询出所有符合货架条件的供应商的id
        $wholesaler_ids = $this->_getConformingWholesalerIds();
        // 查询出供应商的详情
        $wholesaler_list = Tools::getStoreDetailBrief($wholesaler_ids, $this->_customer->area_id);

        $result = [
            'wholesaler_list' => $wholesaler_list
        ];
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    private function _getConformingWholesalerIds()
    {
        //子查询查出货架分类下的所有一级分类
        $subQuery1 = ShelvesProductCategoryMap::find()
            ->select(['first_category_id'])
            ->where(['shelves_category_id' => $this->_shelvesCategoryId]);

        // 热销lsin
        $hotData = BestSellingLsin7Days::find()
            ->alias('a')
            ->select([new Expression('distinct(p.wholesaler_id)')])
            ->leftJoin(['p' => 'lelai_booking_product_a.products_city_' . $this->_city], 'p.lsin = a.lsin')
            ->where(['a.first_category_id' => $subQuery1])
            ->andWhere(['>', 'a.order_num', 0])
            ->andWhere("p.lsin = a.lsin and p.wholesaler_id in (" . join(',', $this->_wholesalerIds) . ") and p.status=1 and p.state=2 and shelf_from_date < '" . $this->_now . "' and shelf_to_date > '" . $this->_now . "'");

        // 超市的货架商品
        $shelvesData = CustomerShelvesProduct::find()
            ->alias('a')
            ->select([new Expression('distinct(p.wholesaler_id)')])
            ->leftJoin(['p' => 'lelai_booking_product_a.products_city_' . $this->_city], 'p.lsin = a.lsin')
            ->where(['a.first_category_id' => $subQuery1])
            ->andWhere(['a.customer_id' => $this->_customerId])
            ->andWhere("p.lsin = a.lsin and p.wholesaler_id in (" . join(',', $this->_wholesalerIds) . ") and p.status=1 and p.state=2 and shelf_from_date < '" . $this->_now . "' and shelf_to_date > '" . $this->_now . "'");


        Tools::log($hotData->createCommand()->rawSql, 'hotData.log');
        Tools::log($shelvesData->createCommand()->rawSql, 'ShelvesData.log');

        $hotData = $hotData->asArray()->all();
        $ShelvesData = $shelvesData->asArray()->all();

        $wholesalerIds = [];
        foreach ($hotData as $item) {
            if (!in_array($item['wholesaler_id'], $wholesalerIds)) {
                $wholesalerIds[] = $item['wholesaler_id'];
            }
        }
        foreach ($ShelvesData as $item) {
            if (!in_array($item['wholesaler_id'], $wholesalerIds)) {
                $wholesalerIds[] = $item['wholesaler_id'];
            }
        }

        return $wholesalerIds;

    }

    public static function request()
    {
        return new getShelvesWholesalerListRequest();
    }

    public static function response()
    {
        return new getShelvesWholesalerListResponse();
    }
}