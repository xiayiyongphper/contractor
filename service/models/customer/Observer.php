<?php
namespace service\models\customer;

use common\models\CityConfig;
use common\models\DeviceToken;
use common\models\LeCustomers;
use common\models\LeCustomersBalanceAdditionalPackage;
use common\models\LeCustomersBalanceAdditionalPackageLog;
use common\models\LeCustomersBalanceLog;
use framework\components\Date;
use framework\components\es\Console;
use service\components\Tools;
use service\models\Process;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/29
 * Time: 17:23
 */
class Observer
{
    /**
     * 商家已经同意配送
     */
    public static function orderConfirm($data)
    {
        $content = '商家已经同意配送。';
        if (!isset($data['order_id']) && isset($data['entity_id'])) {
            $data['order_id'] = $data['entity_id'];
        }
        self::pushMessage($data, $content, 'lelaishop://order/info?oid=' . $data['order_id']);
    }

    /**
     * 商家拒绝了你的订单
     */
    public static function orderDecline($data)
    {
        $content = '商家拒绝了你的订单';
        if (!isset($data['order_id']) && isset($data['entity_id'])) {
            $data['order_id'] = $data['entity_id'];
        }
        self::pushMessage($data, $content, 'lelaishop://order/info?oid=' . $data['order_id']);
    }

    /**
     * 商家同意取消订单
     */
    public static function orderAgreeCancel($data)
    {
        $content = '商家同意取消订单';
        if (!isset($data['order_id']) && isset($data['entity_id'])) {
            $data['order_id'] = $data['entity_id'];
        }
        self::pushMessage($data, $content, 'lelaishop://order/info?oid=' . $data['order_id']);
        //Tools::log($data,'shortMessage.log');
		//商家同意取消订单时，发送短信给送货司机
		DriverSms::sendShortMessage($data);
    }

    /**
     * 商家拒绝取消订单
     */
    public static function orderRejectCancel($data)
    {
        $content = '商家拒绝取消订单';
        if (!isset($data['order_id']) && isset($data['entity_id'])) {
            $data['order_id'] = $data['entity_id'];
        }
        self::pushMessage($data, $content, 'lelaishop://order/info?oid=' . $data['order_id']);

    }

    /**
     * 商家订单备注
     */
    public static function orderAddComment($data)
    {
        $content = '您有一条供货商留言，请注意查看';
        if (!isset($data['order_id']) && isset($data['entity_id'])) {
            $data['order_id'] = $data['entity_id'];
        }
        self::pushMessage($data, $content, 'lelaishop://order/info?oid=' . $data['order_id']);

    }

    /**
     * 推送给商家版
     */
    public static function pushNotification($data)
    {
        $redis = Tools::getRedis();
        $redis->lPush(Process::MESSAGE_PUSH_QUEUE, serialize($data));
    }

    /**
     * 有一个功能：零钱信息推送
     * 1、推送时间：每周二、周五早晨9点
     * 2、推送条件：用户零钱余额大于等于10元
     * 3、推送文案：您有#元零钱未使用，下单可直抵现金哦
     */
    public static function pushBalanceMessage()
    {
        $customers = LeCustomers::find()->where(['>=', 'balance', 10])->all();
        /** @var LeCustomers $customer */
        foreach ($customers as $customer) {
            $data = [
                'customer_id' => $customer->entity_id,
                'order_id' => ''  //无用
            ];

            $content = '您有' . $customer->balance . '元零钱未使用，下单可直抵现金哦';
            print_r($data);
            self::pushMessage($data, $content, 'lelaishop://');
        }
    }

    public static function pushMessage($data, $content, $scheme = null)
    {
        // 数据
        $customer_id = $data['customer_id'];
        $redis = Tools::getRedis();
        Tools::log($data, 'wangyang.txt');
        /** @var  DeviceToken $queue */
        $queue = DeviceToken::findOne(['customer_id' => $customer_id]);
        if ($queue && $queue->entity_id) {
            $params = array(
                'title' => '乐来订货网',
                'content' => $content,
            );
			// 默认不传scheme会打开app
            if($scheme!==null) {
                $params['scheme'] = $scheme;
            }elseif(isset($data['order_id'])){
				$params['scheme'] = 'lelaishop://order/info?oid=' . $data['order_id'];
			}else{
				$params['scheme'] = 'lelaishop://';
			}
            $message = array(
                'system' => $queue->system,
                'token' => $queue->token,
                'platform' => 1,
                'channel' => $queue->channel,
                'value_id' => $customer_id,
                'typequeue' => $queue->typequeue,
                'params' => serialize($params),
            );
            Tools::log($message, 'wangyang.txt');
            $redis->lPush(Process::MESSAGE_PUSH_QUEUE, serialize($message));
        }
    }


