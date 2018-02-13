<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 18:31
 */
namespace service\resources\contractor\v1;

use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\ContractorResponse;
use service\models\common\Contractor;

/**
 * Class contractorDetail
 * @package service\resources\contractor\v1
 * 业务员详情接口
 */
class contractorDetail extends Contractor
{

    public function run($data)
    {
        /** @var ContractorAuthenticationRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
        $response = $this->getContractorInfo($contractor,$this->role_permission);
        return $response;
    }

    public static function request(){
        return new ContractorAuthenticationRequest();
    }

    public static function response(){
        return new ContractorResponse();
    }

}