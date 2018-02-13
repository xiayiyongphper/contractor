<?php

namespace service\components;

use common\models\ContractorVisitTask;
use common\models\CoreConfigData;
use common\models\CustomerTagRelation;
use common\models\LeCustomers;
use common\models\LeMerchantDelivery;
use common\models\LeMerchantStore;
use common\models\SpecialProduct;
use common\models\VisitTaskType;
use framework\components\Date;
use framework\components\es\Console;
use framework\components\ToolsAbstract;
use Yii;


/**
 * public function
 */
class Tools extends ToolsAbstract
{

    public static $classifyArray = [
        7 => [
            'icon' => 'http://assets.lelai.com/assets/secimgs/huoyueyonghu.png',
            'short' => '活跃用户'
        ],
        8 => [
            'icon' => 'http://assets.lelai.com/assets/secimgs/zhongdianguanzhuyonghu.png',
            'short' => '重点关注用户'
        ],
        9 => [
            'icon' => 'http://assets.lelai.com/assets/secimgs/chenmoyonghu.png',
            'short' => '沉睡用户'
        ],

    ];

    /**
     * 取商品价格，特殊商品返回特殊价格
     * 如果是特价商品返回特价,不然返回原价
     */
    public static function getPrice($val)
    {
        $specialPrice = $val['special_price'];
        $price = $val['price'];

        /* 特殊商品返回特殊价格 */
        if (SpecialProduct::isSecKillProduct($val)) {
            return self::numberFormat($specialPrice, 2);
        }

        if ($specialPrice > 0
            && $specialPrice < $price
            && Tools::dataInRange($val['special_from_date'], $val['special_to_date'])
        ) {
            $finalPrice = $specialPrice;
        } else {
            $finalPrice = $price;
        }

        return self::numberFormat($finalPrice, 2);
    }

    public static function getImage($gallery, $size = '600x600', $single = true)
    {
        $gallery = explode(';', $gallery);
        $search = ['source', '600x600', '180x180'];
        if ($single) {
            return str_replace($search, $size, $gallery[0]);
        } else {
            $images = array();
            foreach ($gallery as $image) {
                $images[] = str_replace($search, $size, $image);
            }
            return $images;
        }
    }

    public static function formatPrice($price)
    {
        return number_format($price, 2, '.', '');
    }

    public static function getAssetsFile($file, $decode = false)
    {
        $file = Yii::getAlias('@service') . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $file;
        if (file_exists($file)) {
            $json = file_get_contents($file);
            if ($decode) {
                $json = json_decode($json, true);
            }
            return $json;
        } else {
            $e = new \Exception('Assets file not existed.', 999);
            Console::get()->logException($e);
        }
        return false;
    }

    /**
     *
     * 返回magento系统中的system_config信息,需要传入path
     *
     * @param $path
     *
     * @return bool|string
     */
    public static function getSystemConfigByPath($path)
    {
        $config = CoreConfigData::findOne(['path' => $path]);
        if ($config) {
            return $config->value;
        } else {
            return false;
        }
    }

    public static function random($low = 0, $high = 1, $decimals = 5)
    {
        $decimals = abs($decimals);
        if ($high < $low) {
            $t = $high;
            $high = $low;
            $low = $t;
        }
        $length = ($high - $low) * pow(10, $decimals);
        $dt = rand(0, $length);
        return $low + floatval($dt / pow(10, $decimals));
    }

    /**
     * assortmentArray
     * Author Jason Y. wang
     * 把key拿出来，覆盖key相同的
     * @param array $array
     * @param string $key
     * @param int $flag 是否在数据中删除提取出来的key
     * @return array
     */
    public static function conversionKeyArray($array, $key, $flag = 0)
    {
        $newArray = array();
        foreach ($array as $k => $v) {
            $newKey = $v[$key];
            if ($flag == 0) {
                unset($v[$key]);
            }
            $newArray[$newKey] = $v;
        }
        return $newArray;
    }

    /**
     * 把数组转换为key => value数组
     *
     * @param $array
     * @param $keyKey
     * @param $valKey
     * @return array
     */
    public static function conversionArray2KeyValue($array, $keyKey, $valKey)
    {
        $newArray = array();
        foreach ($array as $v) {
            $newArray[$v[$keyKey]] = $v[$valKey];
        }
        return $newArray;
    }

    /*
    *  @desc 根据两点间的经纬度计算距离  xiayiyongs
    *  @param float $lat 纬度值
    *  @param float $lng 经度值
    */
    public static function getDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6378.138; //approximate radius of earth in kilo-meters

        /*
          Convert these degrees to radians
          to work with the formula
        */

