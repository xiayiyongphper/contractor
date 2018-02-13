<?php
/**
 * 供货商综合得分规则
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/25
 * Time: 11:22
 */

namespace service\tasks;

use framework\components\ToolsAbstract;
use framework\tasks\TaskAbstract;
use framework\components\Date;
use yii\db\Expression;
use service\components\Tools;
use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTasks;
use common\models\contractor\ContractorTaskHistory;
use common\components\UserTools;
use framework\db\readonly\models\core\SalesFlatOrder;

class monthOrderCustomer extends TaskAbstract
{
    public function run($data)
    {
        //获取指标id
        $metric_id = ContractorMetrics::getMetricIdByIdentifier(ContractorMetrics::METRIC_IDENTIFIER_MONTH_ORDER_CUSTOMER_COUNT);
        if (!$metric_id) {
            $this->log('invalid metric_identifier');
            return;
        }
        //$this->log($metric_id,'monthOrderCustomer.log');

        $date = new Date();
        $today = $date->date("Y-m-d");
        $month = intval($date->date("Ym"));
        $last_month = intval(date("Ym", strtotime("-1 month", $date->timestamp())));
        $eight_days_ago = date("Y-m-d", strtotime('-8 day', $date->timestamp()));//8天前
        $across_month_flag = intval(date("Ym", strtotime($eight_days_ago))) == $last_month ? true : false;

        //-----------------如果跨月，要算上个月的-----------------------
        if ($across_month_flag) {
            $start_date = date("Y-m-01", strtotime("-1 month", $date->timestamp()));//上月1号
            $end_date = date("Y-m-d", strtotime($date->date("Y-m-01")) - 24 * 3600);//上月最后一天
            $order_customer_data = $this->getMonthOrderCustomerCount($start_date, $end_date);
            //$this->log($order_customer_data,'monthOrderCustomer.log');

            //只更新value大于0的数据
            foreach ($order_customer_data as $item){
                $this->updateHistory($metric_id, $item['contractor_id'], $item['city'], $item['date'], $item['count']);
            }
        }

        //-----------------------------本月----------------------------
        $start_date = date("Y-m-01", $date->timestamp());//本月1号
        $order_customer_data = $this->getMonthOrderCustomerCount($start_date);
        //$this->log($order_customer_data,'monthOrderCustomer.log');

        //只更新value大于0的数据
        foreach ($order_customer_data as $item){
            $this->updateHistory($metric_id, $item['contractor_id'], $item['city'], $item['date'], $item['count']);
        }

        $this->log('task finished');
    }

    private function getMonthOrderCustomerCount($start_date, $end_date = null)
    {
        //$data = UserTools::getMonthOrderCustomerCountByProxy($start_date, $end_date);
        //因为订单的记录的下单时间早于实际时间8小时，因此查询的时间要减8小时
        $start_date = date("Y-m-d H:i:s",(strtotime($start_date) - 8*3600));
        if($end_date){
            $end_date = date("Y-m-d H:i:s",(strtotime($end_date) + 24*3600 - 8*3600));
        }

        //子查询查出查询时间范围内，分属各个业务员的用户首单下单日期（如果用户期间换过城市，分开统计）
        $subQuery = SalesFlatOrder::find()
            ->select([new Expression("substring(addtime(min(created_at),'8:00:00'),1,10) as date"),'customer_id','contractor_id','city'])
            ->where(['>=','created_at',$start_date]);
        if($end_date){
            $subQuery->andWhere(['<','created_at',$end_date]);
        }
        $subQuery->andWhere(['>','contractor_id',0])//没有业务员的订单不要，会跟城市的冲突
        ->andWhere(['customer_tag_id' => SalesFlatOrder::CUSTOMER_TAG_ID_NORMAL])
            ->andWhere(['not in','state',SalesFlatOrder::INVALID_ORDER_STATE()])
            ->andWhere(['not in','wholesaler_id',SalesFlatOrder::excludeWholesalerIds()])
            ->andWhere(['not in','customer_id',SalesFlatOrder::excludeCustomerIds()])
            ->andWhere(['not like','wholesaler_name',['t','T','特通渠道','乐来供应链','测试']])
            ->groupBy(['customer_id','contractor_id','city']);

        //统计各业务员每天产生的下单用户数
        $query = SalesFlatOrder::find()
            ->select([new Expression("count(*) as count"),'date','contractor_id','city'])
            ->from(['a' => $subQuery])
            ->groupBy(['date','contractor_id','city']);
        $this->log($query->createCommand()->getRawSql());

        $data_contractor = $query->asArray()->all();
        //$this->log($data_contractor,'monthOrderCustomerCount.log');


        //城市的统计逻辑与业务员类似
        $subQuery = SalesFlatOrder::find()
            ->select([new Expression("substring(addtime(min(created_at),'8:00:00'),1,10) as date"),'customer_id','city'])
            ->where(['>=','created_at',$start_date]);
        if($end_date){
            $subQuery->andWhere(['<','created_at',$end_date]);
        }
        $subQuery->andWhere(['customer_tag_id' => SalesFlatOrder::CUSTOMER_TAG_ID_NORMAL])
            ->andWhere(['not in','state',SalesFlatOrder::INVALID_ORDER_STATE()])
            ->andWhere(['not in','wholesaler_id',SalesFlatOrder::excludeWholesalerIds()])
            ->andWhere(['not in','customer_id',SalesFlatOrder::excludeCustomerIds()])
            ->andWhere(['not like','wholesaler_name',['t','T','特通渠道','乐来供应链','测试']])
            ->groupBy(['customer_id','city']);

        $query = SalesFlatOrder::find()
            ->select([new Expression("count(*) as count"),'date','city'])
            ->from(['a' => $subQuery])
            ->groupBy(['date','city']);
        $this->log($query->createCommand()->getRawSql());

        $data_city = $query->asArray()->all();
        //城市的数据，contractor_id=0
        foreach ($data_city as $k=> $v){
            $data_city[$k]['contractor_id'] = 0;
        }
        //合并城市和业务员的统计结果
        $data = array_merge($data_city,$data_contractor);
        //$this->log($data,'monthOrderCustomerCount.log');

        return $data;
    }

    private function updateHistory($metric_id, $owner_id, $city, $date, $value)
    {
        $history = ContractorTaskHistory::findOne(['metric_id' => $metric_id, 'owner_id' => $owner_id, 'city' => $city, 'date' => $date]);
        //$this->log($history,'monthOrderCustomer.log');
        if (!$history) {
            $history = new ContractorTaskHistory();
        }

        $date_obj = new Date();
        $history->metric_id = $metric_id;
        $history->owner_id = $owner_id;
        $history->city = $city;
        $history->date = $date;
        $history->value = $value;
        $history->updated_at = $date_obj->date();
        //$this->log($history->toArray(),'monthOrderCustomer.log');
        $history->save();
        if (!empty($history->getErrors())) {
            $this->log($history->getErrors());
        }
    }

    protected function getLogFileName()
    {
        if (!$this->logFileName) {
            $this->logFileName = $this->getDefaultLogFileName();
        }
        return $this->logFileName;
    }
}