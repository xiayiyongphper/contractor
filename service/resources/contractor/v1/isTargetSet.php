<?php
namespace service\resources\contractor\v1;

use common\models\contractor\ContractorTasks;
use common\models\contractor\ContractorMetrics;
use common\models\LeContractor;
use common\models\ContractorAuthAssignment;
use service\components\Tools;
use service\message\contractor\IsTargetSetRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use framework\components\Date;

/**
 * Created by PhpStorm.
 * User: hongliang
 * Date: 17-07-18
 * Time: 上午11:43
 */

/**
 * Class getWholeTarget
 * 校验城市或业务员某月指标是否设置
 * @package service\resources\contractor\v1
 */
class isTargetSet extends Contractor
{
    public function run($data)
    {
        /** @var IsTargetSetRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
        $type = $request->getType();
        $month = $request->getMonth();
        $city = $request->getCity();
        $chosen_contractor_id = $request->getChosenContractorId();

        $date = new Date();
        $now = $date->timestamp();
        $cur_month = intval(date("Ym",$now));

        if(!in_array($type,[1,2]) || !$month || ($type == 1 && !$city) || ($type == 2 && !$chosen_contractor_id)){
            ContractorException::invalidParam();
        }

        //鉴权，总办可以校验城市，总办和城市经理可以校验业务员
        if(($type == 1 && !in_array($contractor->role,[self::CEO_MANAGER])) || ($type == 2 && !in_array($contractor->role,[self::CEO_MANAGER,self::MANAGER_CONTRACTOR]))){
            ContractorException::noPermission();
        }

        //管辖的城市
        $city_arr = array_filter(explode('|',$contractor->city_list));

        //有month是编辑，只有当前和未来的月份可以新增
        if($month < $cur_month){
            throw new ContractorException(($month % 100)."月份已过，无法设置目标", ContractorException::PASSED_MONTH_NOT_EDITABLE);
        }

        if($type == 1){
            if(!in_array($city,$city_arr)){
                ContractorException::noPermission();
            }

            $city_task_list = ContractorTasks::getCityTask($city,$month);
            if(!empty($city_task_list)){
                throw new ContractorException(($month % 100)."月已设置目标，不可重复设置", ContractorException::TARGET_ALREADY_SET);
            }

        }else{
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

            $roles = [self::COMMON_CONTRACTOR,self::SUPPLY_CHAIN];//有效的角色
            if(empty($chosen_contractor) || !in_array($chosen_contractor['city'],$city_arr) || !in_array($chosen_contractor['item_name'],$roles)){
                ContractorException::noPermission();
            }

            $contractor_task_list = ContractorTasks::getContractorTask($chosen_contractor_id,$month,$chosen_contractor['city']);
            if(!empty($contractor_task_list)){
                throw new ContractorException(($month % 100)."月已设置目标，不可重复设置", ContractorException::TARGET_ALREADY_SET);
            }
        }

        return true;
    }

    public static function request()
    {
        return new IsTargetSetRequest();
    }

    public static function response()
    {
        return true;
    }
}