        $lat1 = ($lat1 * pi()) / 180;
        $lng1 = ($lng1 * pi()) / 180;

        $lat2 = ($lat2 * pi()) / 180;
        $lng2 = ($lng2 * pi()) / 180;

        /*
          Using the
          Haversine formula

          http://en.wikipedia.org/wiki/Haversine_formula

          calculate the distance
        */

        $calcLongitude = $lng2 - $lng1;
        $calcLatitude = $lat2 - $lat1;
        $stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
        $stepTwo = 2 * asin(min(1, sqrt($stepOne)));
        $calculatedDistance = $earthRadius * $stepTwo;

        return round($calculatedDistance, 2);
    }

    /**
     * 判断当前是否在起止时间内
     * 此处的start和end需要输入中国时区的时间!
     * @param $start
     * @param $end
     * @param $now
     *
     * @return bool
     */
    public static function dataInRange($start = null, $end = null, $now = null)
    {
        if (!$start || !$end) {
            return false;
        }
        if (is_numeric($start)) {
            $startTime = $start;
        } else {
            $startTime = strtotime($start);
        }
        if (is_numeric($end)) {
            $endTime = $end;
        } else {
            $endTime = strtotime($end);
        }

        if (!$now) {
            $date = new Date();
            $now = $date->timestamp();
        }

        if ($startTime <= $now && $now <= $endTime) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 判断是否为特价
     * @param $val
     * @return bool
     */
    public static function getIsSpecial($val)
    {
        if ($val['special_price'] > 0
            && $val['special_price'] < $val['price']
            && Tools::dataInRange($val['special_from_date'], $val['special_to_date'])
        ) {
            return true;
        }
        return false;
    }

    public static function getProductPromotions($rule_ids)
    {
        $rules = Proxy::getSaleRule($rule_ids);
        if ($rules) {
            $promotions = $rules->toArray()['promotions'];
            $rules = self::conversionKeyArray($promotions, 'rule_id', 1);
            //Tools::log(__FUNCTION__, 'wangyang.log');
            //Tools::log($rules, 'wangyang.log');
            return $rules;
        } else {
            return [];
        }
    }

    /**
     * Function: getCategoryLevelByID
     * Author: Jason Y. Wang
     * 计算一个分类的level
     * @param $category_id
     * @return null
     */
    public static function getCategoryLevelByID($category_id)
    {
        $categories = Redis::getPMSCategories();
        foreach ($categories as $key => $category) {
            $category = unserialize($category);
            if ($category['id'] == $category_id) {
                return $category['level'];
            }
        }
        return null;
    }

    public static function getWholesalerIdsByAreaId($areaId, $orderBy = 'sort desc')
    {
        //获取所有区域内店铺列表ID
        $merchantModel = new LeMerchantStore();
        $query = $merchantModel::find()->where(['like', 'area_id', '|' . $areaId . '|'])
            ->andWhere(['status' => LeMerchantStore::STATUS_NORMAL])
            ->andWhere(['>=', 'sort', 0]);
        $query->orderBy($orderBy);
        $wholesalerIds = $query->column();
        return $wholesalerIds;
    }

    /**
     * getStoreDetailBrief
     * Author Jason Y. wang
     * 返回简单的店铺详情
     * @param $wholesalerIds
     * @param int $areaId
     * @param null $sort
     * @param int $customer_id
     * @return array
     */
    public static function getStoreDetailBrief($wholesalerIds, $areaId = 0, $sort = null, $customer_id = 0)
    {
        $data = [];
        if (empty($wholesalerIds)) {
            return $data;
        }
//        Tools::log(func_get_args(), 'getStoreDetailBrief.log');
        $wholesalers = LeMerchantStore::find()->select(['entity_id', 'store_name', 'customer_service_phone',
            'min_trade_amount', 'promised_delivery_time', 'rebates', 'city', 'marketing_tags', 'category_tags', 'store_category', 'short_name', 'store_type'])
            ->where(['in', 'entity_id', $wholesalerIds])
            ->andWhere(['status' => LeMerchantStore::STATUS_NORMAL]);
        if ($sort) {
            $wholesalers = $wholesalers->orderBy($sort);
        }

        $wholesalers = $wholesalers->asArray()->all();
        $deliveryArray = [];
        if ($areaId) {
            //查出所有供应商配送说明
            $deliveryArray = LeMerchantDelivery::find()->where(['in', 'store_id', $wholesalerIds])
                ->andWhere(['delivery_region' => $areaId])->asArray()->all();
            $deliveryArray = Tools::conversionKeyArray($deliveryArray, 'store_id');
        }

        $customer = null;
        if ($customer_id) {
            $customer = LeCustomers::findOne(['entity_id' => $customer_id]);
        }

        //组织数据
        foreach ($wholesalers as $merchantInfo) {
            //配送区域送达时间说明
            $merchant_area_setting = isset($deliveryArray[$merchantInfo['entity_id']]) ? $deliveryArray[$merchantInfo['entity_id']] : null;
//            Tools::log($merchant_area_setting, 'getStoreDetailBrief.log');
            if ($merchant_area_setting) {
                $promised_delivery_text = $merchant_area_setting['note'];
                $min_trade_amount = $merchant_area_setting['delivery_lowest_money'];
            } else {
                $min_trade_amount = $merchantInfo['min_trade_amount'];
                $promised_delivery_text = $merchantInfo['promised_delivery_time'] ? $merchantInfo['promised_delivery_time'] . '小时送达' : '';
            }

            if ($customer) {
                $orderCountToday = Proxy::getOrderCountToday($customer, $merchantInfo['entity_id']);
                $min_trade_amount = $orderCountToday > 0 ? 0 : $min_trade_amount;
            }

            $data[$merchantInfo['entity_id']] = [
                'wholesaler_id' => $merchantInfo['entity_id'],
                'wholesaler_name' => $merchantInfo['short_name'] ?: $merchantInfo['store_name'],
                'phone' => [$merchantInfo['customer_service_phone']],
                'city' => $merchantInfo['city'],
                'min_trade_amount' => round($min_trade_amount), //最低起送价取整
                'delivery_text' => $promised_delivery_text,
                'customer_service_phone' => $merchantInfo['customer_service_phone'],
                'rebates' => $merchantInfo['rebates'],
                'rebates_text' => $merchantInfo['rebates'] ? '全场返现' . $merchantInfo['rebates'] . '%' : '',
                'store_category' => $merchantInfo['store_category'],
                'short_name' => $merchantInfo['short_name'],
                'store_type' => $merchantInfo['store_type'],
            ];
        }

        return $data;
    }

    public
    static function getCategoryByThirdCategoryIds($thirdCategoryIds)
    {
        $categories = self::proCate();

        Tools::log($categories, 'getCategoryByThirdCategoryIds.log');

        $result = [
            'id' => 1,
            'parent_id' => 0,
            'name' => 'Root',
            'path' => '1',
            'level' => '0',
            'child_category' => [],
        ];
        foreach ($categories as $first_category) {
            $first_category_data = $first_category;
            $first_category_data['child_category'] = [];
            foreach ($first_category['child_category'] as $second_category) {
                foreach ($second_category['child_category'] as $third_category) {
                    if (in_array($third_category['id'], $thirdCategoryIds)) {
                        array_push($first_category_data['child_category'], $third_category);
                    }
                }
            }

            Tools::log($first_category_data, 'getCategoryByThirdCategoryIds.log');

            if (!empty($first_category_data['child_category'])) {
                array_push($result['child_category'], $first_category_data);
            }

        }

        return $result;
    }

    /**
     * 取产品分类
     */

    public
    static function proCate()
    {
        /** @var \yii\Redis\Cache $redis */
        $redis = Yii::$app->redisCache;
        //通过SD库接口取产品分类,结果存放redis
        if ($redis->exists("pro_cate") === false) {
            $categories = Redis::getPMSCategories();
            $tree = self::collectionToArray($categories, 0);
            $tree = self::sortCategory($tree);
            $redis->set("pro_cate", serialize($tree), 3600);
        }
        $category = unserialize($redis->get("pro_cate"));
        return $category;

    }

    /**
     * @param $collection
     * @param $parentId
     * @return array
     */
    protected
    static function collectionToArray($collection, $parentId)
    {
        $categories = array();
        foreach ($collection as $key => $category) {
            $category = unserialize($category);
            if ($category['parent_id'] == $parentId) {
                $categories[] = array(
                    'id' => $category['id'],
                    'parent_id' => $category['parent_id'],
                    'name' => $category['name'],
                    'path' => $category['path'],
                    'level' => $category['level'],
                    'child_category' => self::collectionToArray($collection, $category['id']),
                );
                unset($collection[$key]);
            }
        }
        return $categories;
    }


    private
    static function sortCategory($category)
    {
        $ids = [80, 450, 103, 31, 2, 127, 413, 269, 429, 213, 476, 161, 422, 421];
        $categories = ($category && isset($category[0]) && isset($category[0]['child_category'])) ? $category[0]['child_category'] : [];
        $category_new = [];
        foreach ($ids as $id) {
            foreach ($categories as $key => $category_child) {
                if ($category_child['id'] == $id) {
                    array_push($category_new, $category_child);
                    unset($categories[$key]);
                }
            }
        }

        foreach ($categories as $category_left) {
            array_push($category_new, $category_left);
        }
        return $category_new;
    }

    /*
     * 对象数组相互转换
     */
    public
    static function ObjToArr($object)
    {
        $array = [];
        if (is_object($object)) {
            foreach ($object as $key => $value) {
                $array[$key] = $value;
            }
        } else {
            $array = $object;
        }
        return $array;
    }

    /*
     * 获取单个商店的简略信息
     */
    public
    static function getCustomerBrief($storeId, $date = null)
    {
        if (intval($storeId) <= 0) {
            return [];
        }
        $date = $date ? $date : date('Y-m-d H:i:s');
        /** @var LeCustomers $item */
        $item = LeCustomers::find()->where(['entity_id' => $storeId])->one();
        $storeInfo['store_id'] = $item->entity_id;
        $storeInfo['store_name'] = $item->store_name;
        $storeInfo['lat'] = $item->lat;
        $storeInfo['lng'] = $item->lng;
        $storeInfo['status'] = $item->status;
        $classify_ids = array_keys(self::$classifyArray);
        $classify = '';
        //超市聚合tag 注册超市才有
        /** @var CustomerTagRelation $classifyModel */
        $classifyModel = CustomerTagRelation::find()->where(['customer_id' => $item->entity_id])->andWhere(['tag_id' => $classify_ids])->one();
        if ($classifyModel) {
            $classify = isset(self::$classifyArray[$classifyModel->tag_id]) ? self::$classifyArray[$classifyModel->tag_id] : [];
        }
        $storeInfo['classify_tag'] = empty($classify) ? '' : $classify['short'];
        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        $storeInfo['last_visit_label'] = '最近拜访：未拜访';
        $storeInfo['last_ordered_label'] = '最近下单：未下单';
        /* 1970-01-01 00:00:01之前的都是未拜访/未下单！！！！！ */
        if (($lastVisitedAt = strtotime($item->last_visited_at)) && $lastVisitedAt > 0) {
            $storeInfo['last_visit_label'] = '最近拜访：'
                . round(($curTimestamp - $lastVisitedAt) / 3600 / 24) . '天前';
        }
        if (($lastOrderAt = strtotime($item->last_place_order_at)) && $lastOrderAt > 0) {
            $storeInfo['last_ordered_label'] = '最近下单：'
                . round(($curTimestamp - $lastOrderAt) / 3600 / 24)
                . '天前，￥' . $item->last_place_order_total;
        }
        // 拜访任务
        $visit_task = ContractorVisitTask::find()->alias('v')->select(['t.desc'])->leftJoin(['t' => VisitTaskType::tableName()], 'v.visit_task_type = t.entity_id')->where(['v.customer_id' => $storeId])->where(['<=', 'v.start_time', $date])->andWhere(['>=', 'v.end_time', $date])->asArray()->one();
        $storeInfo['visit_task'] = $visit_task['desc'] ? '拜访任务:' . $visit_task['desc'] : '';
        $storeInfo['store_front_img'] = $item->store_front_img;

        return $storeInfo;
    }

    public
    static function getWholesalerBrief($storeId)
    {
        /** @var LeMerchantStore $store */
        $store = LeMerchantStore::find()->where(['entity_id' => $storeId])->asArray()->one();
        $storeInfo = $store;
        $storeInfo['address'] = $store['store_address'];
        $storeInfo['phone'] = $store['contact_phone'];
        $storeInfo['customer_name'] = $store['store_name'];
        $storeInfo['status'] = $store['status'];
        $curTimestamp = ToolsAbstract::getDate()->timestamp();
        $storeInfo['last_visit_label'] = '最近拜访：未拜访';
        if (($lastVisitedAt = strtotime($storeInfo['last_visited_at'])) && $lastVisitedAt > 0) {
            $storeInfo['last_visit_label'] = '最近拜访：'
                . round(($curTimestamp - $lastVisitedAt) / 3600 / 24) . '天前';
        }
        return $storeInfo;
    }

//获取用户所属于的分群
    public
    static function getCustomerBelongGroup($customer_id)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, ENV_GROUP_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['customer_id' => $customer_id]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json; charset=UTF-8', 'Authorization:Bearer ' . ENV_GROUP_AUTH_TOKEN));
        $result = curl_exec($ch);
        $result = json_decode($result, true);
        Tools::log($result, 'xiayy.log');
        $data = isset($result['data']) ? $result['data'] : [];
        if (!is_array($data)) {
            $data = [];
        }
        return $data;
    }

}