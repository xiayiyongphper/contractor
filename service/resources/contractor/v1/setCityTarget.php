<?php
namespace service\resources\contractor\v1;

use common\models\contractor\ContractorTasks;
use common\models\contractor\ContractorMetrics;
use common\models\LeContractor;
use common\models\ContractorAuthAssignment;
use service\components\Tools;
use framework\components\es\Console;
use service\message\contractor\SetCityTargetRequest;
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
 * Class setCityTarget
 * 设置城市所有指标
 * @package service\resources\contractor\v1
 */
class setCityTarget extends Contractor
{
    public function run($data)
    {
        /** @var SetCityTargetRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
        $city = $request->getCity();
        $month = $request->getMonth();

        $date = new Date();
        $now = $date->timestamp();
        $cur_month = intval(date("Ym",$now));

        //鉴权，只有总办可以设置城市指标
        if(!in_array($contractor->role,[self::CEO_MANAGER])){
            ContractorException::noPermission();
        }

        if(!$city || !$month){
            ContractorException::invalidParam();
        }

        //只有当前和未来的月份可以设置
        if($month < $cur_month){
            ContractorException::noPermission();
        }

        //校验查询的城市是否是自己管辖的城市
        $city_arr = array_filter(explode('|',$contractor->city_list));
        if(!in_array($city,$city_arr)){
            ContractorException::noPermission();
        }

        //至少设置一个指标
        if(empty($request->getCityTarget())){
            ContractorException::setAtLeastOneTask();
        }

        //不能设置重复的指标
        $chosen_metric_ids = [];
        foreach ($request->getCityTarget() as $item){
            if(in_array($item->getMetricId(),$chosen_metric_ids)){
                ContractorException::invalidParam();
            }else{
                $chosen_metric_ids []= $item->getMetricId();
            }
        }

        //所有的指标id
        $metric_ids = ContractorMetrics::getAllMetricsIds();

        //删除所有已设置的指标
        ContractorTasks::deleteAll(['city' => $city, 'month' => $month,'owner_type' => ContractorTasks::OWNER_TYPE_CITY]);

        //设置指标
        Tools::log($request->getCityTarget(),'setCityTarget.log');
        foreach ($request->getCityTarget() as $item){
            if(empty($item->getBaseValue()) || empty($item->getTargetValue()) || empty($item->getPerfectValue()) || empty($item->getMetricId())){
                ContractorException::invalidParam();
            }

            //校验指标id是否有效
            if(!in_array($item->getMetricId(),$metric_ids)){
                ContractorException::invalidParam();
            }

            $taskModel = new ContractorTasks();
            $taskModel->metric_id = $item->getMetricId();
            $taskModel->base_value = $item->getBaseValue();
            $taskModel->target_value = $item->getTargetValue();
            $taskModel->perfect_value = $item->getPerfectValue();
            $taskModel->owner_id = 0;
            $taskModel->city = $city;
            $taskModel->month = $month;
            $taskModel->owner_type = ContractorTasks::OWNER_TYPE_CITY;
            $taskModel->created_at = $date->date();
            $taskModel->updated_at = $date->date();
            $taskModel->save();
            $errors = $taskModel->getErrors();
            if (count($errors) > 0) {
                Console::get()->log($errors, $this->getTraceId(), [__METHOD__], Console::ES_LEVEL_WARNING);
            }
        }

        return true;
    }

    public static function request()
    {
        return new SetCityTargetRequest();
    }

    public static function response()
    {
        return true;
    }
}