    /**
     * 活动,每月首单标记
     * 新单判断标记
     * data = [
     * 'order_id' => $orderId,
     * 'customer_id' => $customerId
     * 'order_created_at' => $order->created_at,
     * ];
     *
     * @param $data
     */
    public static function act_monthFirstOrder_orderNew($data)
    {

        // 只有买特定供应商的单才做标记
        $wholesaler_id = $data['wholesaler_id'];
        if (!in_array($wholesaler_id, [
            31,//龙盛行
            33,//新百惠商行
            35,//清新堡新批发部

            // 加上肇庆所有的店, 郑国睿2016年07月16日13:28:18
            28, //肇庆市裕海贸易有限公司
            30, //端州区广客隆副食商行
            70, //盛丰行购销部
            71, //信立购销部
            72, //肇庆端州区颖志副食商行
            78, //端州区雪缘食品经营部
            79, //端州区银来商行

            // 韶关
            73, //楚泰商行
            76, //雄豐副食商行
            77, //陈记购销部

            // 江门
            101, //	百宏商行
            102, //	誉昌商行

        ])
        ) {
            return;
        }

        $customer_id = $data['customer_id'];

        // 当前月份
        $date = new Date();
        // 现在的时间戳
        $now = $date->gmtTimestamp();
        // 今天凌晨的时间戳
        $today = $date->gmtTimestamp($date->date('Y-m-d 00:00:00', $now));
        //$todayStr = $date->gmtDate('Y-m-d H:i:s', $date->date(null, $today));
        // 本月初
        $thisMonth = $date->gmtTimestamp($date->date('Y-m-01', $today));
        $thisMonthStr = $date->gmtDate('Y-m-d H:i:s', $date->date(null, $thisMonth));
        // 本月享受过首单没有
        $query = ActMonthFirstOrder::find()
            ->where(['customer_id' => $customer_id])
            ->andWhere(['>=', 'order_created_at', $thisMonthStr])//->andWhere([''=>["processing_receive","processing_shipping","pending_comment","processing","complete"]])
        ;

        // 没有则添加记录
        if (!$query->count()) {
            $newRecord = new ActMonthFirstOrder();
            $newRecord->setAttributes([
                'customer_id' => $data['customer_id'],
                'order_id' => $data['entity_id'],
                'order_created_at' => $data['created_at'],
            ]);
            $newRecord->save();
        }

    }

    /**
     * 活动,每月首单标记
     * 取消订单删记录
     * data = [
     * 'order_id' => $orderId,
     * 'customer_id' => $customerId,
     * 'order_created_at' => $order->created_at,
     * ];
     *
     * @param $data
     */
    public static function act_monthFirstOrder_orderCancel($data)
    {
        $order_id = $data['entity_id'];

        // 删除此订单id的记录
        ActMonthFirstOrder::deleteAll(['order_id' => $order_id]);

    }

	/**
	 * 新订单,标记用户首单
	 * @param $data
	 */
	public static function first_order_at($data)
	{
		// 首单判断
		$customerId = $data['customer_id'];
		/** @var LeCustomers $customer */
		$customer = LeCustomers::findByCustomerId($customerId);
		if($customer->entity_id && !$customer->first_order_id){
			$customer->first_order_id = $data['entity_id'];
			$customer->first_order_at = $data['created_at'];
			$customer->save();
		}
	}


    /*
     * 用户注册成功
     * 1.发钱包余额
     * 2.发额度包
     */
	public static function customerCreated($data)
    {
		Tools::log('============================', 'registerGiveBalance.txt');
        Tools::log('注册赠送:' . json_encode($data), 'registerGiveBalance.txt');
        // 注册成功赠送钱包余额
        $customerId = $data['entity_id'];
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if (!$customer->getId()) {
            Tools::log('注册赠送出错,不存在此用户:' . $customerId, 'registerGiveBalance.txt');
            return;
        }

		/**
		 * 1.钱包余额
		 */
        // 此用户是否已经发放过注册金
        $log = LeCustomersBalanceLog::find()
            ->where(['customer_id' => $customerId])
            ->andWhere(['action' => 'REGISTER'])
            ->one();
        if (!$log) {
        	self::registerWallet($customer);
        }else{
			Console::get()->log(
				[
					'customer_id' => $customerId,
					'warning' => 'Register wallet already given, Log:' . json_encode($log->toArray()),
					'data' => $data,
				], null,
				['warning', 'register', 'give'],
				Console::ES_LEVEL_WARNING
			);
			Tools::log('此用户注册金已发放过,Log:' . json_encode($log->toArray()), 'registerGiveBalance.txt');
		}

		/**
		 * 2.额度包
		 */
		// 此用户是否已经发放过注册金
		$log = LeCustomersBalanceAdditionalPackageLog::find()
			->where(['customer_id' => $customerId])
			->andWhere(['action' => 'REGISTER_ADDITIONAL_PACKAGE'])
			->one();
		if (!$log) {
			self::registerAdditionalPackage($customer);
		}else{
			Console::get()->log(
				[
					'customer_id' => $customerId,
					'warning' => 'Register additional package already given, Log:' . json_encode($log->toArray()),
					'data' => $data,
				], null,
				['warning', 'register', 'additional_package'],
				Console::ES_LEVEL_WARNING
			);
			Tools::log('此用户注册额度包已发放过,Log:' . json_encode($log->toArray()), 'registerGiveBalance.txt');
		}

    }

