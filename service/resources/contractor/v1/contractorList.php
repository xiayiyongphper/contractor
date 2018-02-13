<?php
namespace service\resources\contractor\v1;

use common\models\LeContractor;
use service\components\Tools;
use service\message\contractor\getVisitFilterItemsRequest;
use service\message\contractor\getVisitFilterItemsResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


/**
* Class contractorList
* 业务员列表
* @package service\resources\contractor\v1
*/
class contractorList extends Contractor
{
    public function run($data)
    {
        /** @var getVisitFilterItemsRequest $request */
        $request = self::parseRequest($data);
        $contractor_id = $request->getContractorId();
        $response = self::response();
        $contractor = $this->initContractor($request);

        $city = $request->getCity();
        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        if ($contractor->role == self::COMMON_CONTRACTOR){
            //如果是普通业务员，业务员列表里只有自己
            $responseData['contractors'][] = [
              'contractor_id' => $contractor_id,
                'name' => $contractor->name
            ];
        }else{
            $contractors = LeContractor::find()->select('entity_id as contractor_id,name')->where(['city' => $city])->asArray()->all();
            $responseData['contractors'] = $contractors;
        }

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;

    }

    public static function request()
    {
        return new getVisitFilterItemsRequest();
    }

    public static function response()
    {
        return new getVisitFilterItemsResponse();
    }
}