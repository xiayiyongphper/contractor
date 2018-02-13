<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\contractor\v1;

use common\models\contractorReportProduct;
use common\models\LeCustomers;
use service\components\Tools;
use service\message\contractor\PmsProduct;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class reportProduct extends Contractor
{

    public function run($data)
    {
        /** @var PmsProduct $request */
        $request = self::parseRequest($data);

        $this->initContractor($request);

        if(!$request->getName()){
            ContractorException::invalidParam();
        }

        $product = new contractorReportProduct();

        $product->name = $request->getName();
        $product->brand = $request->getBrand();
        $product->barcode = $request->getBarcode();
        $product->wholesaler = $request->getWholesaler();
        $product->remark = $request->getRemark();
        $product->gallery = implode(';', $request->getGallery());
        $product->contractor_id = $request->getContractorId();

        if(!$product->save()){
            ContractorException::SaveFailed();
        }

        $response = self::response();
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