	/**
	 * 注册发钱包余额
	 * @param $customer LeCustomers
	 *
	 * @return bool
	 */
	private static function registerWallet($customer){
		// 读取城市配置
		$config = CityConfig::findOne(['city'=>$customer->city]);
		if($config){
			// 转成以前的配置格式
			$config = $config->register_give_from.','.$config->register_give_to;
			Tools::log('城市注册赠送余额配置:' . $config, 'registerGiveBalance.txt');
		}else{
			// 读取平台配置
			$config = Tools::getSystemConfigByPath('wallet/register/give');
			Tools::log('平台注册赠送余额配置:' . $config, 'registerGiveBalance.txt');
		}
		// 分析配置
		if (count(explode(',', $config)) != 2) {
			Console::get()->log(
				[
					'error' => 'Register wallet config error, config:'.$config,
					'data' => $customer->toArray(),
				], null,
				['error', 'register', 'give'],
				Console::ES_LEVEL_ERROR
			);
			Tools::log('注册赠送余额出错,配置有误:' . $config, 'registerGiveBalance.txt');
			return false;
		}
		list($low, $high) = explode(',', $config);
		if (!is_numeric($low) || !is_numeric($high)) {
			Console::get()->log(
				[
					'error' => 'Register wallet config error, low:'.$low.'high:'.$high,
					'data' => $customer->toArray(),
				], null,
				['error', 'register', 'give'],
				Console::ES_LEVEL_ERROR
			);
			Tools::log('注册赠送余额出错,配置有误:' . $config, 'registerGiveBalance.txt');
			return false;
		}

		// 至此,可以发放
		$amount = Tools::random($low, $high, 2);
		$result = $customer->addBalance('注册赠送', 'REGISTER', $amount, 0, 0);
		if($result){
			Console::get()->log(
				[
					'success' => 'Register wallet give success!',
					'customer_id' => $customer->entity_id,
					'amount' => $amount,
				], null,
				['success', 'register', 'give'],
				Console::ES_LEVEL_INFO
			);
			// 推送
			self::pushMessage(
				[
					'customer_id' => $customer->entity_id,
				],
				"初次相识，小乐奉上{$amount}元见面礼，进入“我的零钱”查看吧"
			);
		}
		return $result;
	}

	/**
	 * 注册发额度包
	 * @param $customer LeCustomers
	 *
	 * @return bool
	 */
	private static function registerAdditionalPackage($customer){
		// 读取城市配置
		$config = CityConfig::findOne(['city'=>$customer->city]);
		if($config){
			// 转成以前的配置格式
			$amount = $config->register_additional_package;
			Tools::log('城市注册赠送额度包配置:' . $amount, 'registerGiveBalance.txt');
		}else{
			// 读取平台配置
			$amount = Tools::getSystemConfigByPath('wallet/register/additional_package');
			Tools::log('平台注册赠送额度包配置:' . $amount, 'registerGiveBalance.txt');
		}
		// 分析配置
		if (!is_numeric($amount)) {
			Console::get()->log(
				[
					'customer_id' => $customer->entity_id,
					'error' => 'Register additional package config error, config:'.$amount,
					'data' => $customer->toArray(),
				], null,
				['error', 'register', 'additional_package'],
				Console::ES_LEVEL_ERROR
			);
			Tools::log('注册赠送额度包出错,配置有误:' . $amount, 'registerGiveBalance.txt');
			return false;
		}
		// 配置为0则跳过
		if($amount<=0){
			Console::get()->log(
				[
					'customer_id' => $customer->entity_id,
					'error' => 'Config is 0, skip!',
					'data' => $customer->toArray(),
				], null,
				['warning', 'register', 'additional_package'],
				Console::ES_LEVEL_WARNING
			);
			Tools::log('配置为0,跳过赠送.', 'registerGiveBalance.txt');
			return false;
		}

		// 至此,可以发放
		$charge_data = [
			'customer_id' => $customer->entity_id,
			'amount' => $amount,
			'title' => '注册赠送额度包',
			'admin_id' => 0,
			'admin_name' => '',
			'action' => 'REGISTER_ADDITIONAL_PACKAGE',
		];
		Tools::log('注册赠送额度包:' . json_encode($charge_data), 'registerGiveBalance.txt');
		$result = self::charge_additional_package($charge_data);
		if($result){
			Console::get()->log(
				[
					'success' => 'Register additional package charge success!',
					'customer_id' => $customer->entity_id,
					'amount' => $amount,
				], null,
				['success', 'register', 'additional_package'],
				Console::ES_LEVEL_INFO
			);
		}
		return $result;
	}

