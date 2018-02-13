<?php
namespace service\resources\contractor\v1;

use common\models\contractor\ContractorTasks;
use common\models\contractor\ContractorMetrics;
use service\components\Tools;
use service\message\contractor\GetWholeTargetRequest;
use service\message\contractor\GetCityTargetResponse;
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
 * Class getCityTarget
 * 获取城市所有指标
 * @package service\resources\contractor\v1
 */
class getCityTarget extends Contractor
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
        //Tools::log(date("Y-m-d H:i:s",$now),'city_target.log');
        $cur_month = intval(date("Ym",$now));

        //鉴权，只有总办可以查看数据
        if(!in_array($contractor->role,[self::CEO_MANAGER])){
            ContractorException::noPermission();
        }

        if(!$city){
            ContractorException::invalidParam();
        }

        //有month是编辑，只有当前和未来的月份可以编辑
        if($month && $month < $cur_month){
            ContractorException::noPermission();
        }

        //校验查询的城市是否是自己管辖的城市
        $city_arr = array_filter(explode('|',$contractor->city_list));
        if(!in_array($city,$city_arr)){
            ContractorException::noPermission();
        }

        //所有的指标
        $metric_list = ContractorMetrics::find()->where(['>','type',0])->all();
        //Tools::log($metric_list,'city_target.log');
        $city_target = [];
        foreach ($metric_list as $item){
            $city_target[$item['entity_id']] = array(
                'metric_id' => $item['entity_id'],
                'metric' => $item['name'],
                'is_set' => 0,
            );
        }

        if($month){
            //是编辑，要查出城市已设置的指标的值
            $city_task_list = ContractorTasks::getCityTask($city,$month);
            if(empty($city_task_list)){
                ContractorException::noPermission();
            }

            foreach ($city_task_list as $item){
                $city_target[$item->metric_id]['base_value'] = floatval($item->base_value);
                $city_target[$item->metric_id]['target_value'] = $item->target_value;
                $city_target[$item->metric_id]['perfect_value'] = $item->perfect_value;
                $city_target[$item->metric_id]['is_set'] = 1;
            }

            $res_month = floor($month/100).'-'.substr($month,-2);
        }else{
            //是新增，要找出最近未设置的月份
            $set_month = ContractorTasks::find()
                ->select('distinct(month)')
                ->where(['city' => $city,'owner_type' => ContractorTasks::OWNER_TYPE_CITY])
                ->andWhere(['>=','month',$cur_month])
                ->asArray()
                ->all();
            Tools::log($set_month,'city_target.log');

            $set_month_list = [];
            foreach ($set_month as $item){
                $set_month_list []= intval($item['month']);
            }

            $res_month = $cur_month;
            while (in_array($res_month,$set_month_list)){
                $res_month = date("Ym",strtotime('+1 month',strtotime($res_month.'01')));
            }
            $res_month = floor($res_month/100).'-'.substr($res_month,-2);
        }

        $responseData = array(
            'month' => $res_month,
            'city_target' => $city_target
        );

        //Tools::log($responseData, 'city_target.log');
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new GetWholeTargetRequest();
    }

    public static function response()
    {
        return new GetCityTargetResponse();
    }
}