<?php

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use common\models\Products;
use common\models\RecordProducts;
use common\models\RecordWholesalers;
use service\components\ProductHelper;
use service\components\Tools;
use service\message\contractor\RecordProductValidateRequest;
use service\message\merchant\getProductResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Class recordProductValidate
 * @package service\resources\contractor\v1
 * 判断秒杀商品
 * 判断普通商品
 */
class recordProductValidate extends Contractor
{

    public function run($data)
    {
        /** @var RecordProductValidateRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();

        $record_wholesalers_id = $request->getRecordWholesalersId();
        $customer = LeCustomers::findByCustomerId($request->getCustomerId());
        $wholesaler_record = RecordWholesalers::findOne(['entity_id' => $record_wholesalers_id]);

        if (!$wholesaler_record) {
            ContractorException::recordListNotExist();
        }

        $wholesaler_id = $wholesaler_record->wholesaler_id;
        $wholesalerIds = Tools::getWholesalerIdsByAreaId($customer->area_id);

        if (!in_array($wholesaler_id, $wholesalerIds)) {
            ContractorException::wholesalerNotDelivery();
        }

        //清单中的商品
        $recordProducts = RecordProducts::find()->where(['record_wholesaler_id' => $record_wholesalers_id])->all();

        $productIds = RecordProducts::find()->select('product_id')
            ->where(['record_wholesaler_id' => $record_wholesalers_id])->column();
        $products = new ProductHelper();
        //数据库中有的商品
        $products = $products->initWithProductIds($productIds, $customer->city, [], '388x388', false)->getTags()->getData();

        $validateProduct = [];

        /** @var RecordProducts $recordProduct */
        foreach ($recordProducts as $recordProduct) {

            $productId = $recordProduct->product_id;
            $num = $recordProduct->num;

            //商品已经删除
            if (!isset($products[$productId])) {
                $product = [];
                $product['name'] = $recordProduct->product_name;
                $product['num'] = $num;
                $product['price'] = $recordProduct->price;
                $product['product_id'] = $productId;
                $product['status'] = 3; //商品已经删除
                $validateProduct[$productId] = $product;
                $this->deleteRecordProduct($record_wholesalers_id, $productId);
                continue;
            }

            //审核不通过或已下架 起订数量大于库存
            if ($products[$productId]['status'] != Products::STATUS_ENABLED ||   //商品已下架，则删除该清单中的商品
                $products[$productId]['state'] != Products::STATE_APPROVED //商品审核不通过，则删除该清单中的商品

            ) {
                $products[$productId]['status'] = Products::STATUS_DISABLED;
                $products[$productId]['num'] = $num;
                $validateProduct[$productId] = $products[$productId];
                $this->deleteRecordProduct($record_wholesalers_id, $productId);
                continue;
            }

            //商品库存为0
            if ($products[$productId]['qty'] == 0) {
                $products[$productId]['status'] = 4;  //已经卖光
                $products[$productId]['num'] = $num;
                $validateProduct[$productId] = $products[$productId];
                $this->deleteRecordProduct($record_wholesalers_id, $productId);
                continue;
            } else if ($products[$productId]['minimum_order'] > $products[$productId]['qty']) { //最低购买数量大于库存，则删除该清单中的商品
                $products[$productId]['status'] = Products::STATUS_DISABLED;
                $products[$productId]['num'] = $num;
                $validateProduct[$productId] = $products[$productId];
                $this->deleteRecordProduct($record_wholesalers_id, $productId);
                continue;
            }

            //商品库存小于商品数量，则修正商品数量为库存数量
            if ($products[$productId]['qty'] < $num) {
                $products[$productId]['num'] = $num;
                $validateProduct[$productId] = $products[$productId];
                $recordProduct->num = $products[$productId]['qty'];
                $recordProduct->save();
                continue;
            }

            //判断起订数量，则修正商品数量为起订数量
            if ($products[$productId]['minimum_order'] > $num) {  //商品库存小于商品数量，则修正商品数量
                $products[$productId]['num'] = $num;
                $validateProduct[$productId] = $products[$productId];
                $recordProduct->num = $products[$productId]['minimum_order'];
                $recordProduct->save();
                continue;
            }
        }
        Tools::log($validateProduct, 'recordProductValidate.log');
        $response->setFrom(Tools::pb_array_filter(['product_list' => $validateProduct]));

        return $response;
    }

    private function deleteRecordProduct($record_wholesalers_id, $productId)
    {
        RecordProducts::find()->where(['record_wholesaler_id' => $record_wholesalers_id])
            ->andWhere(['product_id' => $productId])
            ->one()
            ->delete();
    }


    public static function request()
    {
        return new RecordProductValidateRequest();
    }

    public static function response()
    {
        return new getProductResponse();
    }

}