    /**
     * 下单消费余额
     *
     * data为order表属性
     *
     * @param $data
     */
    public static function balance_consume($data)
    {
        $order = $data;
        Console::get()->log($data, null, ['balance', 'balance_consume'], Console::ES_LEVEL_NOTICE);
        Tools::log('============================', 'balance.txt');
        Tools::log('balance_consume', 'balance.txt');
        Tools::log($data, 'balance.txt');
        // 没有用钱包余额付款的订单不处理
        if (!isset($order['balance'])
            || (isset($order['balance']) && $order['balance'] <= 0)
        ) {
            Tools::log('没有用钱包余额付款的订单不处理', 'balance.txt');
            return;
        }

        // 用了钱包余额的订单,则扣除相应额度;
        $customerId = $order['customer_id'];
        $orderId = $order['entity_id'];
        $orderNo = $order['increment_id'];
        $amount = $order['balance'];

        // 此单是否已经处理过
        $log = LeCustomersBalanceLog::find()
            ->where(['customer_id' => $customerId])
            ->andWhere(['order_id' => $orderId])
            ->andWhere(['action' => 'CONSUME'])
            ->one();
        if ($log) {
			Console::get()->log(
				[
					'info' => 'This order has already consumed balance, Log:' . json_encode($log->toArray()),
					'order' => $data,
				], null,
				['warning', 'balance', 'balance_consume'],
				Console::ES_LEVEL_WARNING
			);
            Tools::log('此订单已消费过,Log:' . json_encode($log->toArray()), 'balance.txt');
            return;
        }
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if (!$customer->getId()) {
			Console::get()->log(
				[
					'error' => 'customer not exist:' . $customerId,
					'order' => $data,
				], null,
				['error', 'balance', 'balance_consume'],
				Console::ES_LEVEL_ERROR
			);
            Tools::log('不存在此用户:' . $customerId, 'balance.txt');
        }

        // 至此,可以扣钱了
        $customer->reduceBalance('消费', 'CONSUME', $amount, $orderId, $orderNo);
    }

    /**
     * 订单取消响应函数,退回订单钱包余额
     *
     * data为order表属性
     *
     * @param $data
     */
    public static function return_balance($data)
    {
        $order = $data;
        Console::get()->log($data, null, ['balance', 'return_balance'], Console::ES_LEVEL_NOTICE);
        Tools::log('============================', 'balance.txt');
        Tools::log('return_balance', 'balance.txt');
        Tools::log($data, 'balance.txt');
        // 没有用钱包余额付款的订单不处理
        if (!isset($order['balance'])
            || (isset($order['balance']) && $order['balance'] <= 0)
        ) {
            Tools::log('没有用钱包余额付款的订单不处理', 'balance.txt');
            return;
        }

        // 用了钱包余额的订单,则返还余额;
        $customerId = $order['customer_id'];
        $orderId = $order['entity_id'];
        $orderNo = $order['increment_id'];
        $amount = $order['balance'];

        // 此单是否已经处理过
        $log = LeCustomersBalanceLog::find()
            ->where(['customer_id' => $customerId])
            ->andWhere(['order_id' => $orderId])
            ->andWhere(['action' => 'RETURN'])
            ->one();
        if ($log) {
			Console::get()->log(
				[
					'warning' => 'This order has already returned balance, Log:' . json_encode($log->toArray()),
					'order' => $data,
				], null,
				['warning', 'balance', 'return_balance'],
				Console::ES_LEVEL_WARNING
			);
            Tools::log('此订单已回退过,Log:' . json_encode($log->toArray()), 'balance.txt');
            return;
        }
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if (!$customer->getId()) {
			Console::get()->log(
				[
					'error' => 'customer not exist:' . $customerId,
					'order' => $data,
				], null,
				['error', 'balance', 'return_balance'],
				Console::ES_LEVEL_ERROR
			);
            Tools::log('不存在此用户:' . $customerId, 'balance.txt');
        }

        // 至此,可以退钱
        $result = $customer->addBalance('订单退回', 'RETURN', $amount, $orderId, $orderNo);
		if($result){
			Console::get()->log(
				[
					'success' => 'Balance return success!',
					'customer_id' => $customerId,
					'order' => $data,
				], null,
				['success', 'balance', 'return_balance'],
				Console::ES_LEVEL_INFO
			);
		}

    }


