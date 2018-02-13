<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\LeCustomers;
use common\models\LePlanGroup;
use common\models\LeVisitPlan;
use service\components\Tools;
use service\message\contractor\VisitPlanRequest;
use service\message\contractor\VisitPlanResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


/**
 * Class contractorList
 * 拜访计划首页
 * @package service\resources\contractor\v1
 * 此接口适配APP版本 1.7.x xiayiyong
 */
class visitPlanList extends Contractor
{
    public function run($data)
    {
        /** @var VisitPlanRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $city = $request->getCity();
        $contractor = $this->initContractor($request);
        $filterContractorId = $request->getFilterContractorId();
        $date = $request->getDate() ?: date('Y-m-d');

        if (!$city) {
            ContractorException::contractorCityEmpty();
        }
        // 首先查询该城市是否有路线规划
        $plan_group = LePlanGroup::find()->where(['city' => $city])->all();
        if (!$plan_group) {
            $responseData = [
                'error_msg' => '您还没有对超市进行路线规划，请尽快与城市经理一起完成超市路线规划工作，方便系统为你们推送拜访优先级最高的路线规划，提高工作效率',
                'has_group' => 0,
            ];
            $response->setFrom(Tools::pb_array_filter($responseData));
            return $response;
        }

        // 查询计划
        $select = ['v.customer_id', 'v.entity_id as plan_id', 'v.date', 'v.action', 'v.level as plan_level', 'v.remark', 'v.group_name', 'c.*'];
        $visitAll = LeVisitPlan::find()->alias('v')->select($select)->leftJoin(['c' => LeCustomers::tableName()], 'c.entity_id = v.customer_id')->where(['v.date' => $date])->andWhere(['in', 'v.action', [0, 1]]);

        // 城市经理可以查看别的业务员的拜访计划
        $contractor_id = 0;
        if ($contractor->role != self::COMMON_CONTRACTOR) {
            if ($filterContractorId > 0) {
                $contractor_id = $filterContractorId;
            }
        } else {
            $contractor_id = $contractor->entity_id;
        }
        $visitAll->andWhere(['c.contractor_id' => $contractor_id]);
        $visit_plan = $visitAll->orderBy('v.level asc,v.entity_id desc')->asArray()->all();

        // 若是没有拜访计划 则返回 提示没有拜访计划的信息
        if (empty($visit_plan)) {
            $responseData = [
                'error_msg' => '暂无计划',
                'has_plan' => 0,
            ];
            $response->setFrom(Tools::pb_array_filter($responseData));
            return $response;
        }

        // 组合visit_plan的超市信息
        $customerArr = [];
        $hasVisitNum = 0;
        $group_name = '';// 路线名称
        foreach ($visit_plan as $k => $v) {
            $group_name = $v['group_name'];
            $visitRecord = VisitRecords::find()->where(['customer_id' => $v['customer_id'], 'contractor_id' => $contractor_id])->andWhere(['between', 'created_at', $date . ' 00:00:00', $date . ' 23:59:59'])->orderBy('created_at asc')->all();
            // 该计划内超市有拜访记录 返回拜访记录信息
            if ($visitRecord) {
                foreach ($visitRecord as $kh => $vh) {
                    if ($vh->visit_status == 0) {
                        // 拜访中 返回拜访记录的信息
                        $visit_status = 1;// 0待拜访 1拜访中 2已拜访
                    } else {
                        // 已拜访 返回拜访记录的信息
                        $visit_status = 2;
                        $hasVisitNum++;
                    }
                    $visit_plan[$k] = [
                        'plan_id' => $v['plan_id'],
                        'date' => $v['date'],
                        'visit_status' => $visit_status,
                        'task_status' => $v['action'],// // 0:任务 1:新增 2:删除 3临时
                        'level' => $v['plan_level'],// 0临时拜访 1:必拜访 2:推荐拜访
                        'customer_id' => $v['customer_id'],// 商店的id
                        'customer_name' => $v['store_name'],// 商店名称
                        'store_front_img' => $v['store_front_img'],// 商店正面照
                        'lat' => $v['lat'],// 商店经度
                        'lng' => $v['lng'],// 商店纬度
                        'record_id' => $vh->entity_id,// 拜访记录id
                        'visit_way' => $vh->visit_way,// 拜访方式
                        'visit_purpose' => $vh->visit_purpose,// 拜访目的
                        'arrival_time' => substr($vh->arrival_time, 11, 5),// 抵达时间
                        'leave_time' => substr($vh->leave_time, 11, 5),// 离开时间
                        'use_minutes' => intval(ceil((strtotime($vh->leave_time) - strtotime($vh->arrival_time)) / 60)),// 用时分钟
                        'arrival_distance' => $vh->arrival_distance,// 抵达定位差 千米
                        'leave_distance' => $vh->leave_distance,// 离开定位差
                    ];
                }
            } else {
                // 该计划内超市没有拜访记录 待拜访 返回商店的信息
                $storeInfo = Tools::getCustomerBrief($v['customer_id'], $date);// 获取单个商店的简略信息
                $visit_plan[$k] = [
                    'plan_id' => $v['plan_id'],
                    'date' => $v['date'],
                    'visit_status' => 0,
                    'task_status' => $v['action'],// // 0:任务 1:新增 2:删除 3临时
                    'level' => $v['plan_level'],// 0临时拜访 1:必拜访 2:推荐拜访
                    'customer_id' => $storeInfo['store_id'],// 商店的id
                    'customer_name' => $storeInfo['store_name'],// 商店名称
                    'store_front_img' => $storeInfo['store_front_img'],// 商店正面照
                    'lat' => $storeInfo['lat'],// 商店经度
                    'lng' => $storeInfo['lng'],// 商店纬度
                    'classify_tag' => $storeInfo['classify_tag'],// 超市聚合tag
                    'last_visit_label' => $storeInfo['last_visit_label'],// 最近拜访
                    'last_ordered_label' => $storeInfo['last_ordered_label'],// 最近下单
                    'visit_task' => $storeInfo['visit_task'], // 拜访任务
                ];
            }
            $customerArr[] = $v['customer_id'];// 拜访计划内的超市id集合
        }

        // 再查询出  有拜访记录但是不在拜访计划内的临时拜访的超市
        $visitRecordElse = VisitRecords::find()->where(['contractor_id' => $contractor_id])->andWhere(['not in', 'customer_id', $customerArr])->andWhere(['between', 'created_at', $date . ' 00:00:00', $date . ' 23:59:59'])->orderBy('created_at asc')->all();
        if ($visitRecordElse) {
            // 说明临时拜访的超市 返回拜访记录信息
            foreach ($visitRecordElse as $kl => $vl) {
                // 根据商店的id查询出商店的信息
                $customerInfo = LeCustomers::find()->where(['entity_id' => $vl->customer_id])->one();
                if ($vl->visit_status == 0) {
                    // 拜访中 返回拜访记录的信息
                    $visit_status = 1;// 0待拜访 1拜访中 2已拜访
                } else {
                    // 已拜访 返回拜访记录的信息
                    $visit_status = 2;
                }
                $visit_plan[] = [
                    'plan_id' => 0,// 已拜访
                    'date' => $date,// 日期
                    'visit_status' => $visit_status,// 0未拜访 1拜访中 2已拜访
                    'task_status' => 3,// 0:任务 1:新增 2:删除 3临时
                    'level' => 0,// 0临时拜访 1:必拜访 2:推荐拜访
                    'customer_id' => $vl->customer_id,// 商店的id
                    'customer_name' => $customerInfo->store_name,// 商店名称
                    'store_front_img' => $customerInfo->store_front_img,// 商店正面照
                    'lat' => $customerInfo->lat,// 商店经度
                    'lng' => $customerInfo->lng,// 商店纬度
                    'record_id' => $vl->entity_id,// 拜访记录的id
                    'visit_way' => $vl->visit_way,// 拜访方式
                    'visit_purpose' => $vl->visit_purpose,// 拜访目的
                    'arrival_time' => substr($vl->arrival_time, 11, 5),// 抵达时间
                    'leave_time' => substr($vl->leave_time, 11, 5),// 离开时间
                    'use_minutes' => intval(ceil((strtotime($vl->leave_time) - strtotime($vl->arrival_time)) / 60)),// 用时分钟
                    'arrival_distance' => $vl->arrival_distance,// 抵达定位差 千米
                    'leave_distance' => $vl->leave_distance,// 离开定位差
                ];
            }
        }

        // 组合修改记录
        $visitChange = LeVisitPlan::find()->alias('v')->select(['v.customer_id', 'v.entity_id as plan_id', 'v.date', 'v.action', 'v.level as plan_level', 'v.remark', 'v.operation_time', 'c.*'])->leftJoin(['c' => LeCustomers::tableName()], 'c.entity_id = v.customer_id')->where(['v.date' => $date, 'c.contractor_id' => $contractor_id])->andWhere(['in', 'v.action', [1, 2]])->orderBy('v.entity_id desc')->asArray()->all();
//        throw new ContractorException(json_encode($visitChange), 110);
        $visit_plan_change = [];
        if ($visitChange) {
            foreach ($visitChange as $kc => $vc) {
                $visit_plan_change[$kc] = [
                    'plan_id' => $vc['plan_id'],
                    'date' => $vc['date'],
                    'task_status' => $vc['action'],// 0:任务 1:新增 2:删除 3临时
                    'level' => $vc['plan_level'],// 0临时拜访 1:必拜访 2:推荐拜访
                    'customer_id' => $vc['customer_id'],// 商店的id
                    'customer_name' => $vc['store_name'],// 商店名称
                    'contractor_id' => $vc['contractor_id'],// 操作人的id
                    'contractor_name' => $vc['contractor'],// 操作人的名字
                    'operation_time' => $vc['operation_time'],// 操作时间
                    'remark' => $vc['remark'],// 备注
                ];
            }
        }

        // 拜访序号 拜访轨迹用
        $i = 0;
        foreach ($visit_plan as $kpn => $vpn) {
            if ($vpn['visit_status'] != 0) {
                $i++;
                $visit_plan[$kpn]['serial_number'] = $i;
            } else {
                $visit_plan[$kpn]['serial_number'] = 0;
            }
        }

        $responseData = [
            'contractor_name' => $contractor->name,
            'date' => $date,
            'schedule' => '已拜访' . $hasVisitNum . '家，拜访完成' . ceil(($hasVisitNum / planGroupList::BOUNDARY) * 100) . '%',
            'group_name' => $group_name,
            'visit_plan' => $visit_plan,
            'visit_plan_change' => $visit_plan_change,
        ];

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;

    }

    public static function request()
    {
        return new VisitPlanRequest();
    }

    public static function response()
    {
        return new VisitPlanResponse();
    }
}