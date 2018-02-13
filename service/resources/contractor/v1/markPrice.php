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
use framework\components\ToolsAbstract;
use service\message\common\Image;
use service\message\contractor\MarkPriceRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class markPrice extends Contractor
{
    public function run($data)
    {
        /** @var MarkPriceRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);

        $product_id = $request->getProductId();
        $price = $request->getPrice();

        $markPrice = new ContractorMarkPriceHistory();
        $markPrice->contractor_id = $contractor->entity_id;
        $markPrice->contractor_name = $contractor->name;
        $markPrice->source = $request->getSource();
        $markPrice->source_type = (int)$request->getSourceType();
        $markPrice->city = $contractor->city;
        $markPrice->customer_name = $request->getStoreName();
        $markPrice->customer_id = $request->getStoreId();
        $markPrice->mark_price_product_id = $product_id;
        $markPrice->price = $price;
        $markPrice->created_at = ToolsAbstract::getDate()->date('Y-m-d H:i:s');

        $gallery = [];
        /** @var Image $image */
        foreach ($request->getGallery() as $image) {
            $gallery[] = $image->getSrc();
        }
        $markPrice->gallery = implode(';', $gallery);

        if (!$markPrice->save()) {
            ContractorException::contractorSystemError();
        }

        /* HL说帮他更新一下updated_at字段 */
        $markPriceProductObj = MarkPriceProduct::findOne(['entity_id' => $product_id]);
        if ($markPriceProductObj) {
            $markPriceProductObj->updated_at = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
            $markPriceProductObj->save();
        }

        return true;
    }

    public static function request()
    {
        return new MarkPriceRequest();
    }

    public static function response()
    {
        return true;
    }
}