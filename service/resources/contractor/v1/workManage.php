<?php
/**
 * Created by PhpStorm.
 * Date: 2017/3/29
 * Time: 20:33
 */

namespace service\resources\contractor\v1;

use service\components\Tools;
use service\message\contractor\workManageRequest;
use service\message\contractor\workManageResponse;
use service\models\common\Contractor;

class workManage extends Contractor
{
    public function run($data)
    {
        $request = self::parseRequest($data);
        $response = self::response();

//        $contractor = $this->initContractor($request);
//        $city = $request->getCity();

        $quick_entry = [
            [
                'name' => '供应商拜访记录',
                'sub_name' => '',
                'schema' => 'lelaibd://visit/WholesalerList'
            ],
            [
                'name' => '超市拜访计划',
                'sub_name' => '',
                'schema' => 'lelaibd://visit/plan'
            ],
            [
                'name' => '超市路线规划',
                'sub_name' => '',
                'schema' => 'lelaibd://customer/group'
            ],
            [
                'name' => '线下标的商品',
                'sub_name' => '收集非平台供货商价格',
                'schema' => 'lelaibd://product/price'
            ],
            [
                'name' => '优惠券发放历史',
                'schema' => 'lelaibd://coupon/sendHistory'
            ]
        ];

        if(version_compare($this->getAppVersion(),'1.9','<')){
            array_unshift($quick_entry,[
                'name'     => '超市拜访记录',
                'sub_name' => '',
                'schema'   => 'lelaibd://visit/list'
            ]);
        }

        $responseData['quick_entry'] = $quick_entry;

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new workManageRequest();
    }

    public static function response()
    {
        return new workManageResponse();
    }
}