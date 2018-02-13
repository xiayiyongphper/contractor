<?php

namespace service\resources\contractor\v1;

use common\models\contractor\ContractorTasks;
use common\models\contractor\ContractorMetrics;
use common\models\LeContractor;
use common\models\ContractorAuthAssignment;
use service\components\Tools;
use service\message\contractor\GetWholeTargetRequest;
use service\message\contractor\GetWholeTargetResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use framework\components\Date;
use framework\components\ToolsAbstract;

/**
 * Created by PhpStorm.
 * User: hongliang
 * Date: 17-07-18
 * Time: 上午11:43
 */

/**
 * Class getWholeTarget
 * 获取城市和业务员所有指标
 * @package service\resources\contractor\v1
 */
class getWholeTarget extends Contractor
{
    public function run($data)
    {
        /** @var GetWholeTargetRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);
        $city = $request->getCity();
        $month = $request->getMonth();

        $date = new Date();
        $now = $date->timestamp();
        $datetime = date("Ymd", $now);
        $month8 = $month . "08";
        $cur_month = intval(date("Ym", $now));

//        Tools::log('datetime:' . $datetime, 'getWholeTarget.log');
//        Tools::log('month8:' . $month8, 'getWholeTarget.log');
//        Tools::log('month:' . $month, 'getWholeTarget.log');
//        Tools::log('cur_month:' . $cur_month, 'getWholeTarget.log');

        //鉴权，只有城市经理和总办可以查看数据
        if (!in_array($contractor->role, [self::MANAGER_CONTRACTOR, self::CEO_MANAGER])) {
            ContractorException::noPermission();
        }

        //校验查询的城市是否是自己管辖的城市
        $city_arr = array_filter(explode('|', $contractor->city_list));
        if (!in_array($city, $city_arr)) {
            ContractorException::noPermission();
        }

        if (!$city || !$month) {
            ContractorException::invalidParam();
        }

        //指标map
        $metrics = ContractorMetrics::getMetricsMap();

        //城市指标
        $city_target = array(
            'city' => $city,
            'editable' => 0,
            'addable' => 0
        );
        $city_task_list = ContractorTasks::getCityTask($city, $month);
        foreach ($city_task_list as $item) {
            $city_target['target_list'] [] = array(
                'metric_id' => $item->metric_id,
                'metric' => isset($metrics[$item->metric_id]) ? $metrics[$item->metric_id] : '',
                'base_value' => $item->base_value,
                'target_value' => $item->target_value,
                'perfect_value' => $item->perfect_value
            );
        }
        if ($contractor->role == self::CEO_MANAGER && $month >= $cur_month && !empty($city_target['target_list'])) {
            $city_target['editable'] = 1;
        }
        if ($contractor->role == self::CEO_MANAGER) {
            $city_target['addable'] = 1;
        }

        //业务员指标
        $contractor_targets = [];
        $contractor_ids = [];
        $summary_data = [];
        //$contractor_set_time = [];//业务员指标设置时间map,用于判断该业务员该月指标对城市经理是否可编辑
        $contractor_task_list = ContractorTasks::getContractorsTaskByCity($city, $month);
        foreach ($contractor_task_list as $item) {
            $contractor_targets[$item->owner_id]['target_list'][] = array(
                'metric_id' => $item->metric_id,
                'metric' => isset($metrics[$item->metric_id]) ? $metrics[$item->metric_id] : '',
                'target_value' => $item->target_value,
            );
            $contractor_ids [] = $item->owner_id;
            //统计指标
            if (isset($summary_data[$item->metric_id])) {
                $summary_data[$item->metric_id]['value'] += $item->target_value;
            } else {
                $summary_data[$item->metric_id]['key'] = isset($metrics[$item->metric_id]) ? $metrics[$item->metric_id] : '';
                $summary_data[$item->metric_id]['value'] = $item->target_value;
            }

        }

        //查出该城市有效的业务员，同时包括已经设置了指标但是中途换了城市或停用或改了角色的业务员，这类业务员需要展示设置过的指标，但是不能编辑和新增
        $roles = [self::COMMON_CONTRACTOR, self::SUPPLY_CHAIN];
        $contractors = LeContractor::find()
            ->select([
                LeContractor::tableName() . '.entity_id',
                LeContractor::tableName() . '.name',
                LeContractor::tableName() . '.city',
                LeContractor::tableName() . '.status',
                'a.item_name'
            ])
            ->where([
                'or',
                [
                    LeContractor::tableName() . '.city' => $city,
                    LeContractor::tableName() . '.status' => LeContractor::CONTRACTOR_STATUS_NORMAL,
                    'a.item_name' => $roles
                ],
                ['in', 'entity_id', $contractor_ids]
            ])
            ->leftJoin(['a' => ContractorAuthAssignment::tableName()], "a.user_id=" . LeContractor::tableName() . ".entity_id")
            ->asArray()->all();


        foreach ($contractors as $item) {
            $contractor_targets[$item['entity_id']]['contractor_id'] = $item['entity_id'];
            $contractor_targets[$item['entity_id']]['contractor_name'] = $item['name'];
            $contractor_targets[$item['entity_id']]['editable'] = 1;
            $contractor_targets[$item['entity_id']]['addable'] = 1;

            //总办只能编辑当前月或未来月的业务员指标
            if ($contractor->role == self::CEO_MANAGER) {//总办只能编辑已设置且当前月或未来月的业务员指标
                if ($month < $cur_month || empty($contractor_targets[$item['entity_id']]['target_list'])) {
                    $contractor_targets[$item['entity_id']]['editable'] = 0;
                }
            } else {
                //城市经理只能编辑已设置指标且时间未超过8号的业务员指标
                if (($contractor->role == self::MANAGER_CONTRACTOR && $datetime > $month8) ||
                    empty($contractor_targets[$item['entity_id']]['target_list'])) {
                    $contractor_targets[$item['entity_id']]['editable'] = 0;
                }
            }

            //城市、状态、角色，任意一个不符合条件，即不可编辑和新增
            if ($item['city'] != $city || $item['status'] != LeContractor::CONTRACTOR_STATUS_NORMAL || !in_array($item['item_name'], $roles)) {
                $contractor_targets[$item['entity_id']]['editable'] = 0;
                $contractor_targets[$item['entity_id']]['addable'] = 0;
            }
        }
        //$contractor_targets = ksort($contractor_targets);

        $month_str = floor($month / 100) . '-' . substr($month, -2);

        $responseData = array(
            'month' => $month_str,
            'city_target' => $city_target,
            'contractor_target' => $contractor_targets,
            'summary_data' => $summary_data,
        );

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new GetWholeTargetRequest();
    }

    public static function response()
    {
        return new GetWholeTargetResponse();
    }
}