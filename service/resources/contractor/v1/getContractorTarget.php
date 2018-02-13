<?php

namespace service\resources\contractor\v1;

use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTasks;
use common\models\ContractorAuthAssignment;
use common\models\LeContractor;
use framework\components\Date;
use service\components\Tools;
use service\message\contractor\GetContractorTargetRequest;
use service\message\contractor\GetContractorTargetResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Created by PhpStorm.
 * User: hongliang
 * Date: 17-07-18
 * Time: 上午11:43
 */

/**
 * Class getContractorTarget
 * 获取业务员所有指标
 * @package service\resources\contractor\v1
 */
class getContractorTarget extends Contractor
{
    public function run($data)
    {
        /** @var GetContractorTargetRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);
        $chosen_contractor_id = $request->getChosenContractorId();
        $month = $request->getMonth();

        if (!$chosen_contractor_id) {
            ContractorException::invalidParam();
        }

        $date = new Date();
        $now = $date->timestamp();
        $cur_month = intval(date("Ym", $now));
        $datetime = date("Ymd", $now); //当前年月日
        $month8 = $month . "08";  //当前年月8号

        $city_arr = array_filter(explode('|', $contractor->city_list));


        //鉴权，只有城市经理和总办可以查看数据
        if (!in_array($contractor->role, [self::MANAGER_CONTRACTOR, self::CEO_MANAGER])) {
            ContractorException::noPermission();
        }

        //要操作的业务员
        $chosen_contractor = LeContractor::find()
            ->select([
                LeContractor::tableName() . '.entity_id',
                LeContractor::tableName() . '.name',
                LeContractor::tableName() . '.city',
                LeContractor::tableName() . '.status',
                'a.item_name'
            ])
            ->where(['entity_id' => $chosen_contractor_id, 'status' => LeContractor::CONTRACTOR_STATUS_NORMAL])
            ->leftJoin(['a' => ContractorAuthAssignment::tableName()], "a.user_id=" . LeContractor::tableName() . ".entity_id")
            ->asArray()->one();
        Tools::log($chosen_contractor, 'contractor_target.log');

        $roles = [self::COMMON_CONTRACTOR, self::SUPPLY_CHAIN];//有效的角色
        if (empty($chosen_contractor) || !in_array($chosen_contractor['city'], $city_arr) || !in_array($chosen_contractor['item_name'], $roles)) {
            ContractorException::noPermission();
        }

        //所有的指标
        $metric_list = ContractorMetrics::find()->where(['>', 'type', 0])->all();
        $contractor_targets = [];
        foreach ($metric_list as $item) {
            $contractor_targets[$item['entity_id']] = array(
                'metric_id' => $item['entity_id'],
                'metric' => $item['name'],
                'is_set' => 0,
            );
        }

        if ($month) {//有月份，是编辑
            //业务员指标
            $contractor_task_list = ContractorTasks::getContractorTask($chosen_contractor_id, $month, $chosen_contractor['city']);

            //如果没有查到指标，说明此业务员该月还没有设置，不能编辑
            if (empty($contractor_task_list)) {
                ContractorException::noPermission();
            }

            foreach ($contractor_task_list as $item) {
                $contractor_targets[$item->metric_id]['target_value'] = $item->target_value;
                $contractor_targets[$item->metric_id]['is_set'] = 1;
            }

            if ($month < $cur_month) {
                ContractorException::noPermission();
            }

            //总办只能编辑已设置且当前月或未来月的业务员指标
            //城市经理只能编辑已设置指标且时间未超过8号的业务员指标
            if($contractor->role == self::CEO_MANAGER){
                //可以修改
            }else if($contractor->role == self::MANAGER_CONTRACTOR && $datetime <= $month8){
                //可以修改
            }else{
                ContractorException::noPermission();
            }

            $res_month = floor($month / 100) . '-' . substr($month, -2);
        } else {
            //是新增，要找出最近未设置的月份
            $set_month = ContractorTasks::find()
                ->select('distinct(month)')
                ->where(['owner_id' => $chosen_contractor_id, 'owner_type' => ContractorTasks::OWNER_TYPE_CONTRACTOR])
                ->andWhere(['>=', 'month', $cur_month])
                ->asArray()
                ->all();
            Tools::log($set_month, 'contractor_target.log');

            $set_month_list = [];
            foreach ($set_month as $item) {
                $set_month_list [] = intval($item['month']);
            }

            $res_month = $cur_month;
            while (in_array($res_month, $set_month_list)) {
                $res_month = date("Ym", strtotime('+1 month', strtotime($res_month . '01')));
            }
            $res_month = floor($res_month / 100) . '-' . substr($res_month, -2);
        }

        $responseData = array(
            'month' => $res_month,
            'contractor_target' => $contractor_targets
        );

        Tools::log($responseData, 'whole_target.log');
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new GetContractorTargetRequest();
    }

    public static function response()
    {
        return new GetContractorTargetResponse();
    }
}