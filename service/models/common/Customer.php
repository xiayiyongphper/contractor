<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/22
 * Time: 14:17
 */

namespace service\models\common;


use common\models\CustomerAuditLog;
use common\models\LeCustomers;
use common\models\LeCustomersAddressBook;
use common\models\Region;
use common\models\RegionArea;
use framework\components\Date;
use framework\protocolbuffers\Message;
use service\components\Tools;
use service\message\common\SourceEnum;
use service\message\customer\CustomerResponse;
use service\resources\ResourceAbstract;

abstract class Customer extends ResourceAbstract
{
    //验证码过期时间
    const CODE_EXPIRATION_TIME = 60; //60s

    public static function parseRequest($data){
        /** @var Message $request */
        $request = get_called_class()::request();
        $request->parseFromString($data);
        return $request;
    }

    /**
     * Function: getCustomerModel
     * Author: Jason Y. Wang
     * 返回用户模型
     * @param $customerId
     * @param $token
     * @return LeCustomers|null
     */
    function getCustomerModel($customerId,$token){

        if(!$customerId){
            return null;
        }
        /* @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);

//        Tools::log($token,'wangyang.log');
//        Tools::log($customer->auth_token,'wangyang.log');

        if($customer){
            if($this->getSource() == SourceEnum::PCWEB){
                return $customer;
            }elseif($token == $customer->auth_token){
                return $customer;
            }else{
                CustomerException::customerAuthTokenExpired();
            }
        }else{
            CustomerException::customerNotExist();
        }

    }

    /**
     * Function: getCustomerModel
     * Author: Jason Y. Wang
     * 返回用户模型
     * @param $customerId
     * @param $token
     * @return bool
     * @throws CustomerException
     */
    function getCustomerLicense($customerId,$token){

        /* @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if($customer){
            if($customer->auth_token == $token){
                return true;
            }else{
                CustomerException::customerAuthTokenExpired();
            }
        }else{
            CustomerException::customerNotExist();
        }
    }

    /**
     * Function: getCustomerInfo
     * Author: Jason Y. Wang
     * 返回用户信息
     * @param LeCustomers $customer
     * @return CustomerResponse
     */
    function  getCustomerInfo(LeCustomers $customer){
        $response = false;
        if ($customer) {
            $response = new CustomerResponse();
            $responseData = array(
                'customer_id' => $customer->getId(),
                'username' => $customer->username,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'detail_address' => $customer->detail_address,
                'area_id' => $customer->area_id,
                'store_name' => $customer->store_name,
                'auth_token' => $customer->auth_token,
                'lat' => $customer->lat,
                'lng' => $customer->lng,
                'status' => $customer->status,
                'store_area' => $customer->store_area,
                'storekeeper' => $customer->storekeeper,
                'province' => $customer->province,
                'city' => $customer->city,
                'district' => $customer->district,
                'balance' => $customer->balance,
            );

            /** @var Region $region */
            $region = Region::find()->where(['code' => $customer->city])->one();
            if($region){
                $responseData['city_name'] = $region->chinese_name;
            }

            $data = new Date();
            $created_at = $data->date('Y-m-d H:i:s',$customer->created_at);
            $responseData['created_at'] = $created_at;

            //是否填写资料
            if($customer->store_name){
                //已填写
                $responseData['fill_user_info'] = 1;
            }else{
                //未填写
                $responseData['fill_user_info'] = 0;
            }
            //返回收货人信息
            /* @var $addressBook LeCustomersAddressBook */
            $addressBook = LeCustomersAddressBook::findReceiverCustomerId(['customer_id' => $customer->getId()]);
            if($addressBook && $addressBook->getId()){
                $responseData['receiver_name'] = $addressBook->receiver_name;
                $responseData['receiver_phone'] = $addressBook->phone;
            }else{
                $responseData['receiver_name'] = '';
                $responseData['receiver_phone'] = '';
            }
            //审核不通过原因
            $customer_audit_log = CustomerAuditLog::find()
                ->where(['customer_id' => $customer->getId(),'type'=>1])
                ->orderBy(['created_at' => SORT_DESC ])->one();
            if($customer_audit_log){
                $responseData['not_pass_reason'] = $customer_audit_log['content'];
            }else{
                $responseData['not_pass_reason'] = '';
            }

            // 未过期优惠券数量
//			$coupon_available_count = SalesruleUserCoupon::find()
//				->where(['state'=>1])
//				->andWhere(['>=', 'expiration_date', $data->date('Y-m-d H:i:s')])
//				->count();
//			$responseData['coupon_available_count'] = $coupon_available_count;

            /** @var RegionArea $area */
            $area = RegionArea::find()->where(['entity_id' => $customer->area_id])->one();
            if($area){
                $responseData['area_name'] = $area->area_name;
            }
            $responseData['orders_total_price'] = $customer->orders_total_price;
            //$responseData = array_filter($responseData);  //把0也过滤掉了，已后优化
            $response->setFrom(Tools::pb_array_filter($responseData));
        }

        return $response;
    }

    /**
     * Function: getCustomerInfo
     * Author: Jason Y. Wang
     * 返回简要用户信息
     * @param array $customer
     * @return CustomerResponse
     */
    function  getCustomerBriefInfo($customer){
        $response = false;
        if (count($customer)) {
            $response = new CustomerResponse();
            $responseData = array(
                'customer_id' => $customer['entity_id'],
                'username' => $customer['username'],
                'phone' => $customer['phone'],
                'address' => $customer['address'],
                'detail_address' => $customer['detail_address'],
                'area_id' => $customer['area_id'],
                'store_name' => $customer['store_name'],
                'auth_token' => $customer['auth_token'],
                'lat' => $customer['lat'],
                'lng' => $customer['lng'],
                'status' => $customer['status'],
                'store_area' => $customer['store_area'],
                'storekeeper' => $customer['storekeeper'],
                'province' => $customer['province'],
                'city' => $customer['city'],
                'district' => $customer['district'],
                'balance' => $customer['balance'],
                'contractor_id' => $customer['contractor_id'],
                'contractor' => $customer['contractor'],
                'first_order_id' => $customer['first_order_id'],
            );

            $data = new Date();
            $created_at = $data->date('Y-m-d H:i:s',$customer['created_at']);
            $responseData['created_at'] = $created_at;

            $responseData['orders_total_price'] = $customer['orders_total_price'];
            $response->setFrom(Tools::pb_array_filter($responseData));
        }
        return $response;
    }

}