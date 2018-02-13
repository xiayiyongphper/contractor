<?php

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use common\models\LePlanGroup;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\PlanGroupEditRequest;
use service\message\contractor\PlanGroupEditResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use yii\base\ErrorException;

/**
 * Class planGroup
 * 超市路线规划新增 编辑 移动 删除
 * @package service\resources\contractor\v1
 * 此接口适配APP版本 1.7.x xiayiyong
 */
class planGroupEdit extends Contractor
{

    public function run($data)
    {
        /** @var PlanGroupEditRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $city = $request->getCity();
        $contractor = $this->initContractor($request);// 当前登录的业务员的信息

        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        // 判断是否为城市经理 若是才能执行路线规划的新增和删除、
        $plan_group = $request->getPlanGroup() ? $request->getPlanGroup() : [];// 路线规划信息
        $storesArr = $request->getStoreId();// 超市信息
        $del_group = $request->getDelGroup();// 是否删除路线规划
        $group_id = intval($plan_group->getGroupId());
        // 判断是否为删除 且只有城市经理和管理员才能删除
        if ($del_group == 1) {
            if ($contractor->role != self::COMMON_CONTRACTOR) {
                if ($group_id > 0) {
                    $groupInfo = LePlanGroup::find()->where(['entity_id' => $group_id])->one();
                    if (!$groupInfo) {
                        ContractorException::groupIdEmpty();
                    }
                    // 更改超市的group_id为0
                    $customerUpdate = LeCustomers::updateAll(['group_id' => 0], 'group_id = ' . $groupInfo->entity_id);
                    if ($customerUpdate !== false) {
                        // 删除路线规划
                        $groupInfo->delete();
                    }
                } else {
                    ContractorException::groupIdEmpty();
                }
            } else {
                ContractorException::noPermission();
            }
        } else {
            // 只有城市经理和管理员才能新增路线规划
            if ($contractor->role != self::COMMON_CONTRACTOR) {
                // 编辑或者新增路线规划
                if ($group_id <= 0 && $plan_group->getName()) {
                    // 路线规划名称不能重复
                    if ($plan_group->getName()) {
                        $isRepeat = LePlanGroup::find()->where(['name' => $plan_group->getName(), 'contractor_id' => $contractor->entity_id, 'city' => $city])->one();
                        if ($isRepeat) {
                            throw new ContractorException('路线名称:' . $plan_group->getName() . '重复,请修改', 40000);
                        }

                        // 新增路线规划
                        $planModel = new LePlanGroup();
                        $planModel->name = $plan_group->getName();
                        $planModel->city = $city;
                        $planModel->contractor_id = $contractor->entity_id;
                        $planModel->creat_at = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
                        $planModel->update_at = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
                        $planModel->remark = '';
                        if (!$planModel->save()) {
                            throw new ErrorException('新增路线失败');
                        }
                        // 获取刚插入的路线规划的id并更新对应的超市的group_id
                        $group_id = $planModel->attributes['entity_id'];
                    } else {
                        ContractorException::noPermission();
                    }
                }

                // 修改路线规划名称
                if ($group_id > 0 && $plan_group->getName()) {
                    // 路线规划名称不能重复
                    if ($plan_group->getName()) {
                        $isRepeat = LePlanGroup::find()->where(['name' => $plan_group->getName(), 'contractor_id' => $contractor->entity_id, 'city' => $city])->andWhere(['!=', 'entity_id', $group_id])->one();
                        if ($isRepeat) {
                            throw new ContractorException('路线名称:' . $plan_group->getName() . '重复,请修改', 40000);
                        }
                    }
                    $planOne = LePlanGroup::find()->where(['entity_id' => $group_id])->one();
                    $planOne->name = $plan_group->getName();
                    if (!$planOne->save()) {
                        throw new ContractorException('路线名称:' . $plan_group->getName() . '修改失败', 40000);
                    }
                }
            }

            // 更新超市表的group_id
            if ($group_id >= 0 && !empty($storesArr)) {
                // 更新超市的group_id
                foreach ($storesArr as $up) {
                    $customerInfo = LeCustomers::find()->where(['entity_id' => $up, 'city' => $city, 'status' => 1])->one();
                    if (!$customerInfo) {
                        throw new ContractorException('超市不属于该城市或者为非注册超市', 40000);
                    }
                    $customerInfo->group_id = $group_id;
                    $customerInfo->group_time = ToolsAbstract::getDate()->date('Y-m-d H:i:s');
                    if (!$customerInfo->save()) {
                        throw new ContractorException('超市名称为:' . $customerInfo->store_name . '修改失败', 40000);
                    }
                }
            }
        }

        // 返回参数
        $responseDate['plan_group'] = ['group_id' => $group_id,
            'name' => $plan_group->getName(),];


        $response->setFrom(Tools::pb_array_filter($responseDate));
        return $response;

    }

    public
    static function request()
    {
        return new PlanGroupEditRequest();
    }

    public
    static function response()
    {
        return new PlanGroupEditResponse();
    }
}