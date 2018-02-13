<?php
/**
 * Created by Jason Y. wang
 * User: wangyang
 * Date: 16-7-21
 * Time: 下午5:29
 */

namespace service\resources\contractor\v1;


use common\models\CustomerAuditLog;
use common\models\LeCustomers;
use common\models\LeCustomersAddressBook;
use common\models\LeCustomersIntention;
use framework\components\mq\Customer;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\SaveStoreRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use service\models\customer\Observer;

class saveStoreInfo extends Contractor
{
    public function run($data)
    {
        /** @var SaveStoreRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
        //审核通过时才判断营业执照号
        if($request->getStatus() == 1){
            /** @var LeCustomers $store */
            $storeNo = LeCustomers::find()->where(['business_license_no' => $request->getBusinessLicenseNo()])
                ->andWhere(['<>','entity_id',$request->getStoreId()])->one();
            //从意向环境直接填入的信息，会有问题
//            $storeIntentionNo = LeCustomersIntention::find()->where(['business_license_no' => $request->getBusinessLicenseNo()])
//                ->andWhere(['status' => 0])
//                ->one();

            if($storeNo){
                // 营业执照已被注册
                ContractorException::businessLicenseNoExist();
            }
        }

        //Tools::log($request->getPhone()."----".$request->getStoreId(),'hl.log');
        if(LeCustomers::checkUserDuplicated($request->getPhone(),$request->getStoreId())){
            ContractorException::customerPhoneAlreadyRegistered();
        }

        /** @var LeCustomers $store */
        $store = LeCustomers::find()->where(['entity_id' => $request->getStoreId()])->one();
        $store->username = $request->getUsername();
        $store->area_id = $request->getAreaId();
        $store->address = $request->getAddress();
        $store->detail_address = $request->getDetailAddress();
        $store->store_name = $request->getStoreName();
        $store->storekeeper = $request->getStorekeeper();
        $store->phone = $request->getPhone();
        $store->lat = $request->getLat();
        $store->lng = $request->getLng();
        $store->img_lat = $request->getImgLat();
        $store->img_lng = $request->getImgLng();
        $action = false;
        if($request->getStatus()===1&&$store->status!==1){
            $action = true;
        }
        $store->status = $request->getStatus();
        $store->business_license_no = $request->getBusinessLicenseNo();
        $store->business_license_img = $request->getBusinessLicenseImg();
        $store->store_front_img = $request->getStoreFrontImg();
        $store->store_type = $request->getStoreType() ? $request->getStoreType() : '';
        $store->store_area_new = $request->getStoreAreaNew() ? $request->getStoreAreaNew() : '';
        $store->storekeeper_instore_times = $request->getStorekeeperInstoreTimes();
        Tools::log($request->getStoreType(),'hl.log');
        Tools::log($request->getStoreAreaNew(),'hl.log');
        Tools::log($request->getStorekeeperInstoreTimes(),'hl.log');

        if(count($request->getType()) > 0){
            $type = implode('|',$request->getType());
            $store->type = $type;
        }
        $store->level = $request->getLevel();
        $store->contractor = $contractor->name;
        $store->contractor_id = $contractor->entity_id;
        //审核不通过原因
        $customer_audit_log = new CustomerAuditLog();
        if($request->getNotPassReason()){
            $customer_audit_log->customer_id = $store->entity_id;
            $customer_audit_log->type = 1;
            $customer_audit_log->content = $request->getNotPassReason();
            $customer_audit_log->save();
        }

        if($action){
            // 审核通过
            $store->apply_at = date('Y-m-d H:i:s');
            $store->state = LeCustomers::STATE_PENDING_REVIEW;
            // 审核通过的时候处理赠送逻辑
            if(!Customer::publishApprovedEvent($store->toArray())){
                ToolsAbstract::log($store->toArray(),'publishApprovedEventError.log');
            }
//			Observer::customerCreated($store->toArray());
        }

//        Tools::log($store,'wangyang.log');
        if($store->save()){
            $intentionId = intval($request->getIntentionId());
            if($intentionId){
                LeCustomersIntention::updateAll(['status'=>1], "entity_id=$intentionId");
            }
            //保存用户的收货人信息
            if($receiver = LeCustomersAddressBook::findReceiverCustomerId($store->getId())){
                $receiver->phone = $store->phone;
                $receiver->receiver_name = $store->storekeeper;
                $receiver->updated_at = date('Y-m-d H:i:s');
            }else{
                $receiver = new LeCustomersAddressBook();
                $receiver->customer_id = $store->getId();
                $receiver->phone = $store->phone;
                $receiver->receiver_name = $store->storekeeper;
                $receiver->created_at = date('Y-m-d H:i:s');
                $receiver->updated_at = date('Y-m-d H:i:s');
            }
            if(!$receiver->save()){
                ContractorException::contractorSystemError();
            }
            //Tools::log($store,'wangyang.log');
            return true;
        }else{
            ContractorException::contractorSystemError();
        }
    }

    public static function request(){
        return new SaveStoreRequest();
    }

    public static function response(){
        return true;
    }
}