    /**
     * 订单确认收货响应函数
     * 钱包增加返现
     *
     * data为order表属性
     *
     * @param $data
     */
    public static function rebates_add($data)
    {
        $order = $data;
        Console::get()->log($data, null, ['balance', 'rebates_add'], Console::ES_LEVEL_NOTICE);
        Tools::log('============================', 'balance.txt');
        Tools::log('rebates_add', 'balance.txt');
        Tools::log($data, 'balance.txt');
        // 无返现的订单不处理
        if (!isset($order['rebates'])
            || (isset($order['rebates']) && $order['rebates'] <= 0)
        ) {
            Tools::log('无返现的订单不处理', 'balance.txt');
            return;
        }

        // 读取城市配置
		$config = CityConfig::findOne(['city'=>$order['city']]);
		if($config){
			Tools::log('城市返现配置:'.json_encode($config->toArray()), 'balance.txt');
			// 检查返现开关
			if(!$config->wallet_switch){
				Console::get()->log(
					[
						'warning' => 'City config wallet switch is OFF',
						'order' => $data,
					], null,
					['warning', 'balance', 'rebates_add', 'in_disabled_city'],
					Console::ES_LEVEL_WARNING
				);
				Tools::log('城市未开通返现到钱包', 'balance.txt');
				return;
			}
			// 检查下单日期
			$date = new Date();
			if($date->date(null, $order['created_at']) < $config->rebates_order_created_from){
				Console::get()->log(
					[
						'warning' => 'Order date not conform to config, order:'.$order['created_at'].' config:'.$config->rebates_order_created_from,
						'order' => $data,
					], null,
					['warning', 'balance', 'rebates_add'],
					Console::ES_LEVEL_WARNING
				);
				Tools::log('下单日期不符合, order:'.$order['created_at'].' config:'.$config->rebates_order_created_from, 'balance.txt');
				return;
			}
		}else{
			// 以前的配置
			// 韶关23号零点后下单的才返到钱包
			if ($order['city'] == '440200' && $order['created_at'] < '2016-08-22 16:00:00') {
				Tools::log('韶关2016年08月23日零点之后下的单才返现', 'balance.txt');
				return;
			}

			// 其他城市2016年08月16日零点之后下的单才返现
			if ($order['created_at'] < '2016-08-15 16:00:00') {
				Tools::log('2016年08月16日零点之后下的单才返现', 'balance.txt');
				return;
			}

			// 订单城市为非开通返现到钱包的城市则不返现到钱包
			$disabled = json_decode(Tools::getSystemConfigByPath('wallet/subsidies/disabled'), true);
			Tools::log($disabled, 'balance.txt');
			if ($disabled && isset($disabled['city']) && in_array($order['city'], $disabled['city'])) {
				Console::get()->log(
					[
						'warning' => 'City config wallet switch is OFF',
						'order' => $data,
						'disabled' => $disabled,
					], null,
					['warning', 'balance', 'rebates_add', 'in_disabled_city'],
					Console::ES_LEVEL_WARNING
				);
				Tools::log('order in disabled city:' . $order['city'], 'balance.txt');
				return;
			}
		}

        // 有返现的的订单,则返到钱包;
        $customerId = $order['customer_id'];
        $orderId = $order['entity_id'];
        $orderNo = $order['increment_id'];
        $amount = $order['rebates'];
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);

        // 此单是否已经处理过
        $log = LeCustomersBalanceLog::find()
            ->where(['customer_id' => $customerId])
            ->andWhere(['order_id' => $orderId])
            ->andWhere(['action' => 'REBATES'])
            ->one();
        if ($log) {
			Console::get()->log(
				[
					'warning' => 'This order has already added balance, Log:' . json_encode($log->toArray()),
					'order' => $data,
				], null,
				['warning', 'balance', 'rebates_add'],
				Console::ES_LEVEL_WARNING
			);
            Tools::log('此订单已返现过,Log:' . json_encode($log->toArray()), 'balance.txt');
            return;
        }

        if (!$customer->getId()) {
            Console::get()->log(
                [
					'error' => 'customer not exist:' . $customerId,
                    'order' => $data,
                ], null,
                ['error', 'balance', 'rebates_add', 'can_not_find_customer'],
                Console::ES_LEVEL_ERROR
            );
            Tools::log('不存在此用户:' . $customerId, 'balance.txt');
            return;
        }

        // 至此,可以返现
        $result = $customer->addBalance('返现', 'REBATES', $amount, $orderId, $orderNo);

