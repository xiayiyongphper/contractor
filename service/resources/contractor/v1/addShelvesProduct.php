<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/11/29
 * Time: 17:01
 */

namespace service\resources\contractor\v1;

use common\models\LeContractor;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\models\common\Contractor;
use service\message\contractor\AddShelvesProductRequest;
use service\models\common\ContractorException;
use common\models\LeCustomers;
use common\models\CustomerShelvesProduct;
use common\models\Products;
use service\resources\Exception;

/**
 * Class addShelvesProduct
 * @package service\resources\contractor\v1
 */
class addShelvesProduct extends Contractor
{
    public function run($data){
        /** @var AddShelvesProductRequest $request */
        $request = self::parseRequest($data);
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);

        $customerId = $request->getCustomerId();
        $lsin = $request->getLsin();
        if(empty($customerId) || empty($lsin)){
            ContractorException::invalidParam();
        }
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if(empty($customer)){
            ContractorException::storeNotExist();
        }

        $shelve = CustomerShelvesProduct::findOne([
            'customer_id' => $customerId,
            'lsin' => $lsin
        ]);

        if(!empty($shelve)){
            ContractorException::shelvesProductExist();
        }

        //根据lsin查一个商品，获取分类信息
        /** @var Products $product */
        $product = new Products($contractor->city);
        $product = $product->findOne(['lsin' => $lsin]);
//        $product = new Products(441800)->findOne(['lsin' => $lsin]);
        if(empty($product)){
            ContractorException::invalidLsin();
        }

        $shelve = new CustomerShelvesProduct();
        $shelve->customer_id = $customerId;
        $shelve->first_category_id = $product->first_category_id;
        $shelve->second_category_id = $product->second_category_id;
        $shelve->third_category_id = $product->third_category_id;
        $shelve->lsin = $lsin;
        $shelve->brand = $product->brand;
        $shelve->buy_cycle_proportion = 100000;

        $shelve->save();
        if(!empty($shelve->getErrors())){
            Tools::logException(new \Exception(json_encode($shelve->getErrors())));
            ContractorException::operateFail();
        }

        return true;
    }

    public static function request()
    {
        return new AddShelvesProductRequest();
    }

    public static function response()
    {
        return;
    }
}