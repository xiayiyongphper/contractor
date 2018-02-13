<?php
namespace service\resources\contractor\v1;

use common\components\UserTools;
use common\models\CustomerLevel;
use common\models\CustomerType;
use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LeCustomersIntention;
use common\models\RegionArea;
use framework\components\ToolsAbstract;
use service\components\Tools;

use service\message\contractor\StoresListRequest;
use service\message\contractor\StoresResponse;

use service\message\customer\CustomerResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

use framework\data\Pagination;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;

/**
 * Class storeList
 * 店铺列表
 * @package service\resources\contractor\v1
 */
class storeList extends Contractor
{
    /**
     * @var StoresResponse
     */
    private $recentlyOrderStoresResponse = null;

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
        /*
        if (!ContractorPermission::contractorReviewStoreListPermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }*/

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
     * @return array
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
        $select = ['entity_id', 'province', 'city', 'district', 'area_id', 'address', 'detail_address', $distanceExp,
            'store_name', 'store_front_img', 'lat', 'lng', 'phone', 'status', 'contractor_id', 'contractor',
            'created_at', 'type', 'level', 'disabled'];

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

        $regSelect = array_merge($select, [
            new Expression('0 as intention'),
            'last_visited_at', 'last_place_order_at', 'last_place_order_total'
        ]);
        $regQuery = LeCustomers::find()->select($regSelect);
        if(!$this->setWhereCond($regQuery, $request, $contractor, true)) {
            return false;
        }

        $intentionSelect = array_merge($select, [
            new Expression('1 AS intention'),
            new Expression('0 AS last_visited_at,0 AS last_place_order_at,0 AS last_place_order_total')
        ]);
        $intentionQuery = LeCustomersIntention::find()->select($intentionSelect);
        if(!$this->setWhereCond($intentionQuery, $request, $contractor, false)) {
            return false;
        }

        // 1:已注册超市（除了要查询未审核列表，其他都只查询审核通过的） 2:意向超市 3:未审核超市，其他：全部
        /* @var ActiveQuery $query */
        if ($request->getListType() == 1) { // 1:已注册超市
            $regQuery->andWhere(['status' => 1]);
            $query = $regQuery;
        } else if ($request->getListType() == 2) {  // 2:意向超市
            /* 意向超市只允许按照距离和拜访时间排序，其他排序无效 */
            if (!in_array($request->getSort(), [1, 2, 3])) {
                $order = null;
            }
            $query = $intentionQuery;
        } else if ($request->getListType() == 3) { // 3:未审核超市
            $regQuery->andWhere(['status' => 0]);   // 状态0:未审核
            $order = 'created_at desc';
            $query = $regQuery;
        } else {
            $regQuery->andWhere(['status' => 1]);
            $query = (new ActiveQuery(LeCustomers::className()))
                ->from(['temp' => $regQuery->union($intentionQuery, true)]);
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

        $list = $query->orderBy($order)
            ->offset($pagination->getOffset())
            ->limit($pageSize)
            ->all(LeCustomers::getDb());

//        throw new ContractorException($query->createCommand(LeCustomers::getDb())->getRawSql(), 111);
//        throw new ContractorException(var_export($list, 1), 111);

        $data['stores'] = $this->fixListData($request, $contractor, $list);
        $data['pagination'] = Tools::getPagination($pagination);

        return $data;
    }

    /**
     *
     *
     * @param StoresListRequest $request
     * @param LeContractor $contractor
     * @param array $list
     * @return array
     */
    private function fixListData(StoresListRequest $request, LeContractor $contractor, array $list)
    {
        /* 客户等级和客户类型 */
        $levels = CustomerLevel::find()->asArray()->all();
        $levels = Tools::conversionArray2KeyValue($levels, 'entity_id', 'level');
        $types = CustomerType::find()->asArray()->all();
        $types = Tools::conversionArray2KeyValue($types, 'entity_id', 'type');
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
                'level_name' => isset($levels[$item->level]) ? $levels[$item->level] : '',  // 客户等级
                'lat' => $item->lat,
                'lng' => $item->lng,
                'customer_style' => $item->intention, // 0:注册超市   1:意向超市
                'area_name' => isset($areas[$item->area_id]) ? $areas[$item->area_id] : '', // 区域名
                'contractor_id' => $item->contractor_id,  // 业务员ID
                'contractor' => $item->contractor, // 业务员名字
                'created_at' => $this->increase8Hours($item->created_at),
                'is_visit' => $canView,
                'intention_id' => 0, // 注册未审核列表接口用到
                'disabled' => $item->disabled ? 1 : 0, // // 是否有效，0-正常，1-无效
            ];

            /* 客户类型整理 */
            $newTypeName = '';
            $typeIds = explode('|', $item->type);
            $typeIds = $typeIds ? array_filter($typeIds) : [];
            foreach ($typeIds as $typeId) {
                if (isset($types[$typeId])) {
                    $newTypeName .= $types[$typeId] . ',';
                }
            }
            $retArr[$k]['type_name'] = rtrim($newTypeName, ',');

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

