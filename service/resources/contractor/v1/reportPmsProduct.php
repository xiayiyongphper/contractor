<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use service\components\Tools;
use service\message\contractor\PmsProduct;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class reportPmsProduct extends Contractor
{

    public function run($data)
    {
        /** @var PmsProduct $request */
        $request = self::parseRequest($data);
        $response = self::response();

        $contractor = $this->initContractor($request);

        if (!$request->getBarcode() || !$request->getCustomerId()) {
            ContractorException::invalidParam();
        }

        $customer = LeCustomers::findOne(['entity_id' => $request->getCustomerId()]);

        if (!$customer || $customer->contractor_id != $contractor->entity_id) {
            ContractorException::customerNotMatch();
        }

        $product['barcode'] = $request->getBarcode();
        $request->getName() && $product['name'] = $request->getName();
        $request->getBrand() && $product['brand'] = $request->getBrand();
        $request->getPrice() && $product['price'] = $request->getPrice();
        $request->getSalesAttributeValue() && $product['sales_attribute_value'] = $request->getSalesAttributeValue();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, ENV_PMS_API_PRODUCT_REVIEW_URL_V1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json; charset=UTF-8', 'Authorization:Bearer ' . ENV_PMS_API_TOKEN));
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($product));//POST数据

        $data = curl_exec($curl);
        $data = json_decode($data, true);

        if (!$data || $data['code']) {
            Tools::log(ENV_PMS_API_PRODUCT_REVIEW_URL_V1, 'pms_product_error.log');
            Tools::log($data, 'pms_product_error.log');
            ContractorException::contractorSystemError();
        }

        return $response;
    }

    public static function request()
    {
        return new PmsProduct();
    }

    public static function response()
    {
        return true;
    }
}