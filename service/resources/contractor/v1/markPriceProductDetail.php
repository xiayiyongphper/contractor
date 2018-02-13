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
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\MarkPriceDetailRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class markPriceProductDetail extends Contractor
{
    public function run($data)
    {
        $response = $this->response();
        /** @var MarkPriceDetailRequest $request */
        $request = self::parseRequest($data);
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);

        if(!ContractorPermission::contractorMarkPriceProductDetailPermission($this->role_permission)){
            ContractorException::contractorPermissionError();
        }

        $product_id = $request->getProductId()?:0;
        $product = MarkPriceProduct::findOne(['entity_id' => $product_id]);

        if(!$product || $product->status == 0){
            ContractorException::markPriceProductNotFound();
        }

        $historyList = [];
        //最多返回50条记录
        $productMarkPriceHistory = ContractorMarkPriceHistory::find()
            ->where(['mark_price_product_id' => $product_id])->orderBy('created_at desc')->limit(50)->all();
        $first_item = null;
        if($productMarkPriceHistory && count($productMarkPriceHistory)){
            /** @var ContractorMarkPriceHistory $item */
            foreach ($productMarkPriceHistory as $item){
                if($first_item == null){
                    $first_item = $item;
                }
                $history['history_id'] = $item->entity_id;
                $history['contractor_name'] = $item->contractor_name;
                $history['price'] = $item->price;
                $history['created_at'] = date('Y-m-d',strtotime($item->created_at));
                array_push($historyList,$history);
            }
        }

        if($first_item){
            $price = $first_item->price;
            $created_at = date('Y-m-d',strtotime($first_item->created_at));
            $name = $first_item->contractor_name;
        }else{
            $price = -1;
            $created_at = '';
            $name = '';
        }

        $result = [
            'product_id' => $product->entity_id,
            'name' => $product->name,
            'image' => $product->image,
            'barcode' => $product->barcode,
            'price' => $price,
            'last_marked_time' => $created_at,
            'history' => $historyList,
            'contractor_name' => $name,
        ];

        $response->setFrom(Tools::pb_array_filter($result));
        return  $response;
    }

    public static function request()
    {
        return new MarkPriceDetailRequest();
    }

    public static function response()
    {
        return new \service\message\common\MarkPriceProduct();
    }
}