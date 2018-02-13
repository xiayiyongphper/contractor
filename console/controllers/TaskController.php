<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:30
 */

namespace console\controllers;

use framework\components\ToolsAbstract;
use service\tasks\freshOrderGmv;
use service\tasks\initialTaskHistory;
use yii\console\Controller;

class TaskController extends Controller
{
    /**
     * ManageCrontab
     */
    public function actionCrontab()
    {
        $redis = ToolsAbstract::getRedis();
        $timer_key = ToolsAbstract::getCrontabKey();
        $redis->del($timer_key);
        $files = [
            'assignMarkProductPriceTask',
            'dau',
            'firstOrderCustomer',
            'freshOrderGmv',
            'initialTaskHistory',
            'monthOrderCustomer',
            'orderCountStatistics',
            'StoreAverageGMV',
        ];
        foreach ($files as $file) {
            $route = 'task.' . $file;
            $data = [
                'type' => 2,
                'time' => '0 0 * * *',
                'data' => [
                    'route' => $route,
                    'params' => [
                    ],
                ]
            ];
            $json = json_encode($data);
            $redis->hSet($timer_key, $route, $json);
        }
        //$redis->sRem($timer_key, json_encode($data1));    //del
        $list = $redis->hGetAll($timer_key);
        echo print_r($list, true);
    }

    public function actionFreshGmv()
    {
        $model = new freshOrderGmv();
        $model->run();
    }

    public function actionInitTaskHistory()
    {
        (new initialTaskHistory())->run('');
    }
}