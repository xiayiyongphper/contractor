<?php
namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use common\models\LeCustomersIntention;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\CreateCustomerIntentionRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-7-22
 * Time: 上午11:32
 * Email: henryzxj1989@gmail.com
 */
class createCustomersIntention extends Contractor
{

	public function run($data)
	{
	    /** @var CreateCustomerIntentionRequest $request */
        $request = self::parseRequest($data);
//		Tools::log($request->toArray(), 'api_receive.log');
		$contractor = $this->initContractor($request);

        if(!ContractorPermission::contractorStoreIntentionCreatePermission($this->role_permission)){
            ContractorException::contractorPermissionError();
        }

        if(LeCustomersIntention::checkUserByPhone($request->getPhone())){
            ContractorException::customerPhoneAlreadyRegistered();
        }

        $storeNo = LeCustomers::find()->where(['business_license_no' => $request->getBusinessLicenseNo()])->one();
		$storeIntentionNo = LeCustomersIntention::find()->where(['business_license_no' => $request->getBusinessLicenseNo()])->one();
		if($storeNo || $storeIntentionNo){
			// 营业执照已被注册
			ContractorException::businessLicenseNoExist();
		}
		$store = new LeCustomersIntention();
		$store->city = $request->getCity();
		$store->area_id = $request->getAreaId();
		$store->address = $request->getAddress();
		$store->detail_address = $request->getDetailAddress();
		$store->store_name = $request->getStoreName();
		$store->storekeeper = $request->getStorekeeper();
		$store->phone = $request->getPhone();
		$store->lat = $request->getLat();
		$store->lng = $request->getLng();
		$store->business_license_no = $request->getBusinessLicenseNo();
		$store->business_license_img = $request->getBusinessLicenseImg();
		$store->store_front_img = $request->getStoreFrontImg();
		$store->storekeeper_instore_times = $request->getStorekeeperInstoreTimes();
		$store->created_at = date('Y-m-d H:i:s');
        $store->img_lat = $request->getImgLat();
        $store->img_lng = $request->getImgLng();
        $store->store_type = $request->getStoreType();
        $store->store_area_new = $request->getStoreAreaNew();

		if(count($request->getType()) > 0){
			$type = implode('|',$request->getType());
			$store->type = $type;
		}
		$store->level = $request->getLevel();
		$store->contractor = $contractor->name;
		$store->contractor_id = $contractor->entity_id;

		if(!$store->save()){
			//Tools::log($store,'wangyang.log');
			ContractorException::contractorSystemError();
		}
		return true;
	}

	public static function request(){
		return new CreateCustomerIntentionRequest();
	}

	public static function response(){
		return true;
	}

}