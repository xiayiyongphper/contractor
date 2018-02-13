<?php

namespace service\resources\contractor\v1;

use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\RegionArea;
use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\components\Tools;
use service\message\contractor\StoresListRequest;
use service\message\contractor\StoresResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use yii\db\Expression;
use yii\db\Query;

/**
 * Class storeList
 * 首页指定业务员任务超市列表
 * @package service\resources\contractor\v1
 */
class taskStoreList extends Contractor
{

    const PAGE_SIZE = 40;

    /**
     * 入口
     *
     * @param string $data
     * @return StoresResponse
     * @throws ContractorException
     */
    public function run($data)
    {
        /** @var StoresListRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
        /* 验证contractor */
        $cityArr = array_filter(explode('|', $contractor->city_list));
        if (!$cityArr) {
            ContractorException::contractorCityListEmpty();
        }

        if (!$contractor) {
            ContractorException::contractorInitError();
        }

        /* 验证城市参数 */
        if (!in_array((string)$request->getCity(), $cityArr)) {
            throw new ContractorException('不在该城市管理范围', 401);
        }

        /* 验证权限？？？？ */

//        if (!ContractorPermission::contractorReviewStoreListPermission($this->role_permission)) {
//            ContractorException::contractorPermissionError();
//        }

        $response = $this->response();

        $respData = $this->getResponseData($request, $contractor);
        if (!$respData) {
            return $response;
        }
//        throw new ContractorException(var_export($data, 1), 111);

        $response->setFrom(Tools::pb_array_filter($respData));
        return $response;
    }

    /**
     * 返回回应数据
     *
     * @param StoresListRequest $request
     * @param LeContractor $contractor
     * @return array | boolean
     * @throws ContractorException
     */
    private function getResponseData(StoresListRequest $request, LeContractor $contractor)
    {
        $lng = $request->getLng() ?: 0;
        $lat = $request->getLat() ?: 0;
        $distanceExp = new Expression('(ROUND(6378.138*2*ASIN(SQRT(POW(SIN(('
            . $lat . '*PI()/180-`lat`*PI()/180)/2),2)+COS('
            . $lat . '*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(('
            . $lng . '*PI()/180-`lng`*PI()/180)/2),2)))*1000)) as distance');
        $select = ['c.entity_id', 'province', 'city', 'district', 'area_id', 'address', 'detail_address', $distanceExp,
            'store_name', 'store_front_img', 'lat', 'lng', 'phone', 'contractor_id', 'contractor',
            'created_at', 'type', 'level', 'disabled', 'last_visited_at', 'last_place_order_at', 'last_place_order_total'];

        // 排序，1：距离从近到远排序
        $order = null;
        if ($request->getSort() == 1) {
            $order = 'distance asc';
        } else if ($request->getSort() == 2) {
            $order = 'last_visited_at desc';
        } else if ($request->getSort() == 3) {
            $order = 'last_visited_at asc';
        } else if ($request->getSort() == 4) {
            $order = 'last_place_order_at desc';
        } else if ($request->getSort() == 5) {
            $order = 'last_place_order_at asc';
        }

        $query = LeCustomers::find()->alias('c')->select($select)
            ->leftJoin('lelai_slim_customer.contractor_visit_task as t', 'c.entity_id = t.customer_id');
        if (!$this->setWhereCond($query, $request,$contractor)) {
            return false;
        }

        /* 分页参数 */
        $totalCount = $query->count('*', LeCustomers::getDb());
        $paginationReq = $request->getPagination();
        if ($paginationReq) {
            $page = $paginationReq->getPage() ?: 1;
            $pageSize = $paginationReq->getPageSize() ?: self::PAGE_SIZE;
        } else {
            $page = 1;
            $pageSize = self::PAGE_SIZE;
        }
        $pagination = new Pagination(['totalCount' => $totalCount]);
        $pagination->setPageSize($pageSize);
        $pagination->setCurPage($page);

        $query->orderBy($order)
            ->offset($pagination->getOffset())
            ->limit($pageSize);
        Tools::log($query->createCommand()->getRawSql(),'taskStoreList.log');
        $list = $query->all(LeCustomers::getDb());


        $data['stores'] = $this->fixListData($contractor, $list);
        $data['pagination'] = Tools::getPagination($pagination);

        return $data;
    }

