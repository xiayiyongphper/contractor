<?php
/**
 * Created by Jason Y. wang
 * User: wangyang
 * Date: 16-7-21
 * Time: 下午5:29
 */

namespace service\resources\contractor\v1;


use common\models\LeCustomers;
use common\models\LeCustomersIntention;

use service\components\Tools;
use service\message\contractor\SaveStoreRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class saveStoreIntention extends Contractor
{
    public function run($data)
    {
        /** @var SaveStoreRequest $request */
        $request = self::parseRequest($data);
        /** @var LeCustomers $store */
        $storeNo = LeCustomers::find()->where(['business_license_no' => $request->getBusinessLicenseNo()])->one();
        $storeIntentionNo = LeCustomersIntention::find()->where(['business_license_no' => $request->getBusinessLicenseNo()])
            ->andWhere(['<>','entity_id',$request->getStoreId()])
            ->andWhere(['status' => 0])->one();
        if($storeNo || $storeIntentionNo) {
            // 营业执照已被注册
            ContractorException::businessLicenseNoExist();
        }

        $contractor = $this->initContractor($request);
        /** @var LeCustomersIntention $store */
        $store = LeCustomersIntention::find()->where(['entity_id' => $request->getStoreId()])->one();
		if(!$store){
			ContractorException::storeIntentionNotFound();
		}

		$newData = $request->toArray();
		$allow = ['area_id','address','detail_address','store_name','storekeeper','phone','lat','lng','business_license_no','business_license_img','store_front_img','store_type','store_area_new'];
		foreach ($newData as $key=>$value) {
			if( in_array($key, $allow) ){
				$store->setAttribute($key, $value);
			}
		}

        if(count($request->getType()) > 0){
            $type = implode('|',$request->getType());
            $store->type = $type;
        }
        $store->level = $request->getLevel();
        $store->contractor = $contractor->name;
        $store->contractor_id = $contractor->entity_id;
        $store->storekeeper_instore_times = $request->getStorekeeperInstoreTimes();

		if(!$store->save()){
			//Tools::log($store,'wangyang.log');
			ContractorException::contractorSystemError();
		}
		return true;
    }

    public static function request(){
        return new SaveStoreRequest();
    }

    public static function response(){
        return true;
    }
}