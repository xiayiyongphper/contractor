<?php

namespace service\resources\contractor\v1;

use common\models\CustomerLevel;
use common\models\CustomerType;
use common\models\LeContractor;
use service\components\Tools;
use service\message\contractor\StoreListFilterRequest;
use service\message\contractor\StoreListFilterResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


/**
 * 店铺列表过滤条件
 * @author zqy
 * @package service\resources\contractor\v1
 */
class storeListFilter extends Contractor
{
    /**
     * 入口
     *
     * @param string $data
     * @return StoreListFilterResponse
     * @throws ContractorException
     */
    public function run($data)
    {
        /** @var StoreListFilterRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);

        /* 验证contractor */
        $cityArr = array_filter(explode('|', $contractor->city_list));
        if (!$cityArr) {
            ContractorException::contractorCityListEmpty();
        }

        /* 验证城市参数 */
        if (!in_array((string)$request->getCity(), $cityArr)) {
            throw new ContractorException('不在该城市管理范围', 401);
        }

        /* 获取业务员列表 */
        $contractorArr = [['key' => $contractor->entity_id, 'value' => $contractor->name]];
        if ($this->isAllowRole($contractor)) {
            $condArr = $request->getCity() ? ['city' => $request->getCity()] : null;
            $contractorArr = LeContractor::find()
                ->select('entity_id AS key,name AS value')
                ->where($condArr)
                ->andWhere(['status' => LeContractor::CONTRACTOR_STATUS_NORMAL])
                ->asArray()->all();
            // 城市经理，则添加全部
//            array_unshift($contractorArr, ['key' => '0', 'value' => '全部']);
        }

        /* 获取客户类型和客户等级 */
        $storeLevelArr = CustomerLevel::find()->select('entity_id AS key,level AS value')->asArray()->all();
        $storeTypeArr = CustomerType::find()->select('entity_id AS key,type AS value')->asArray()->all();

        $data = [
            'contractor_list' => $contractorArr,
            'store_type_list' => $storeTypeArr,
            'store_level_list' => $storeLevelArr,
            'customer_type_list' => [
                ['key' => '1', 'value' => '已注册'],
                ['key' => '2', 'value' => '意向'],
            ]
        ];

        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($data));
        return $response;
    }

    /**
     * 是否允许的角色
     *
     * @param LeContractor $contractor
     * @return bool
     */
    private function isAllowRole(LeContractor $contractor)
    {
        $allowArr = [
            Contractor::MANAGER_CONTRACTOR,    // 城市经理
            Contractor::AREA_CONTRACTOR,    // 大区经理
            Contractor::CEO_MANAGER,    // 总办
            Contractor::SYSTEM_MANAGER  // 管理员
        ];
        if (in_array((string)$contractor->role, $allowArr, true)) {
            return true;
        }
        return false;
    }

    /**
     * @return StoreListFilterRequest
     */
    public static function request()
    {
        return new StoreListFilterRequest();
    }

    /**
     * @return StoreListFilterResponse
     */
    public static function response()
    {
        return new StoreListFilterResponse();
    }
}