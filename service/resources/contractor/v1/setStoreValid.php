<?php
/**
 * Created by hongliang
 * User: hongliang
 * Date: 17-3-31
 * Time: 下午5:29
 */

namespace service\resources\contractor\v1;


use common\models\LeCustomers;
use common\models\LeCustomersIntention;
use service\components\Tools;
use service\message\contractor\SetStoreValidRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class setStoreValid extends Contractor
{
    public function run($data)
    {
        /** @var SetStoreValidRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);

        Tools::log('customer_style===='.$request->getCustomerStyle(),'hl.log');
        if($request->getCustomerStyle() == 1){
            $store = LeCustomersIntention::find()->where(['entity_id' => $request->getStoreId()])->one();
        }else{
            $store = LeCustomers::find()->where(['entity_id' => $request->getStoreId()])->one();
        }

        if(empty($store)){
            ContractorException::storeNotExist();
        }
        $store->disabled = $request->getDisabled();
        Tools::log('Disabled===='.$request->getDisabled(),'hl.log');

        if(!$store->save()){
            ContractorException::contractorSystemError();
        }
        //Tools::log($store,'hl.log');

        return true;
    }

    public static function request(){
        return new SetStoreValidRequest();
    }

    public static function response(){
        return true;
    }
}