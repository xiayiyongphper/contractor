<?php
namespace service\resources\contractor\v1;

use common\models\Region;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\contractorCityListRequest;
use service\message\contractor\contractorCityListResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

//use Elasticsearch\Endpoints\Indices\Validate\Query;

/**
 * Created by PhpStorm.
 * User: hongliang
 * Date: 17-3-31
 * Time: 上午11:43
 */

/**
 * Class contractorCityList
 * 业务员管辖城市列表
 * @package service\resources\contractor\v1
 */
class contractorCityList extends Contractor
{
    public function run($data)
    {
        /** @var contractorCityListRequest $request */
        $request = self::parseRequest($data);
        $contractor_id = $request->getContractorId();
        Tools::log('contractor_id=='.$contractor_id, 'hl.log');
        $response = self::response();
        $contractor = $this->initContractor($request);

        $city_id_list = explode('|',$contractor->city_list);//此处不会为空，在initContractor中已检验过，如为空则抛异常
        foreach ($city_id_list as $k=>$city_id){
            $city_id = intval($city_id);
            //因为city_list两端也有'|'，所以会有空值，要过滤掉
            if($city_id == 0){
                unset($city_id_list[$k]);
                continue;
            }
            $city_id_list[$k] = intval($city_id);
        }
        Tools::log($city_id_list, 'hl.log');
        $city_info_list = Region::getCityInfoByCityIds($city_id_list);
        Tools::log($city_info_list,'hl.log');
        $city_group = array();
        foreach ($city_info_list as $city){
            if(!isset($city_group[$city['province_code']])){
                $city_group[$city['province_code']] = array(
                    'code' => $city['province_code'],
                    'name' => $city['province_name'],
                    'city_list' => array()
                );
            }

            $city_group[$city['province_code']]['city_list'] []= array(
                'code' => $city['city_code'],
                'name' => $city['city_name']
            );
        }

        Tools::log($city_group, 'hl.log');
        $responseData['city_tree'] = array_values($city_group);
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new contractorCityListRequest();
    }

    public static function response()
    {
        return new contractorCityListResponse();
    }
}