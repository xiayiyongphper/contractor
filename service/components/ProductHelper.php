<?php
/**
 * Created by PhpStorm.
 * User: Jason Y. wang
 * Date: 16-11-28
 * Time: 上午9:47
 */

namespace service\components;


use common\models\CoreConfigData;
use common\models\GroupSubProducts;
use common\models\LeMerchantStore;
use framework\db\readonly\models\core\SalesFlatOrderItem;
use framework\db\readonly\models\core\SalesFlatOrder;
use common\models\LeMerchantStoreCategoryCommission;
use common\models\MerchantStoreRebates;
use common\models\Products;
use common\models\ProductType;
use common\models\SpecialProduct;
use common\models\Tags;
use framework\components\ToolsAbstract;
use service\message\common\CouponReceive;
use yii\helpers\ArrayHelper;

class ProductHelper
{
    protected $area_id = 0;
    /**
     * 标签参数
     * @var array
     */
    protected $tagParams = [];
    //最近购买参数
    protected $latestBuyParams = [];
    //返回
    protected $result = [];
    //商品ID数组
    protected $productIds;
    //供应商ID数组
    protected $wholesalerIds;
    //商品数组
    protected $productArray;
    //平台返点
    protected $lelai_rebates;
    //返回商品图大小
    protected $imageSize;

    //用到哪些属性
    protected $moreProperty = false;  //更多属性
    protected $tags = false;
    protected $parameters = false;  //商品参数 在商品详情展示
    protected $couponReceive = false;  //领取优惠券按钮
    protected $latestBuy = false;
    protected $customer_id = 0;

    //是否已经获取了所有商品的tags
    protected $tagsFlag = false;
    protected $wholesalerInfoFlag = false;

    //对像属性
    protected $productTagsArray = [];
    protected $ruleTagsArray = [];
    protected $wholesalerArray = [];
    protected $city;
    protected $commissions;

    public function __construct($area_id = 0)
    {
        if ($area_id) {
            $this->area_id = $area_id;
        }
    }

    /**
     * @param $productArray
     * @param $city
     * @param string $imageSize
     * Author Jason Y. wang
     * 根据商品数组查询数据
     * 传入商品数组，注意不是对象
     * @param $wholesalerIds
     * @return $this
     */
    public function initWithProductArray($productArray, $city, $imageSize = '388x388', $wholesalerIds = [])
    {
        $this->productArray = $productArray;
        $this->city = $city;
        $this->imageSize = $imageSize;
        $this->productIds = ArrayHelper::getColumn($productArray, 'entity_id');
        //获取全部店铺   wholesalerArray
        $this->getAllWholesalers($wholesalerIds);
        // 平台返点
        $this->lelai_rebates = CoreConfigData::getLeLaiRebates();
        return $this;
    }

    /**
     * @param $productIds
     * @param $city
     * @param string $imageSize
     * Author Jason Y. wang
     * 根据商品ID查询数据
     * @param $wholesalerIds
     * @param bool $filter 是否过滤不可用的商品信息
     * @return $this
     */
    public function initWithProductIds($productIds, $city, $wholesalerIds = [], $imageSize = '388x388', $filter = true)
    {
        $this->city = $city;
        $this->imageSize = $imageSize;
        $commonProductIds = [];
        $specialProductIds = [];
        foreach ($productIds as $productId) {
            if (SpecialProduct::isSpecialProduct($productId)) {
                array_push($specialProductIds, $productId);
            } else {
                array_push($commonProductIds, $productId);
            }
        }
        //特殊商品
        $specialProductArray = SpecialProduct::find()->where(['entity_id' => $specialProductIds])->asArray()->all();
        //普通商品
        $commonProductArray = Redis::getProducts($city, $commonProductIds, $filter);

        $productArray = $commonProductArray + $specialProductArray;

        // 模型查询
        $this->productArray = $productArray;
        $this->productIds = $productIds;
        //获取全部店铺   wholesalerArray
        $this->getAllWholesalers($wholesalerIds);
        // 平台返点
        $this->lelai_rebates = CoreConfigData::getLeLaiRebates();
        return $this;
    }

