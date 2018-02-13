<?php
/**
 * Created by Jason Y. wang
 * User: wangyang
 * Date: 16-7-21
 * Time: 下午5:28
 */

namespace service\resources\contractor\v1;


use common\components\UserTools;

use service\message\contractor\GetAreaByLngLatRequest;
use service\message\contractor\GetAreaByLngLatResponse;
use service\models\common\Contractor;

class getAreaByLngLat extends Contractor
{
    public function run($data)
    {
        /** @var GetAreaByLngLatRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
//        Tools::log($request->getLat(),'wangyang.log');
//        Tools::log($request->getLng(),'wangyang.log');
        $area_id = UserTools::findAreaIdByLocation($request->getLat(),$request->getLng());
        $response = new GetAreaByLngLatResponse();
        $response->setAreaId($area_id);
        return $response;
    }

    public static function request(){
        return new GetAreaByLngLatRequest();
    }

    public static function response(){
        return new GetAreaByLngLatResponse();
    }
    
}