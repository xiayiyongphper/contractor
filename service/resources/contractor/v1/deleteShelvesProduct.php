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
use service\resources\Exception;

/**
 * Class addShelvesProduct
 * @package service\resources\contractor\v1
 */
class deleteShelvesProduct extends Contractor
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
            $shelve->delete();
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