    protected function getBasicPropertyData($product)
    {
        // promotion_text要判断时间
        if (!Tools::dataInRange($product['promotion_text_from'], $product['promotion_text_to'])) {
            $product['promotion_text'] = '';
        }

        $this->result[$product['entity_id']] = [
            'product_id' => $product['entity_id'],
            'name' => Products::getProductNameText($product),
            'image' => Tools::getImage($product['gallery'], $this->imageSize),
            'price' => Tools::getPrice($product),
            'original_price' => Tools::formatPrice($product['price']),
            'qty' => $product['qty'],
            'special_price' => $product['special_price'],
            'special_from_date' => $product['special_from_date'],
            'special_to_date' => $product['special_to_date'],
            'sold_qty' => $product['sold_qty'],
            'real_sold_qty' => $product['real_sold_qty'],
            'gallery' => array_filter(explode(';', $product['gallery'])),
            'wholesaler_id' => $product['wholesaler_id'],
            'wholesaler_url' => 'wholesaler/index/index?sid=' . $product['wholesaler_id'],
            'state' => $product['state'],
            'specification' => $product['specification'],
            'barcode' => $product['barcode'],
            'lsin' => isset($product['lsin']) ? $product['lsin'] : '',
            'first_category_id' => $product['first_category_id'],
            'second_category_id' => $product['second_category_id'],
            'third_category_id' => $product['third_category_id'],
            'brand' => $product['brand'],
            'export' => $product['export'],
            'origin' => $product['origin'],
            'package_num' => $product['package_num'],
            'package_spe' => $product['package_spe'],
            'package' => $product['package'],
            'shelf_life' => $product['shelf_life'],
            'desc' => $product['description'],
            'promotion_text_from' => $product['promotion_text_from'],
            'promotion_text_to' => $product['promotion_text_to'],
            'promotion_text' => $product['promotion_text'],
            'product_description' => $product['description'],
            'minimum_order' => $product['minimum_order'],
            'sort_weights' => $product['sort_weights'],
            //'rebates' => $product['rebates'],            // 商家单独设置的返点
            //'rebates_lelai' => $product['rebates_lelai'],    // 乐来单独返点(数据库新加字段)
            'is_calculate_lelai_rebates' => $product['is_calculate_lelai_rebates'],    // 是否取乐来全局返点
            'restrict_daily' => $product['restrict_daily'],
            'subsidies_wholesaler' => $product['subsidies_wholesaler'],
            'subsidies_lelai' => $product['subsidies_lelai'],
            'rule_id' => $product['rule_id'],
            'is_special' => (int)Tools::getIsSpecial($product),
            'sale_unit' => Products::getSaleUnit($product),
            'score' => isset($product['score']) ? $product['score'] : 0,
            'num' => isset($product['sub_product_num']) ? $product['sub_product_num'] : 0, // 套餐中套餐子商品的数量
        ];

        $this->result[$product['entity_id']]['type'] = empty($product['type2']) ? Products::TYPE_SIMPLE : $product['type2'];  // 0或者没有当普通商品处理

        /** 商品销售类型，自营、普通等 */
        if (!empty($product['sales_type'])) {
            $salesTypes = ProductType::getTypesByDecProductType($product['sales_type']);
            $salesTypes = $salesTypes ? implode('|', array_column($salesTypes, 'name')) : '';
            $this->result[$product['entity_id']]['sales_types_str'] = $salesTypes;
        }

        /* 因为两个表的字段都用type！！！这里是特殊商品用的，秒杀相关字段，没有则不设置 */
        if (SpecialProduct::isSpecialProduct($product['entity_id'])) {
            //秒杀、特价活动商品不参与商品级优惠
            $this->result[$product['entity_id']]['rule_id'] = 0;
            $this->result[$product['entity_id']]['status'] = $this->getSpecialProductStatus($product);
        } else {
            $this->result[$product['entity_id']]['status'] = $this->getProductStatus($product);
        }

        if (!empty($product['activity_id'])) {
            $this->result[$product['entity_id']]['activity_id'] = $product['activity_id'];
        }

        /* 需求：套餐子商品列表，只支持普通商品 */
        if (isset($product['type']) && ($product['type'] & Products::TYPE_SIMPLE) && ($product['type'] & Products::TYPE_GROUP)) {
            if ($subProducts = $this->getSubProducts($product['entity_id'], $this->city)) {
                $this->result[$product['entity_id']]['relative_products'] = $subProducts;
            }
        }

        //$this->result[$product['entity_id']]['tag_text'] = $this->result[$product['entity_id']]['is_special'] ? '特价' : '';
        $this->result[$product['entity_id']]['tag_text'] = '';

        //计算返点和获取商家属性
        $wholesaler = isset($this->wholesalerArray[$product['wholesaler_id']]) ? $this->wholesalerArray[$product['wholesaler_id']] : null;
        if (!empty($wholesaler)) {
            list($rebates, $rebates_lelai, $rebates_all) = self::calculateRebates($wholesaler, $product);
            $this->result[$product['entity_id']]['rebates'] = floatval($rebates);
            $this->result[$product['entity_id']]['rebates_lelai'] = floatval($rebates_lelai);
            $this->result[$product['entity_id']]['rebates_all'] = floatval($rebates_all);
            $this->result[$product['entity_id']]['wholesaler_name'] = $wholesaler['wholesaler_name'];
            $this->result[$product['entity_id']]['min_trade_amount'] = $wholesaler['min_trade_amount'];
        } else {
            $this->result[$product['entity_id']]['rebates'] = 0;
            $this->result[$product['entity_id']]['rebates_lelai'] = 0;
            $this->result[$product['entity_id']]['rebates_all'] = 0;
        }
    }

