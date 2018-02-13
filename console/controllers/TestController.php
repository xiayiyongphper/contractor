<?php

namespace console\controllers;


use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTaskHistory;
use common\models\contractor\ContractorTasks;
use common\models\LeContractor;
use framework\components\Date;
use framework\components\ToolsAbstract;
use framework\db\readonly\models\core\SalesFlatOrder;
use yii\console\Controller;
use yii\db\Expression;


/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/2/1
 * Time: 14:40
 */
class TestController extends Controller
{


    public function actionIndex()
    {
        /** @var SalesFlatOrder $order */
        $order = SalesFlatOrder::find()->where(['customer_id' => 33313])
            ->andWhere(['in', 'status', SalesFlatOrder::VALID_ORDER_STATUS()])
            ->andWhere(['not in', 'state', SalesFlatOrder::INVALID_ORDER_STATE()])
            ->orderBy('entity_id desc')->one();
        print_r($order->entity_id);
    }
}
