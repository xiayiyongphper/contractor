<?php

namespace service\resources\contractor\v1;

use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LePlanGroup;
use service\components\Tools;
use framework\data\Pagination;
use service\message\contractor\PlanGroupListRequest;
use service\message\contractor\PlanGroupListResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Class planGroup
 * 超市路线规划列表
 * @package service\resources\contractor\v1
 * 此接口适配APP版本 1.7.x xiayiyong
 */
class planGroupList extends Contractor
{
    const BOUNDARY = 15; // 小于N个超市的时候 不推荐此路线规划  N = 15
    const PAGE_SIZE = 1000;// 分页大小

    public function run($data)
    {
        /** @var PlanGroupListRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $city = $request->getCity();
        $paginationReq = $request->getPagination();// 分页信息
        if ($paginationReq) {
            $page = $paginationReq->getPage() ?: 1;
            $pageSize = $paginationReq->getPageSize() ?: self::PAGE_SIZE;
        } else {
            $page = 1;
            $pageSize = self::PAGE_SIZE;
        }
        $filterContractorId = $request->getFilterContractorId();// 筛选条件的用户id
        $contractor = $this->initContractor($request);// 当前登录的业务员的信息

        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        // 判断是否为城市经理 若是城市经理 则查询出该城市所有业务员的信息 以及 该城市所有超市路线规划信息
        $andWhere = [];
        if ($contractor->role != self::COMMON_CONTRACTOR) {
            // 按照管理的城市列表为准 查询该城市经理管理的城市
            $cityManager = LeContractor::find()->where(['entity_id' => $contractor->entity_id])->asArray()->one();

            // 查询出该城市的所有业务员
            $contractorAll = LeContractor::find()->select('entity_id as contractor_id,name,city')->where(['status' => 1, 'city' => $city])->asArray()->all();
            $first = [];
            foreach ($contractorAll as $k => $v) {
                if ($v['contractor_id'] == $contractor->entity_id) {
                    // 放在第一位
                    $first['contractor_id'] = $v['contractor_id'];
                    $first['name'] = '全部';
                    $first['city'] = $v['city'];
                    unset($contractorAll[$k]);
                }
            }
            if (empty($first)) {
                $first['contractor_id'] = $cityManager['entity_id'];
                $first['name'] = '全部';
                $first['city'] = $cityManager['city'];
            }
            array_unshift($contractorAll, $first);

            // 筛选统计条件
            if ($filterContractorId > 0) {
                $andWhere = ['contractor_id' => $filterContractorId];
            }
        } else {
            $contractorAll[0]['contractor_id'] = $contractor->entity_id;
            $contractorAll[0]['name'] = $contractor->name;
            $contractorAll[0]['city'] = $contractor->city;
            // 筛选统计条件
            $andWhere = ['contractor_id' => $contractor->entity_id];
        }

        $planGroupArr = LePlanGroup::find()->select('entity_id as group_id,name,city,contractor_id')->where(['city' => $city]);

        $pagination = new Pagination(['totalCount' => $planGroupArr->count()]);
        $pagination->setPageSize($pageSize);
        $pagination->setCurPage($page);
        $plan_group = $planGroupArr->offset($pagination->getOffset())
            ->orderBy('entity_id desc')
            ->limit($pageSize)
            ->asArray()
            ->all();

        // 查询该城市未分配的超市数量
        $waiting_num = LeCustomers::find()->where(['city' => $city])->andWhere(['<=', 'group_id', 0])->andWhere(['status' => 1])->andWhere($andWhere)->count();
        $waitingGroup = [
            'group_id' => 0,
            'name' => '待分路线超市',
            'city' => $city,
            'contractor_id' => $contractor->entity_id,
            'customer_num' => $waiting_num,
        ];

        // 查询出每个路线规划所拥有的超市
        if (!empty($plan_group)) {
            foreach ($plan_group as $k => $v) {
                $customer_num = LeCustomers::find()->where(['city' => $city, 'group_id' => $v['group_id'], 'status' => 1])->andWhere($andWhere)->count();
                $plan_group[$k]['customer_num'] = $customer_num;
                $plan_group[$k]['boundary'] = ($customer_num < self::BOUNDARY) ? '小于' . self::BOUNDARY . '个,系统将不会推荐此路线' : '';
            }
        }

        // 待路线规划超市放在第一位
        array_unshift($plan_group, $waitingGroup);


        $responseData = [
            'contractor' => $contractorAll,
            'plan_group' => $plan_group,
            'pagination' => Tools::getPagination($pagination),
        ];

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;

    }

    public static function request()
    {
        return new PlanGroupListRequest();
    }

    public static function response()
    {
        return new PlanGroupListResponse();
    }
}