    protected function getMorePropertyData($product)
    {
        $this->result[$product['entity_id']] += [
            'security_info' => self::getSecurityInfo(),//商品保障详情
        ];
    }

    protected function getCouponReceiveData($product)
    {
        $coupons = Proxy::getCouponReceiveList(1, $product['rule_id'], $product['wholesaler_id']);

        $title = '';
        if ($coupons && !empty($coupons->getCouponReceive())) {
            // 初始化设置回应的商品优惠券列表
            $couponList = [];
            /* 优惠券tag信息 */
            $coupon_receive = $coupons->getCouponReceive();
            /** @var CouponReceive $coupon */
            foreach ($coupon_receive as $coupon) {
                $couponList[] = $coupon->toArray(); // 设置回应的商品优惠券列表
                $coupon_title = $coupon->getCouponTitle() . ';';
                $title = $title . $coupon_title;
            }

            $this->result[$product['entity_id']]['coupon_list'] = $couponList;

            if ($title) {
                $this->result[$product['entity_id']]['coupon_receive_layout'] = [
                    'button_text' => '领取',
                    'coupon_icon' => 'http://assets.lelai.com/assets/coupon/quan.png?v=2.6',//
                    'coupon_title' => $title,//优惠券名称
                ];
            }
        }
        return $this;
    }

    protected function getTagsData($product)
    {
        $seckillTags = [];
        $rebate = [];
        $specialTags = [];
        $product_tags = [];

        //获取商品的标签
        if ($this->tagsFlag == false) {
            $this->tagsFlag = true;
            $tagsModel = new Tags($this->city);
            $tags = $tagsModel->find()->select(['product_id', 'short', 'text', 'color', 'icon'])
                ->where(['in', 'product_id', $this->productIds])
                ->asArray()
                ->all();
            $this->productTagsArray = Tools::conversionKeyArray($tags, 'product_id');

            //计算出来的优惠标签
            //获取商品中的促销规则ID
            $rule_ids = array_filter(array_unique(ArrayHelper::getColumn($this->productArray, 'rule_id')));
            //查询优惠条件标签
            if (count($rule_ids) > 0) {
                $this->ruleTagsArray = Tools::getProductPromotions($rule_ids);
            }
        }

        //写在数据库中的标签
        if (isset($this->productTagsArray[$product['entity_id']])) {
            $product_tags = [$this->productTagsArray[$product['entity_id']]];
        }

        /* 秒杀商品tags */
        if (!empty($this->tagParams['show_seckill']) && SpecialProduct::isSecKillProduct($product)) {
            $seckillTags[] = [
                'short' => '秒杀',
                'color' => '666666'
            ];
        }

        // 商品详情不显示。【订货V2.7】特价自动打标签，优惠券 /优惠活动 > 特价  > 返点 > 自定义，特价没有长标签。
        if (empty($this->tagParams['detail']) && $product['special_price']
            && Tools::dataInRange($product['special_from_date'], $product['special_to_date'])
        ) {
            $specialTags[] = [
                'short' => '特价',
                'color' => '666666'
            ];
        }

        //$product_tags = isset($product['tags'])?$product['tags']:[];

        //计算出来的返点标签
        if ($this->result[$product['entity_id']]['rebates_all'] > 0) {
            $rebate = array(array(
                'icon' => Tags::$ICON_FAN,
                'icon_text' => Tags::$ICON_FAN_TEXT,
                'short' => '返点' . $this->result[$product['entity_id']]['rebates_all'] . '%',
                'text' => '商品参加返' . $this->result[$product['entity_id']]['rebates_all'] . '%活动',
                'color' => '666666'
            ));
        }

        //拼装促销规则信息tags
        $promotion_tags = self::getPromotionRuleTags($this->ruleTagsArray, $product['rule_id']);

        //合并所有tags
        $tags = array_merge(
            $seckillTags,
            $promotion_tags,
            $specialTags,
            $rebate,
            $product_tags
        );

        $this->result[$product['entity_id']]['tags'] = $tags;
    }

