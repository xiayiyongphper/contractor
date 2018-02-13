<?php

namespace console\controllers;


use framework\components\ToolsAbstract;
use yii\console\Controller;


/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/2/1
 * Time: 14:40
 */
class IndexController extends Controller
{

    public function actionIndex()
    {

        //今天，昨天，前天，上周，昨天的上周
        $monthStart = ToolsAbstract::getDate()->date('Y-m-01'); //本月1号
        $today = ToolsAbstract::getDate()->date('Y-m-d'); //今天
        $yesterday = ToolsAbstract::getDate()->date('Y-m-d', strtotime('-1 day')); //昨天
        $dayBeforeYesterday = ToolsAbstract::getDate()->date('Y-m-d', strtotime('-2 day')); //前天
        $lastWeek = ToolsAbstract::getDate()->date('Y-m-d', strtotime('-7 day')); //上周
        $yesterdayLastWeek = ToolsAbstract::getDate()->date('Y-m-d', strtotime('-8 day')); //昨天的上周

        $metricIds = [1];
        foreach ($metricIds as $metricId) {
            $select = <<<SQL
SELECT month_data.month_data,today_data.today_data,yesterday_data.yesterday_data,day_before_yesterday_data.day_before_yesterday_data,last_week_data.last_week_data,
yesterday_last_week_data.yesterday_last_week_data from 
(SELECT SUM(`value`) as month_data FROM contractor_task_history WHERE `date` >= '$monthStart' and `metric_id` = $metricId) as month_data, 
(SELECT SUM(`value`) as today_data FROM contractor_task_history WHERE `date` = '$today'  and `metric_id` = $metricId) as today_data,
(SELECT SUM(`value`) as yesterday_data FROM contractor_task_history WHERE `date` = '$yesterday'  and `metric_id` = $metricId) as yesterday_data,
(SELECT SUM(`value`) as day_before_yesterday_data FROM contractor_task_history WHERE `date` = '$dayBeforeYesterday'  and `metric_id` = $metricId) as day_before_yesterday_data,
(SELECT SUM(`value`) as last_week_data FROM contractor_task_history WHERE `date` = '$lastWeek'  and `metric_id` = $metricId) as last_week_data,
(SELECT SUM(`value`) as yesterday_last_week_data FROM contractor_task_history WHERE `date` = '$yesterdayLastWeek'  and `metric_id` = $metricId) as yesterday_last_week_data
SQL;
            /** @var \yii\db\Connection $mainDb */
            $mainDb = \Yii::$app->mainDb;
            $data = $mainDb->createCommand($select)->queryOne();
            print_r($data);
        }
    }

    public function actionOrderIdDecode()
    {
        echo 123;
    }

    public function actionSelect()
    {
        $select = 'select
a.contractor_id,
a.sales_total as sales_total_a,
b.sales_total as sales_total_b,
c.sales_total as sales_total_c,
ifnull((a.sales_total-b.sales_total)/b.sales_total,0) as sales_total_tongbi,
ifnull((a.sales_total-c.sales_total)/c.sales_total,0) as sales_total_huanbi,
a.first_users as first_users_a,
b.first_users as first_users_b,
c.first_users as first_users_c,
ifnull((a.first_users-b.first_users)/b.first_users,0) as first_users_tongbi,
ifnull((a.first_users-c.first_users)/c.first_users,0) as first_users_huanbi,
a.orders_count as orders_count_a,
b.orders_count as orders_count_b,
c.orders_count as orders_count_c,
ifnull((a.orders_count-b.orders_count)/b.orders_count,0) as orders_count_tongbi,
ifnull((a.orders_count-c.orders_count)/c.orders_count,0) as orders_count_huanbi
from
(select date,contractor_id,sum(sales_total) as sales_total,sum(first_users) as first_users,sum(orders_count) as orders_count from lelai_slim_customer.contractor_statistics_data where date=CURRENT_DATE group by contractor_id) a
left join
(select date,contractor_id,sum(sales_total) as sales_total,sum(first_users) as first_users,sum(orders_count) as orders_count from lelai_slim_customer.contractor_statistics_data where date=DATE_SUB(CURRENT_DATE,INTERVAL 1 DAY) 
	group by contractor_id) b on a.contractor_id=b.contractor_id
left join
(select date,contractor_id,sum(sales_total) as sales_total,sum(first_users) as first_users,sum(orders_count) as orders_count from lelai_slim_customer.contractor_statistics_data where date=DATE_SUB(CURRENT_DATE,INTERVAL 7 DAY) 
	group by contractor_id) c on a.contractor_id=c.contractor_id';

        /** @var \yii\db\Connection $mainDb */
        $mainDb = \Yii::$app->mainDb;
        $store_manager_schema = $mainDb->createCommand($select)->queryOne();
        print_r($store_manager_schema);
    }


}