        if ($result) {
			Console::get()->log(
				[
					'info' => "Rebates success!",
					'order' => $data,
				], null,
				['success', 'balance', 'rebates_add'],
				Console::ES_LEVEL_INFO
			);
            self::pushMessage([
                'customer_id' => $order['customer_id'],
                'order_id' => $order['entity_id'],
            ],
                "返现{$amount}元已到账，最新版本可见"
            );
        }

    }


    /**
     * 订单确认收货响应函数
     * 额度包转钱包余额
     *
     * data为order表属性
     *
     * @param $data
     */
    public static function additional_package_to_balance($data)
    {
        $order = $data;
        Console::get()->log($data, null, ['additional_package', 'additional_package_to_balance'], Console::ES_LEVEL_NOTICE);
        Tools::log('============================', 'additional_package.txt');
        Tools::log('additional_package_to_balance', 'additional_package.txt');
        Tools::log($data, 'additional_package.txt');

        // 查询基本信息
        $customerId = $order['customer_id'];
        $orderId = $order['entity_id'];
        $orderNo = $order['increment_id'];
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if (!$customer->getId()) {
            Console::get()->log(
                [
                    'error' => 'customer not exist:'.$customerId,
                    'order' => $data,
                ], null,
                ['error', 'additional_package', 'additional_package_to_balance', 'can_not_find_customer'],
                Console::ES_LEVEL_ERROR
            );
            Tools::log('不存在此用户:' . $customerId, 'additional_package.txt');
        }

        // 此单是否已经处理过
        $log = LeCustomersBalanceAdditionalPackageLog::find()
            ->where(['customer_id' => $customerId])
            ->andWhere(['order_id' => $orderId])
            ->andWhere(['action' => 'CONSUME'])
            ->one();
        if ($log) {
			Console::get()->log(
				[
					'warning' => 'This order has already processed additional package to balance, Log:' . json_encode($log->toArray()),
					'order' => $data,
				], null,
				['warning', 'additional_package', 'additional_package_to_balance'],
				Console::ES_LEVEL_WARNING
			);
            Tools::log('此订单已处理过额度包,Log:' . json_encode($log->toArray()), 'additional_package.txt');
            return;
        }

		// 用户剩余额度
		$additional_package = LeCustomersBalanceAdditionalPackage::getByCustomerId($customerId);
		if ($additional_package <= 0) {
			Console::get()->log(
				[
					'warning' => 'Customer\'s additional package is 0, can\'t change to balance.',
					'customer_id' => $customerId,
					'order' => $data,
				], null,
				['warning', 'additional_package'],
				Console::ES_LEVEL_WARNING
			);
			Tools::log('用户剩余额度包为0,不能转到钱包。', 'additional_package.txt');
			return;
		}

		// 读取城市配置
		$config = CityConfig::findOne(['city'=>$customer->city]);
		if($config){
			// 转成以前的配置格式
			$range = $config->additional_package_subsidies_range_from.','.$config->additional_package_subsidies_range_to;
			$one_day_limit = (float)$config->additional_package_one_day_consume_limit;
			Tools::log('城市额度包赠送配置, range:'.$range.' one_day_limit:'.$one_day_limit, 'additional_package.txt');
		}else{
			// 读取平台配置
			$range = Tools::getSystemConfigByPath('wallet/additional_package/subsidies_range');
			$one_day_limit = (float)Tools::getSystemConfigByPath('wallet/additional_package/one_day_consume_limit');
			Tools::log('平台额度包赠送配置, range:'.$range.' one_day_limit:'.$one_day_limit, 'additional_package.txt');
		}

		// 每日领取限制
		// 领取的订单,下单当日,同一个供应商的订单只能产生一个领取。
		$date = new Date();
		$orderDayStr = $date->date('Y-m-d', $data['created_at']);
		$wholesaler_id = $data['wholesaler_id'];
		$sql = "SELECT m.entity_id, m.amount, DATE_FORMAT(DATE_ADD(o.created_at, INTERVAL 8 HOUR), '%Y-%m-%d') as order_day, o.created_at AS order_time, o.wholesaler_id
				FROM lelai_slim_customer.le_customers_balance_additional_package_log AS m
				LEFT JOIN lelai_slim_core.sales_flat_order AS o ON o.entity_id = m.order_id
				WHERE m.action = 'CONSUME' 
				AND m.customer_id = {$customerId}
				AND o.wholesaler_id = {$wholesaler_id} 
				HAVING order_day like '%{$orderDayStr}%'
		";
		$logs = LeCustomersBalanceAdditionalPackageLog::getDb()->createCommand($sql)->queryAll();
		$amount_sum = 0;
		foreach ($logs as $log) {
			$amount_sum += $log['amount'];
		}
		if($amount_sum > $one_day_limit){
			Console::get()->log(
				[
					'warning' => 'Customer consume too much today, limit:'.$one_day_limit.' consumed:'.$amount_sum.' Logs:'.json_encode($logs),
					'order' => $data,
				], null,
				['warning', 'consume_too_much', 'additional_package', 'additional_package_to_balance'],
				Console::ES_LEVEL_WARNING
			);
			Tools::log('今日转换额度超限, 限制:'.$one_day_limit.' 已转:'.$amount_sum.' Logs:'.json_encode($logs), 'additional_package.txt');
			return;
		}

		// 分析配置(range)
        if (!$range || !is_array(explode(',', $range)) || count(explode(',', $range)) != 2) {
            Console::get()->log(
                [
                    'error' => 'config error',
                    'config' => $range,
                    'order' => $data,
                ], null,
                ['error', 'additional_package', 'config_error'],
                Console::ES_LEVEL_ERROR
            );
            Tools::log('config_error:' . $range, 'additional_package.txt');
            return;
        }
        list($low, $high) = explode(',', $range);
        if ($low < 1) {
			Console::get()->log(
				[
					'error' => 'config error, "low" must larger than 1:' . $range,
					'config' => $range,
					'order' => $data,
				], null,
				['error', 'additional_package', 'config_error'],
				Console::ES_LEVEL_ERROR
			);
            Tools::log('配置错误,low必须大于等于1:' . $range, 'additional_package.txt');
        }
        // 得到随机转的额度
        $amount = Tools::random($low, $high, 1);

		// 额度包余额不足配置下限,则把剩余的钱都转了
        if ($additional_package < $amount) {
            $amount = $additional_package;
        }
        if ($amount <= 0) {
			Console::get()->log(
				[
					'warning' => 'Customer\'s additional package is 0, can\'t change to balance.',
					'customer_id' => $customerId,
					'order' => $data,
				], null,
				['warning', 'additional_package'],
				Console::ES_LEVEL_WARNING
			);
            Tools::log('用户剩余额度包为0,不能转到钱包。', 'additional_package.txt');
            return;
        }

        // 到此,可以转了
        // 先扣额度包
        $customer->reduceBalanceAdditionalPackage('转到钱包', $action = 'CONSUME', $amount, $orderId, $orderNo);

        // 再加余额
		$result = $customer->addBalance('系统赠送', 'ADDITIONAL_PACKAGE_TO_BALANCE', $amount, $orderId, $orderNo);

		// 转成功的推送
		if ($result) {
			Console::get()->log(
				[
					'info' => "Additional package change to balance success, amount:".$amount,
					'customer_id' => $customerId,
					'order' => $data,
				], null,
				['success', 'additional_package'],
				Console::ES_LEVEL_INFO
			);
			self::pushMessage([
				'customer_id' => $order['customer_id'],
				'order_id' => $order['entity_id'],
			],
				"小乐提示：随机返现活动的{$amount}元已入账，快去看看吧~"
			);
		}

    }


	/**
	 * 运营后台充值额度包
	 *
	 * $event_data = [
	 * 'customer_id' => $importData['entity_id'],
	 * 'amount' => $charge_additional_package,
	 * 'title' =>
	 * '后台充值额度包:'.$charge_additional_package.',操作人:'.LE_Sales_Adminhtml_OrderController::getCommentOperator(),
	 * 'admin_id' => Mage::getSingleton('admin/session')->getUser()->getId(),
	 * 'admin_name' => Mage::getSingleton('admin/session')->getUser()->getUsername(),
	 * ];
	 *
	 * @param $data
	 *
	 * @return bool
	 */
    public static function charge_additional_package($data)
    {
        $order = $data;
        Console::get()->log($data, null, ['additional_package', 'charge_additional_package'], Console::ES_LEVEL_NOTICE);
        Tools::log('============================', 'additional_package.txt');
        Tools::log('charge_additional_package', 'additional_package.txt');
        Tools::log($data, 'additional_package.txt');

        // 查询基本信息
        $customerId = $order['customer_id'];
        $amount = $order['amount'];
        $title = $order['title'];
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if (!$customer->getId()) {
            Console::get()->log(
                [
					'error' => 'customer not exist',
					'customer_id' => $customerId,
                    'data' => $data,
                ], null,
                ['error', 'additional_package'],
                Console::ES_LEVEL_ERROR
            );
            Tools::log('不存在此用户:' . $customerId, 'additional_package.txt');
			return false;
        }

		// 读取城市配置
		$config = CityConfig::findOne(['city'=>$customer->city]);
		if($config){
			// 转成以前的配置格式
			$month_recharge_limit = $config->additional_package_month_recharge_limit;
			Tools::log('城市额度包充值上限配置:' . $month_recharge_limit, 'additional_package.txt');
		}else{
			// 读取平台配置
			$month_recharge_limit = floatval(Tools::getSystemConfigByPath('wallet/additional_package/month_recharge_limit'));
			Tools::log('平台额度包充值上限配置:' . $month_recharge_limit, 'additional_package.txt');
		}
		// 分析配置
        if (!$month_recharge_limit || $month_recharge_limit <= 0) {
            Console::get()->log(
                [
                    'error' => 'month_recharge_limit config error',
                    'config' => $month_recharge_limit,
					'customer_id' => $customerId,
                    'data' => $data,
                ], null,
                ['error', 'additional_package'],
                Console::ES_LEVEL_ERROR
            );
            Tools::log('month_recharge_limit config_error:' . $month_recharge_limit, 'additional_package.txt');
			return false;
        }

        // 用户剩余额度
        $logs = LeCustomersBalanceAdditionalPackageLog::find()
            ->where(['customer_id' => $customerId])
            ->andWhere(['action' => ['CHARGE', 'REGISTER_ADDITIONAL_PACKAGE']])
            ->andWhere(['>=', 'created_at', 10])
            ->all();
        $charged = 0;
        $log_ids = [];
        /** @var LeCustomersBalanceAdditionalPackageLog $log */
        foreach ($logs as $log) {
            $charged += floatval($log->amount);
            array_push($log_ids, $log->entity_id);
        }
        $month_left = $month_recharge_limit - $charged - $amount;
        if ($month_left < 0) {
			Console::get()->log(
				[
					'warning' => 'Charge too much this month, charged this month:' . $charged . ' Log entity_id in(' . implode(',', $log_ids) . ')',
					'month_recharge_limit' => $month_recharge_limit,
					'customer_id' => $customerId,
					'data' => $data,
				], null,
				['warning', 'additional_package',],
				Console::ES_LEVEL_WARNING
			);
            Tools::log('此用户本月额度已用完,本月已冲:' . $charged . ' Log entity_id in(' . implode(',', $log_ids) . ')', 'additional_package.txt');
			return false;
        }

        // 到此,可以充了
		if(isset($data['action'])){
			$action = $data['action'];
		}else{
			$action = 'CHARGE';
		}
        $result = $customer->addBalanceAdditionalPackage($title, $action, $amount, $order['admin_id'], $order['admin_name']);

		if($result){
			Console::get()->log(
				[
					'info' => 'Charge success, charged this month:' . ($charged + $amount),
					'customer_id'=>$customerId,
					'data' => $data,
				], null,
				['success', 'additional_package'],
				Console::ES_LEVEL_INFO
			);
			Tools::log('充值成功,本月已冲:' . ($charged + $amount), 'additional_package.txt');
			return true;
		}else{
			return false;
		}


    }

	/**
	 * 运营后台直接充值钱包
	 *
	 * $event_data = [
	 * 'customer_id' => $importData['entity_id'],
	 * 'amount' => $charge_balance,
	 * 'title' => '系统赠送',
	 * 'admin_id' => Mage::getSingleton('admin/session')->getUser()->getId(),
	 * 'admin_name' => Mage::getSingleton('admin/session')->getUser()->getUsername(),
	 * ];
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public static function charge_balance($data)
	{
		$order = $data;
		Console::get()->log($data, null, ['balance', 'charge_balance'], Console::ES_LEVEL_NOTICE);
		Tools::log('============================', 'balance.txt');
		Tools::log('charge_balance', 'balance.txt');
		Tools::log($data, 'balance.txt');

		// 查询基本信息
		$customerId = $order['customer_id'];
		$amount = $order['amount'];
		$title = $order['title'];
		/** @var LeCustomers $customer */
		$customer = LeCustomers::findByCustomerId($customerId);
		if (!$customer->getId()) {
			Console::get()->log(
				[
					'error' => 'customer not exist',
					'customer_id' => $customerId,
					'data' => $data,
				], null,
				['error', 'balance'],
				Console::ES_LEVEL_ERROR
			);
			Tools::log('不存在此用户:' . $customerId, 'balance.txt');
			return false;
		}

		// 到此,可以充了
		if(isset($data['action'])){
			$action = $data['action'];
		}else{
			$action = 'CHARGE';
		}
		$result = $customer->addBalance($title, $action, $amount, $order['admin_id'], $order['admin_name']);

		if($result){
			Console::get()->log(
				[
					'info' => 'Charge success, charge amount:' . $amount,
					'customer_id'=>$customerId,
					'data' => $data,
				], null,
				['success', 'balance'],
				Console::ES_LEVEL_INFO
			);
			Tools::log('充值成功,本次已充:' . $amount, 'balance.txt');
			return true;
		}else{
			return false;
		}


	}


    /**
     * update customer's last place order time
     * data contains all of order table fields
     *
     * @param $data
     */
    public function updateLastPlaceOrderAt($data)
    {
        if (isset($data['customer_id'], $data['created_at'])) {
            /** @var LeCustomers $customer */
            $customer = LeCustomers::findOne(['entity_id' => $data['customer_id']]);
            if ($customer) {
                $customer->last_place_order_at = $data['created_at'];
                $customer->last_place_order_id = $data['entity_id'];
                $customer->last_place_order_total = $data['grand_total'];
                if ($customer->save()) {
                    Console::get()->log([
                        $data['created_at'],
                        $data['increment_id'],
                        $data['customer_id'],
                    ], null, ['updateLastPlaceOrderAt', $data['increment_id']], Console::ES_LEVEL_DEBUG);
                } else {
                    Console::get()->log($customer->getErrors(), null, ['updateLastPlaceOrderAt', $data['increment_id']], Console::ES_LEVEL_ERROR);
                }
            } else {
                //should never happens,log exception
                Console::get()->log($data, null, ['updateLastPlaceOrderAt', $data['increment_id']], Console::ES_LEVEL_ERROR);
            }
        } else {
            Console::get()->log($data, null, ['updateLastPlaceOrderAt', $data['increment_id']], Console::ES_LEVEL_ERROR);
        }
    }

    public function couponExpire($data){
        $content = '您有优惠券即将过期，快来看看吧';
        self::pushMessage($data, $content, 'lelaishop://coupon/list');
    }

    public function couponNew($data){
        $promotion = '';
        if(isset($data['promotion'])){
            $promotion = $data['promotion'];
        }
        $content = "送您一张{$promotion}优惠券，快来看看吧";
        self::pushMessage($data, $content, 'lelaishop://coupon/list');
    }

}