    protected function getLatestBuyData($product)
    {
        $latestOrderItem = SalesFlatOrderItem::find()
            ->alias('i')
            ->leftJoin(['o' => SalesFlatOrder::tableName()], 'o.entity_id = i.order_id')
            ->select(['i.created_at', 'i.qty'])
            ->where([
                'i.product_id' => $product['entity_id'],
                'i.wholesaler_id' => $product['wholesaler_id']
            ]);
        if (!empty($this->latestBuyParams['customer_id'])) {
            $latestOrderItem = $latestOrderItem->andWhere(['o.customer_id' => $this->latestBuyParams['customer_id']]);
        }
        $latestOrderItem = $latestOrderItem->asArray()->one();

        if (!empty($latestOrderItem)) {
            $this->result[$product['entity_id']]['latest_buy_time'] = $latestOrderItem['created_at'];
            $days = (strtotime(ToolsAbstract::getDate()->date('Y-m-d')) - strtotime(substr($latestOrderItem['created_at'], 0, 10))) / 3600 / 24;
            $this->result[$product['entity_id']]['latest_buy_time'] = $days > 0 ? $days . '天前' : '今天';
            $this->result[$product['entity_id']]['latest_buy_num'] = $latestOrderItem['qty'];
        }
    }

    /**
     * @param $product
     * Author Jason Y. wang
     * 得到商品参数，商品详情需要使用
     */
    protected function getParametersData($product)
    {
        $data = [];
        $specificationText = Products::getProductSpecificationText($product);
        if ($specificationText) {
            $data[] = array(
                'key' => '规格',
                'value' => $specificationText,
            );
        }
        if ($product['brand']) {
            $data[] = array(
                'key' => '品牌',
                'value' => $product['brand'],
            );
        }

        if ($product['production_date']) {
            $data[] = array(
                'key' => '生产日期',
                'value' => $product['production_date'],
            );
        }

        if ($product['shelf_life']) {
            $data[] = array(
                'key' => '保质期',
                'value' => $product['shelf_life'],
            );
        }

        if ($product['barcode']) {
            $data[] = array(
                'key' => '条形码',
                'value' => $product['barcode'],
            );
        }

        if ($product['origin']) {
            $data[] = array(
                'key' => '产地',
                'value' => $product['origin'],
            );
        }
        $this->result[$product['entity_id']]['parameters'] = $data;
    }

    /**
     * Author Jason Y. wang
     * 所有商品信息
     * @return array
     */
    public function getData()
    {
        if (count($this->productArray) > 0) {
            foreach ($this->productArray as $product) {
                if (!isset($product['wholesaler_id']) || !in_array($product['wholesaler_id'], $this->wholesalerIds)) {
                    continue;
                }
                //商品基本属性
                $this->getBasicPropertyData($product);
                //商家属性
                //$this->getWholesalerInfo($product);

                if ($this->moreProperty) {
                    //商品更多属性
                    $this->getMorePropertyData($product);
                }
                if ($this->tags) {
                    //商品tags
                    $this->getTagsData($product);
                }
                if ($this->parameters) {
                    //获取商品参数
                    $this->getParametersData($product);
                }
                if ($this->couponReceive) {
                    //获取商品参数
                    $this->getCouponReceiveData($product);
                }
                if ($this->latestBuy) {
                    $this->getLatestBuyData($product);
                }

            }
        }
        return $this->result;
    }

    public function getMoreProperty()
    {
        $this->moreProperty = true;
        return $this;
    }

    /**
     * Author Jason Y. wang
     * 获取商品Tags
     * @param array $tagParams
     * @return $this
     */
    public function getTags($tagParams = [])
    {
        $this->tagParams = $tagParams;
        $this->tags = true;
        return $this;
    }

    /**
     * Author Jason Y. wang
     * 获取商品参数
     * @return $this
     */
    public function getParameters()
    {
        $this->parameters = true;
        return $this;
    }

    /**
     * Author Jason Y. wang
     * 修正商品中的起送价信息
     * @param $customer_id
     * @return $this
     */
    public function correctMinTradeAmount($customer_id)
    {
        $this->customer_id = $customer_id;
        return $this;
    }

