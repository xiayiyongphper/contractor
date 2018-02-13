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
use service\message\merchant\searchProductResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class getPmsProduct extends Contractor
{

    public function run($data)
    {
        /** @var PmsProduct $request */
        $request = self::parseRequest($data);
        $response = self::response();

        $contractor = $this->initContractor($request);
        $customer = LeCustomers::findOne(['entity_id' => $request->getCustomerId()]);

        if (!$customer || $customer->contractor_id != $contractor->entity_id) {
            ContractorException::customerNotMatch();
        }

        $params = http_build_query(['barcode' => $request->getBarcode()]);
        $url = sprintf('%s?%s', ENV_PMS_API_PRODUCT_URL_V1, $params);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type:application/json; charset=UTF-8', 'Authorization:Bearer ' . ENV_PMS_API_TOKEN));
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $data = curl_exec($curl);
        $data = json_decode($data, true);

        if (!$data || $data['code']) {
            Tools::log(ENV_PMS_API_PRODUCT_URL_V1, 'pms_product_error.log');
            Tools::log($data, 'pms_product_error.log');
            ContractorException::contractorSystemError();
        }

        $products = [];
        if($data['data']){
            foreach ($data['data'] as $product) {
                $products[] = [
                    'product_id' => 0,
                    'name' => $product['name'],
                    'image' => $product['gallery'],
                ];
            }
        }

        $result['product_list'] = $products;
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    public static function request()
    {
        return new PmsProduct();
    }

    public static function response()
    {
        return new searchProductResponse();
    }
}