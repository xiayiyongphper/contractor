<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\LeCustomers;
use common\models\LeVisitPlan;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\ChangeVisitPlanRequest;
use service\message\contractor\ChangeVisitPlanResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


/**
 * Class contractorList
 * 新增删除拜访计划中的超市
 * @package service\resources\contractor\v1
 * 此接口适配APP版本 1.7.x xiayiyong
 */
class changeVisitPlan extends Contractor
{
    public function run($data)
    {
        /** @var ChangeVisitPlanRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);


        $date = $request->getDate();// 当前时间
        $beOpc = $request->getBeOperationContractor();// 被操作的业务员的id
        $planIdArr = $request->getPlanId();// 需要删除的超市的拜访计划对应的id
        $remark = $request->getRemark();// 新增或者删除的备注
        $customerArr = $request->getCustomerId();// 被操作的超市的id集合  是个[1,2,3]这种数组

        // 只能当天能够新增或者删除拜访计划的内容
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            throw new ContractorException('只有当天及以后的拜访计划才能编辑', 40000);
        }

        // 若不是城市经理或者管理员 则不能操作别人的拜访计划
        if ($beOpc > 0 && $contractor->role != self::COMMON_CONTRACTOR) {
            $contractor_id = $beOpc;
        } else {
            $contractor_id = $contractor->entity_id;
        }

        // 若是有plan_id则表示是删除已有的拜访计划里面的超市
        if (!empty($planIdArr)) {
            foreach ($planIdArr as $k => $planId) {
                $visitPlan = LeVisitPlan::find()->where(['entity_id' => $planId, 'customer_id' => $customerArr[0], 'date' => $date])->one();
                if (!$visitPlan) {
                    throw new ContractorException('要删除的拜访计划内的超市不存在', 40000);
                }
//                if (str_replace(' ', '', $remark) == '') {
//                    throw new ContractorException('删除拜访计划内超市必须填写备注', 40000);
//                }
                $visitPlan->action = 2;// 更改状态为手动删除
                $visitPlan->operation_time = ToolsAbstract::getDate()->date('Y-m-d H:i:s');// 删除时间
                $visitPlan->remark = $remark;// 删除备注
                if (!$visitPlan->save()) {
                    throw new ContractorException('计划内超市删除失败', 40000);
                }
            }
        } else {
            // 先查询 该业务员已有的拜访计划内的超市id
            $visitPlanCustomer = LeVisitPlan::find()->alias('v')->select(['v.customer_id', 'c.store_name'])->leftJoin(['c' => LeCustomers::tableName()], 'c.entity_id = v.customer_id')->where(['c.contractor_id' => $contractor_id, 'v.date' => $date])->andWhere(['in', 'v.action', [0, 1]])->asArray()->all();
            $customerHas = [];
            if (!empty($visitPlanCustomer)) {
                foreach ($visitPlanCustomer as $kc => $vc) {
                    $customerHas[$vc['customer_id']] = $vc['store_name'];
                }
            }

            // 新增超市到拜访计划里面
            foreach ($customerArr as $k => $v) {
                // 判断该超市是否已经存在于该业务员的拜访计划中  若是存在 则不能新增
                if (in_array($v, array_keys($customerHas))) {
                    throw new ContractorException('超市:' . $customerHas[$v] . '已经在该业务员的拜访计划中,不能新增', 40000);
                } else {
                    // 若是不在拜访计划内但是已经临时拜访过 则也不能新增
                    $visitRecordElse = VisitRecords::find()->where(['contractor_id' => $contractor_id, 'customer_id' => $v])->andWhere(['>=', 'created_at', $date . ' 00:00:00'])->orderBy('created_at desc')->one();
                    if ($visitRecordElse) {
                        throw new ContractorException('超市:' . $visitRecordElse->store_name . '已经被临时拜访过,不能新增', 40000);
                    }
                }

                // 判断新增的超市是否属于该业务员
                $customerInfo = LeCustomers::find()->where(['entity_id' => $v, 'contractor_id' => $contractor_id])->one();
                if (!$customerInfo) {
                    throw new ContractorException('该超市不属于该业务员,不能新增', 40000);
                }

                // 开始新增
                $visitPlanNew = new LeVisitPlan();
                $visitPlanNew->isNewRecord = true;
                $visitPlanNew->customer_id = $v;
                $visitPlanNew->date = $date;
                $visitPlanNew->action = 1;// 0::自动生成 1:手动新增 2:手动删除
                $visitPlanNew->operation_time = ToolsAbstract::getDate()->date('Y-m-d H:i:s');// 新增时间
                $visitPlanNew->remark = $remark;// 备注
                if (!$visitPlanNew->save()) {
                    throw new ContractorException('拜访计划新增超市失败', 40000);
                }
            }
        }

        $responseData = [
            'contractor_id' => $contractor->entity_id,
            'contractor_name' => $contractor->name,
            'date' => $date,
            'customer_id' => $customerArr,
        ];

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new ChangeVisitPlanRequest();
    }

    public static function response()
    {
        return new ChangeVisitPlanResponse();
    }
}