    /**
     * Author Jason Y. wang
     * 获取商品参数
     * @return $this
     */
    public function getCouponReceive()
    {
        $this->couponReceive = true;
        return $this;
    }

    public function getLatestBuy($params)
    {
        $this->latestBuyParams = $params;
        $this->latestBuy = true;
        return $this;
    }

    /**
     * @param $rules
     * @param $rule_id
     * Author Jason Y. wang
     * 获取优惠规则信息
     * @return array
     */
    protected static function getPromotionRuleTags($rules, $rule_id)
    {
        $promotion_tags = [];
        if (empty($rules) || empty($rule_id) || !is_array($rules) || count($rules) == 0) {
            return $promotion_tags;
        }
        if (isset($rules[$rule_id])) {
            $rule = $rules[$rule_id];
            $promotion_tags = [
                [
                    'short' => isset($rule['tag_short']) ? $rule['tag_short'] : '',
                    'text' => isset($rule['tag_long']) ? $rule['tag_long'] : '',
                    'color' => isset($rule['tag_long_color']) ? $rule['tag_long_color'] : '',
                    'icon' => isset($rule['tag_icon']) ? $rule['tag_icon'] : '',
                    'url' => isset($rule['tag_url']) ? $rule['tag_url'] : '',
                ]
            ];
        }

        return $promotion_tags;
    }

    /**
     * @param $rules
     * @param $wholesaler_id
     * Author Jason Y. wang
     * 获取优惠规则信息
     * @return array
     */
    protected static function getWholesalerPromotionMessage($rules, $wholesaler_id)
    {
        $promotion_messages = [];
        if (empty($wholesaler_id) || !is_array($rules) || count($rules) == 0) {
            return $promotion_messages;
        }

        if (isset($rules[$wholesaler_id])) {
            $rule = $rules[$wholesaler_id];
            $promotion_message = isset($rule['wholesaler_description']) ? $rule['wholesaler_description'] : '';
            array_push($promotion_messages, $promotion_message);
        }
        return $promotion_messages;
    }

    /*
     * 计算商家返点、乐来返点、总返点
     */
    protected function calculateRebates($wholesalerInfo, $productInfo)
    {
        $now = date("Y-m-d H:i:s");
        $rebates = 0;
        $rebates_lelai = 0;
        $store_rebates_info = null;
        //计算乐来返点
        if ($productInfo['special_rebates_lelai_from'] < $now && $productInfo['special_rebates_lelai_to'] > $now) {
            if ($productInfo['special_rebates_lelai'] == -1) {
                $rebates_lelai = 0;
            } else {
                $rebates_lelai = $productInfo['special_rebates_lelai'];
            }
        } else {
            if ($productInfo['rebates_lelai'] == -1) {
                $rebates_lelai = 0;
            } elseif ($productInfo['rebates_lelai'] > 0) {
                $rebates_lelai = $productInfo['rebates_lelai'];
            } else {
                //如果$productInfo['rebates_lelai'] = 0，从le_merchant_store_rebates获取
                if (is_null($store_rebates_info)) {
                    $store_rebates_info = MerchantStoreRebates::find()->where(['store_id' => $wholesalerInfo['wholesaler_id']])->asArray()->one();
                }
                if (!empty($store_rebates_info)) {
                    if ($store_rebates_info['special_from_date'] < $now && $store_rebates_info['special_to_date'] > $now) {
                        $rebates_lelai = $store_rebates_info['special_rebates_lelai'];
                    } else {
                        $rebates_lelai = $store_rebates_info['rebates_lelai'];
                    }
                }
            }
        }
        //计算商家返点
        if ($productInfo['special_rebates_from'] < $now && $productInfo['special_rebates_to'] > $now) {
            if ($productInfo['special_rebates'] == -1) {
                $rebates = 0;
            } else {
                $rebates = $productInfo['special_rebates'];
            }
        } else {
            if ($productInfo['rebates'] == -1) {
                $rebates = 0;
            } elseif ($productInfo['rebates'] > 0) {
                $rebates = $productInfo['rebates'];
            } else {
                //如果$productInfo['rebates'] = 0，从le_merchant_store_rebates获取
                if (is_null($store_rebates_info)) {
                    $store_rebates_info = MerchantStoreRebates::find()->where(['store_id' => $wholesalerInfo['wholesaler_id']])->asArray()->one();
                }
                if (!empty($store_rebates_info)) {
                    if ($store_rebates_info['special_from_date'] < $now && $store_rebates_info['special_to_date'] > $now) {
                        $rebates = $store_rebates_info['special_rebates'];
                    } else {
                        $rebates = $store_rebates_info['rebates'];
                    }
                }
            }
        }

        //总返点
        $rebates_all = $rebates + $rebates_lelai;

        return [$rebates, $rebates_lelai, $rebates_all];
    }