    /**
     *
     *
     * @param LeContractor $contractor
     * @param array $list
     * @return array
     */
    private function fixListData(LeContractor $contractor, array $list)
    {
        /* 区域 */
        $areas = RegionArea::findAll(['entity_id' => array_column($list, 'area_id')]);
        $areas = Tools::conversionArray2KeyValue($areas, 'entity_id', 'area_name');

        $retArr = [];
        foreach ($list as $k => $item) {
            /* 店铺是否可以查看，业务员APP在用，店铺列表中   1:可以查看   0：不能查看 */
            if ($this->isAllowRole($contractor) || $contractor->entity_id == $item->contractor_id) {
                $canView = 1;
            } else {
                $canView = 0;
            }

            $retArr[$k] = [
                'customer_id' => $item->entity_id,    // 超市/店铺ID
                'store_front_img' => $item->store_front_img,  // 小店正面照片
                'store_name' => $item->store_name ? $item->store_name : $item->phone, // 超市/店铺名字
                'distance' => isset($item->distance) ? $item->distance : -1, // 距离
                'lat' => $item->lat,
                'lng' => $item->lng,
                'area_name' => isset($areas[$item->area_id]) ? $areas[$item->area_id] : '', // 区域名
                'contractor_id' => $item->contractor_id,  // 业务员ID
                'contractor' => $item->contractor, // 业务员名字
                'created_at' => $this->increase8Hours($item->created_at),
                'is_visit' => $canView,
                'disabled' => $item->disabled ? 1 : 0, // // 是否有效，0-正常，1-无效
            ];

            /* 下单和拜访情况（目前已注册才有拜访情况），普通业务员只能看到自己的，非普通业务员也可以看到 */
            if (($contractor->role === Contractor::COMMON_CONTRACTOR && $item->contractor_id == $contractor->entity_id)
                || $this->isAllowRole($contractor)
            ) {
                $curTimestamp = ToolsAbstract::getDate()->timestamp();
                $retArr[$k]['last_visit_label'] = '最近拜访：未拜访';
                $retArr[$k]['last_ordered_label'] = '最近下单：未下单';
                /* 1970-01-01 00:00:01之前的都是未拜访/未下单！！！！！ */
                if (($lastVisitedAt = strtotime($item->last_visited_at)) && $lastVisitedAt > 0) {
                    $retArr[$k]['last_visit_label'] = '最近拜访：'
                        . round(($curTimestamp - $lastVisitedAt) / 3600 / 24) . '天前';
                }
                if (($lastOrderAt = strtotime($item->last_place_order_at)) && $lastOrderAt > 0) {
                    $retArr[$k]['last_ordered_label'] = '最近下单：'
                        . round(($curTimestamp - $lastOrderAt) / 3600 / 24)
                        . '天前，￥' . $item->last_place_order_total;
                }
            } else {
                $retArr[$k]['last_visit_label'] = '';
                $retArr[$k]['last_ordered_label'] = '';
            }

        }
        return $retArr;
    }

    /**
     * 设置where条件
     *
     * @param Query $q
     * @param StoresListRequest $req
     * @param LeContractor $contractor
     * @return bool
     */
    private function setWhereCond(Query $q, StoresListRequest $req,LeContractor $contractor)
    {
        $cond = [
            'city' => $req->getCity(),
        ];

        // 用户标识，1：从未下单的用户，其他保留
        if ($req->getUserFlag()) {
            $cond['first_order_id'] = 0;
        }

        if ($contractor->role == self::COMMON_CONTRACTOR) {
            $cond['contractor_id'] = $contractor->entity_id;
        }else{
            // 业务员，多个用|隔开，如1|3|4
            $contractors = explode('|', $req->getContractors());
            $contractors = $contractors ? array_filter($contractors) : null;
            if ($contractors) {
                Tools::log($contractors,'taskStoreList.log');
                $cond['contractor_id'] = $contractors;
            }
        }

        $date = Tools::getDate()->date('Y-m-d H:i:s');
        // 设置cond
        // 正在进行的任务
        $q->where($cond)->andWhere(['<=', 'start_time', $date])->andWhere(['>', 'end_time', $date])->andWhere(['t.status' => 1]);

        // 筛选任务

        $task_desc_ids = array_filter(explode('|', $req->getTaskDescIds()));
        if ($task_desc_ids) {
            $q->andWhere(['visit_task_type' => $task_desc_ids]);
        }
        Tools::log($req->getTaskStatusIds(),'taskStoreList.log');
        $task_status_ids = explode('|', $req->getTaskStatusIds());
        Tools::log($task_status_ids,'taskStoreList.log');
        //完成
        if (in_array('0', $task_status_ids,true) && in_array('1', $task_status_ids,true)) {
            //全部超市
        } else if (in_array('1', $task_status_ids,true)) {
            //完成

            $q->andWhere(new Expression('last_visited_at >= start_time'));
            $q->andWhere(new Expression('last_visited_at <= end_time'));

        } else if (in_array('0', $task_status_ids,true)) {
            //未完成
            $expression = new Expression('last_visited_at < start_time or last_visited_at > end_time');
            $q->andWhere($expression);
        } else {
            //全部超市
        }

        // 筛选区域
        $area_ids = explode('|', $req->getAreaIds());
        $area_ids = $area_ids ? array_filter($area_ids) : null;
        if ($area_ids) {
            $q->andWhere(['in', 'area_id', $area_ids]);
        }

        // 最近多少天未拜访
        if (is_numeric($req->getNoVisitDay()) && $req->getNoVisitDay() >= 0) {
            $datetime = date('Y-m-d 00:00:00', strtotime('-' . ((int)$req->getNoVisitDay()) . 'day +8HOUR'));
            $q->andWhere(['<', 'last_visited_at', $datetime]);
        }

        $q->groupBy('customer_id');

        return true;
    }

    private function isAllowRole(LeContractor $contractor)
    {
        $allowArr = [
            Contractor::MANAGER_CONTRACTOR,    // 城市经理
            Contractor::AREA_CONTRACTOR,    // 大区经理
            Contractor::CEO_MANAGER,    // 总办
            Contractor::SYSTEM_MANAGER,  // 管理员
            Contractor::SUPPLY_CHAIN  //供应链专员
        ];
        if (in_array($contractor->role, $allowArr)) {
            return true;
        }
        return false;
    }

    /**
     * @return StoresListRequest
     */
    public static function request()
    {
        return new StoresListRequest();
    }

    /**
     * @return StoresResponse
     */
    public static function response()
    {
        return new StoresResponse();
    }
}