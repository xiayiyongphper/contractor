<?php

namespace service\resources\contractor\v1;

use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LePlanGroup;
use service\components\Tools;
use framework\data\Pagination;
use service\message\contractor\CityPlanGroupRequest;
use service\message\contractor\CityPlanGroupResponse;
use service\message\contractor\PlanGroupListRequest;
use service\message\contractor\PlanGroupListResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Class planGroup
 * 城市超市路线规划列表
 * @package service\resources\contractor\v1
 * 此接口适配APP版本 1.7.x xiayiyong
 */
class cityPlanGroup extends Contractor
{
    public function run($data)
    {
        /** @var CityPlanGroupRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $city = $request->getCity();
        $contractor = $this->initContractor($request);// 当前登录的业务员的信息

        if (!$city) {
            ContractorException::contractorCityEmpty();
        }
        $plan_group = LePlanGroup::find()->select('entity_id as group_id,name,city,contractor_id')->where(['city' => $city])->asArray()->all();
        $responseData = [
            'plan_group' => $plan_group,
        ];
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;

    }

    public static function request()
    {
        return new CityPlanGroupRequest();
    }

    public static function response()
    {
        return new CityPlanGroupResponse();
    }
}