            /* 注册未审核列表接口用到，超市的意向超市id */
            if ($request->getListType() == 3 && !$item->status && !$item->intention && $item->phone) {
                if ($intentionInfo = LeCustomersIntention::findOne([
                    'phone' => $item['phone'], 'status' => 0, 'city' => $request->getCity()
                ])
                ) {
                    $retArr[$k]['is_intention'] = 1; // 是否有 意向超市  1:是   0：否
                    $retArr[$k]['intention_id'] = $intentionInfo->entity_id;
                    $retArr[$k]['intention_store_name'] = $intentionInfo->store_name;
                }
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
     * @param boolean $isRegQuery
     * @throws ContractorException
     * @return boolean
     */
    private function setWhereCond(Query $q, StoresListRequest $req, LeContractor $contractor, $isRegQuery)
    {
        $cond = [
            'city' => $req->getCity(),
        ];

        if (!$isRegQuery) {
            $cond['status'] = 0;    // 意向超市只显示状态为0的
        }

        // 超市标识，1：隐藏无效超市，其他：保留
        if ($req->getListType() != 3 && $req->getStoreFlag()) { // listType为3（待审核列表），不判断无效标识
            $cond['disabled'] = 0;
        }

        // 用户标识，1：从未下单的用户，其他保留
        if ($req->getUserFlag()) {
            $cond['first_order_id'] = 0;
        }

        // 客户等级，多个用|隔开，如1|3|4
        $customerLevels = explode('|', $req->getCustomerLevels());
        $customerLevels = $customerLevels ? array_filter($customerLevels) : null;
        if ($customerLevels) {
            $cond['level'] = $customerLevels;
        }

        // 业务员，多个用|隔开，如1|3|4
        $contractors = explode('|', $req->getContractors());
        $contractors = $contractors ? array_filter($contractors) : null;
        if ($contractors) {
            $cond['contractor_id'] = $contractors;
        }

        // 最近多少天下N单
        if (is_numeric($req->getOrderDay()) && $req->getOrderDay() >= 0) {
            if ($req->getOrderStart() > $req->getOrderEnd()) {
                throw new ContractorException('下单数参数不正确', 201);
            }

            /* 获取符合订单搜索条件的店铺ID */
            /* @throws ContractorException */
            $this->initRecentlyOrderStoresResponse($req);
            $stores = $this->recentlyOrderStoresResponse->getStores();
            $storeIds = [];
            if ($stores) {
                /* @var $store CustomerResponse */
                foreach ($stores as $store) {
                    $storeIds[] = $store->getCustomerId();
                }
                if ($storeIds) {
                    $cond['entity_id'] = $storeIds;
                }
            } else {
                return false;
            }
        }

        // 设置cond
        $q->where($cond);

        // 客户类型，多个用|隔开，如1|3|4
        $customerTypes = explode('|', $req->getCustomerTypes());
        $customerTypes = $customerTypes ? array_filter($customerTypes) : null;
        if ($customerTypes) {
            $cond1[] = 'or';
            foreach ($customerTypes as $customerType) {
                $cond1[] = ['like', 'type', '|' . $customerType . '|'];
            }
            $q->andWhere($cond1);
        }

        // 最近多少天未拜访
        if ($isRegQuery && is_numeric($req->getNoVisitDay()) &&$req->getNoVisitDay() >= 0) {
            $datetime = date('Y-m-d 00:00:00', strtotime('-' . ((int)$req->getNoVisitDay()) . 'day +8HOUR'));
            $q->andWhere(['<', 'last_visited_at', $datetime]);
        }
//        throw  new ContractorException($q->createCommand(LeCustomers::getDb())->getRawSql(), 111);

        return true;
    }

    private function isAllowRole(LeContractor $contractor)
    {
        $allowArr = [
            Contractor::MANAGER_CONTRACTOR,    // 城市经理
            Contractor::AREA_CONTRACTOR,    // 大区经理
            Contractor::CEO_MANAGER,    // 总办
            Contractor::SYSTEM_MANAGER  // 管理员
        ];
        if (in_array($contractor->role, $allowArr)) {
            return true;
        }
        return false;
    }

    /**
     * 获取最近N天下了M1至M2单的店铺列表
     *
     * @param StoresListRequest $req
     * @throws ContractorException
     * @return null|StoresResponse
     */
    private function initRecentlyOrderStoresResponse(StoresListRequest $req)
    {
        // 如果不为空，则直接返回
        if ($this->recentlyOrderStoresResponse != null) {
            return $this->recentlyOrderStoresResponse;
        }

        try {
            $this->recentlyOrderStoresResponse = UserTools::getRecentlyOrderStoreDataByProxy($req);
            return $this->recentlyOrderStoresResponse;
        } catch (\Exception $e) {
            throw new ContractorException('获取订单信息失败：' . $e->getMessage(), 404, $e);
        }
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