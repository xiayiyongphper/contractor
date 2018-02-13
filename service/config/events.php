<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/29
 * Time: 15:45
 */
return [
    'events' => [
        'order_confirm' => [
            'push' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'orderConfirm',
            ],
        ],
        'order_decline' => [
			// 商家拒单,退回钱包余额
			'return_balance' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'return_balance',
			],
            'push' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'orderDecline',
            ],
        ],
        'order_agree_cancel' => [
			// 商家同意取消订单,退回钱包余额
			'return_balance' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'return_balance',
			],
            'push' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'orderAgreeCancel',
            ],
        ],
		// 商家拒绝取消订单
        'order_reject_cancel' => [
            'push' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'orderRejectCancel',
            ],
        ],
		// 超市拒绝收货
		'order_reject' => [
			'return_balance' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'return_balance',
			],
		],
        'order_add_comment' => [
            'push' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'orderAddComment',
            ],
        ],
        'push_notification' => [
            'push' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'pushNotification',
            ],
        ],

        'order_new' => [
        	// 用户首单标记
			'first_order_at' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'first_order_at',
			],
			// 每月首单标记
			'act_monthFirstOrder_orderNew' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'act_monthFirstOrder_orderNew',
			],
			// 下单消费钱包余额
			'balance_consume' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'balance_consume',
			],
			'last_place_order_at' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'updateLastPlaceOrderAt',
			],
        ],
        'order_cancel' => [
			// 每月首单标记
            'act_monthFirstOrder_orderCancel' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'act_monthFirstOrder_orderCancel',
            ],
			// 取消订单,退回钱包余额
			'return_balance' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'return_balance',
			]
        ],
		'order_pending_comment' => [
			// 订单完成,返现
			'rebates_add' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'rebates_add',
			],
			// 订单完成,额度包转钱包
			'additional_package_to_balance' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'additional_package_to_balance',
			],
		],
		'charge_additional_package' => [
			// 运营后台导入操作充值额度包
			'charge_additional_package' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'charge_additional_package',
			],
		],
		'charge_balance' => [
			// 运营后台导入操作充值钱包
			'charge_balance' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'charge_balance',
			],
		],
		'register'=>[
			// 运营后台审核通过用户
			'approved' => [
				'class' => 'service\models\customer\Observer',
				'method' => 'customerCreated',
			],
		],
        'coupon_expire' => [
            //优惠券即将过期
            'expire_push' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'couponExpire',
            ],
        ],
        'coupon_new' => [
            //系统赠送优惠券
            'expire_push' => [
                'class' => 'service\models\customer\Observer',
                'method' => 'couponNew',
            ],
        ]
    ],
];