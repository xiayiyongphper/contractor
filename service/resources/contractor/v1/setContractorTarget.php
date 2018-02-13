<?php
namespace service\resources\contractor\v1;

use common\models\contractor\ContractorTasks;
use common\models\contractor\ContractorTasksLog;
use common\models\contractor\ContractorMetrics;
use common\models\LeContractor;
use common\models\ContractorAuthAssignment;
use service\components\Tools;
use service\message\contractor\SetContractorTargetRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use framework\components\Date;
use framework\components\ToolsAbstract;
use framework\components\es\Console;

/**
 * Created by PhpStorm.
 * User: hongliang
 * Date: 17-07-18
 * Time: 上午11:43
 */

/**
 * Class setContractorTarget
 * 设置业务员指标
 * @package service\resources\contractor\v1
 */
class setContractorTarget extends Contractor
{
    public function run($data)
    {
        /** @var SetContractorTargetRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
        $chosen_contractor_id = $request->getChosenContractorId();
        $month = $request->getMonth();

        if(!$chosen_contractor_id || !$month){
            ContractorException::invalidParam();
        }

        $date = new Date();
        $now = $date->timestamp();
        $cur_month = intval(date("Ym",$now));
        $datetime = date("Ymd", $now); //当前年月日
        $month8 = $month . "08";  //当前年月8号

        $city_arr = array_filter(explode('|',$contractor->city_list));


        //鉴权，只有城市经理和总办可以查看数据
        if(!in_array($contractor->role,[self::MANAGER_CONTRACTOR,self::CEO_MANAGER])){
            ContractorException::noPermission();
        }

        if($month < $cur_month){
            throw new ContractorException(($month % 100)."月份已过，无法设置目标", ContractorException::PASSED_MONTH_NOT_EDITABLE);
        }

        //要操作的业务员
        $chosen_contractor = LeContractor::find()
            ->select([
                LeContractor::tableName().'.entity_id',
                LeContractor::tableName().'.name',
                LeContractor::tableName().'.city',
                LeContractor::tableName().'.status',
                'a.item_name'
            ])
            ->where(['entity_id' => $chosen_contractor_id,'status' => LeContractor::CONTRACTOR_STATUS_NORMAL])
            ->leftJoin(['a' => ContractorAuthAssignment::tableName()], "a.user_id=".LeContractor::tableName().".entity_id")
            ->asArray()->one();
        Tools::log($chosen_contractor,'contractor_target.log');

        $roles = [self::COMMON_CONTRACTOR,self::SUPPLY_CHAIN];//有效的角色
        if(empty($chosen_contractor) || !in_array($chosen_contractor['city'],$city_arr) || !in_array($chosen_contractor['item_name'],$roles)){
            ContractorException::noPermission();
        }

        //城市经理只能编辑已设置指标且时间未超过8号的业务员指标
        if($contractor->role == self::MANAGER_CONTRACTOR && $datetime > $month8){
            throw new ContractorException("已超过8当月号，不可以设置目标", ContractorException::NO_PERMISSION);
        }

        //至少设置一个指标
        if(empty($request->getContractorTarget())){
            ContractorException::setAtLeastOneTask();
        }

        //不能设置重复的指标
        $chosen_metric_ids = [];
        foreach ($request->getContractorTarget() as $item){
            if(in_array($item->getMetricId(),$chosen_metric_ids)){
                ContractorException::invalidParam();
            }else{
                $chosen_metric_ids []= $item->getMetricId();
            }
        }

        //所有的指标id
        $metric_ids = ContractorMetrics::getAllMetricsIds();

        //删除所有已设置的指标
        ContractorTasks::deleteAll(['owner_id' => $chosen_contractor_id,'city' => $chosen_contractor['city'],'month' => $month,'owner_type' => ContractorTasks::OWNER_TYPE_CONTRACTOR]);

        //设置指标
        foreach ($request->getContractorTarget() as $item){
            if(empty($item->getTargetValue()) || empty($item->getMetricId())){
                ContractorException::invalidParam();
            }

            //校验指标id是否有效
            if(!in_array($item->getMetricId(),$metric_ids)){
                ContractorException::invalidParam();
            }

            $taskModel = new ContractorTasks();
            $taskModel->metric_id = $item->getMetricId();
            $taskModel->base_value = 0;
            $taskModel->target_value = $item->getTargetValue();
            $taskModel->perfect_value = 0;
            $taskModel->owner_id = $chosen_contractor_id;
            $taskModel->city = $chosen_contractor['city'];
            $taskModel->month = $month;
            $taskModel->owner_type = ContractorTasks::OWNER_TYPE_CONTRACTOR;
            $taskModel->created_at = $date->date();
            $taskModel->updated_at = $date->date();
            $taskModel->save();
            $errors = $taskModel->getErrors();
            if (count($errors) > 0) {
                Console::get()->log($errors, $this->getTraceId(), [__METHOD__], Console::ES_LEVEL_WARNING);
            }

            //操作记录
            $logModel = new ContractorTasksLog();
            $logModel->operate_id = $contractor->entity_id;
            $logModel->contractor_id = $chosen_contractor_id;
            $logModel->metric_id = $item->getMetricId();
            $logModel->value = $item->getTargetValue();
            $logModel->created_at = $date->date();
            $logModel->save();
        }

        return true;
    }

    public static function request()
    {
        return new SetContractorTargetRequest();
    }

    public static function response()
    {
        return true;
    }
}