    /**
     * 详情中保障模块
     * @return array
     */
    protected function getSecurityInfo()
    {
        //商品保障详情
        $security_info = [
            [
                'icon' => 'http://assets.lelai.com/assets/secimgs/group.png',
                'text' => '正品低价',
            ],
            [
                'icon' => 'http://assets.lelai.com/assets/secimgs/group.png',
                'text' => '闪电发货',
            ],
            [
                'icon' => 'http://assets.lelai.com/assets/secimgs/group.png',
                'text' => '货到付款',
            ],
            [
                'icon' => 'http://assets.lelai.com/assets/secimgs/group.png',
                'text' => '假一赔十',
            ]
        ];
        return $security_info;
    }

    protected function getAllWholesalers($areaWholesalerIds)
    {
        $wholesalerIds = ArrayHelper::getColumn($this->productArray, 'wholesaler_id');
        if (empty($areaWholesalerIds)) {
            $this->wholesalerIds = $wholesalerIds;
        } else {
            $this->wholesalerIds = array_intersect($areaWholesalerIds, $wholesalerIds);
        }
        $this->wholesalerArray = Tools::getStoreDetailBrief($this->wholesalerIds, $this->area_id, null, $this->customer_id);
//        $wholesalers = LeMerchantStore::find()->where(['entity_id' => $this->wholesalerIds])->asArray()->all();
//        $this->wholesalerArray = Tools::conversionKeyArray($wholesalers, 'entity_id', 1);
    }

    /**
     * @return array
     */
    protected function _initProductCommission()
    {
        if (!$this->commissions) {
            $this->commissions = [];
            //读取当前生效的分类提点信息
            $commissions = LeMerchantStoreCategoryCommission::find()
                ->where(['store_id' => $this->wholesalerIds])
                ->andWhere(['status' => LeMerchantStoreCategoryCommission::STATUS_ENABLED])
                ->asArray()
                ->all();
            foreach ($commissions as $commission) {
                if (!isset($this->commissions[$commission['store_id']])) {
                    $this->commissions[$commission['store_id']] = [];
                }
                $specialValue = $commission['special_value'];
                $value = $commission['value'];
                if ($specialValue > 0 && Tools::dataInRange($commission['special_from_date'], $commission['special_to_date'])
                ) {
                    $finalValue = $specialValue;
                } else {
                    $finalValue = $value;
                }
                $this->commissions[$commission['store_id']][$commission['category_id']] = ToolsAbstract::numberFormat($finalValue, 2);
            }
        }
        return $this->commissions;
    }

    /**
     * 获取子商品列表
     *
     * @param int $groupProductId
     * @param int $city
     * @return array
     */
    protected function getSubProducts($groupProductId, $city)
    {
        /* 先从映射表获取子商品ID，然后从 lelai_slim_merchant.special_products 获取详细信息 */
        $groupSubProducts = GroupSubProducts::find()->orderBy('entity_id asc')
            ->where(['group_product_id' => $groupProductId])->asArray()->all();
        if (!$groupSubProducts) {
            return [];
        }

        foreach ($groupSubProducts as $k => $groupSubProduct) {
            $groupSubProduct['entity_id'] = $groupSubProduct['ori_product_id'];
            $groupSubProducts[$k] = $groupSubProduct;
        }
        return (new ProductHelper())->initWithProductArray($groupSubProducts, $city)->getData();
    }

    private function getProductStatus($product)
    {
        if (empty($product['shelf_from_date']) || empty($product['shelf_to_date'])) {
            return Products::STATUS_DISABLED;
        }
        if (Tools::dataInRange($product['shelf_from_date'], $product['shelf_to_date']) && $product['status'] == Products::STATUS_ENABLED) {
            return Products::STATUS_ENABLED;
        } else {
            return Products::STATUS_DISABLED;
        }
    }

    private function getSpecialProductStatus($product)
    {
        if ($product['status'] == Products::STATUS_ENABLED) {
            return Products::STATUS_ENABLED;
        } else {
            return Products::STATUS_DISABLED;
        }
    }
}
