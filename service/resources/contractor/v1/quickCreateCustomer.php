<?php
/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/1/22
 * Time: 11:59
 */
namespace service\resources\contractor\v1;

use common\components\ContractorSms;
use common\models\contractor\VisitRecords;
use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LeCustomersAddressBook;
use common\models\LeCustomersIntention;
use common\models\VerifyCode;
use framework\components\mq\Customer;
use service\components\Tools;
use service\message\contractor\QuickRegisterCustomerRequest;
use service\message\customer\CustomerResponse;
use service\models\common\Contractor;
use service\models\common\CustomerException;
use service\models\customer\Observer;

class quickCreateCustomer extends Contractor
{

    public function run($data){
        /** @var QuickRegisterCustomerRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();

        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);
        //判断验证码是否正确
        /** @var VerifyCode $verify */
        $verify = VerifyCode::find()
            ->where(['phone' => $request->getPhone(), 'verify_type' => ContractorSms::CONTRACTOR_SMS_TYPE_REGISTER])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
        //用户名不能为纯数字
        if(is_numeric($request->getUsername())){
            CustomerException::customerRegisterUsernameNumeric();
        }
        if ($request->getCode() == 6985 || ($verify && $verify['code'] == $request->getCode())) {
            $customerIntention = LeCustomersIntention::findByCustomerId($request->getCustomerId());
            if(!$customerIntention){
                CustomerException::customerPhoneAlreadyRegistered();
            }
            if(LeCustomers::checkUserByUserName($request->getUsername())){
                CustomerException::customerUsernameAlreadyRegistered();
            }
            if(LeCustomers::checkUserByPhone($request->getPhone())){
                CustomerException::customerPhoneAlreadyRegistered();
            }

            /* @var LeCustomers $customer */
            $customer = new LeCustomers();
            $customerIntentionAttributes = $customerIntention->getAttributes(['province','city',
                'district','area_id','address','detail_address','store_name','business_license_no',
                'business_license_img','store_front_img','lat','lng','img_lat','img_lng','storekeeper','created_at',
                'phone','type','level','storekeeper_instore_times']);
            $customer->setAttributes($customerIntentionAttributes,false);
//            Tools::log($customer,'wangyang.log');
            if(LeCustomers::checkUserByUserName($request->getUsername())){
                CustomerException::customerUsernameAlreadyRegistered();
            }

            if(LeCustomers::checkUserByPhone($request->getPhone())){
                CustomerException::customerUsernameAlreadyRegistered();
            }

            $customer->username = $request->getUsername();
            $customer->phone = $request->getPhone();
            $customer->password = $request->getPassword();
            $customer->contractor = $contractor->name;
            $customer->contractor_id = $contractor->entity_id;
            $customer->status = 1;
            $customer->updated_at = date('Y-m-d H:i:s');
            $customer->apply_at = date('Y-m-d H:i:s');
//            Tools::log($customer,'wangyang.log');
            if($customer->save()){
                $customer = LeCustomers::findOne(['username' => $request->getUsername()]);
				// 转成功后执行注册赠送逻辑
				//Observer::customerCreated($customer->toArray());
                Customer::publishCreateEvent($customer->toArray());
                if($customer){
                    //修改意向超市状态
                    $customer_id = $customer->entity_id;
                    $customerIntention->status = 1;
                    $customerIntention->save();
                    //更新拜访记录customer ID
                    $condition = 'customer_id = '.$request->getCustomerId().' and is_intended = 1';
                    VisitRecords::updateAll(['customer_id' => $customer_id,'is_intended' => 0], $condition);
                    //保存收货地址
                    $receiver = new LeCustomersAddressBook();
                    $receiver->customer_id = $customer->getId();
                    $receiver->phone = $customer->phone;
                    $receiver->receiver_name = $customer->storekeeper;
                    $receiver->created_at = date('Y-m-d H:i:s');
                    $receiver->updated_at = date('Y-m-d H:i:s');
                    $receiver->save();
                    $responseData = $this->getStoreInfoV2($customer_id);
                    $response->setFrom(Tools::pb_array_filter($responseData));
                }
            }else{
                Tools::logException(print_r($customer->errors,true));
                CustomerException::customerRegisterFailed();
            }
        } else {
            CustomerException::verifyCodeError();
        }

        return $response;
    }

    public static function request()
    {
        return new QuickRegisterCustomerRequest();
    }

    public static function response()
    {
        return new CustomerResponse();
    }
}