<?php
/**
 * Created by Jason Y. wang
 * User: wangyang
 * Date: 16-7-21
 * Time: 下午5:29
 */

namespace service\resources\contractor\v1;


use common\models\CustomerLevel;
use common\models\CustomerType;
use common\models\LeContractor;
use common\models\RegionArea;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\EditStoreInfoRequest;
use service\message\contractor\EditStoreInfoResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

class EditStoreInfo extends Contractor
{
    public function run($data)
    {
        /** @var EditStoreInfoRequest $request */
        $request = self::parseRequest($data);
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);

        if (!ContractorPermission::contractorEditStorePermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }



//        Tools::log($contractor, 'hl.log');
        $store = [];
        if ($request->getStoreId()) {
            $customer_style = $request->getStoreStyle();
            $store = $this->getStoreInfoV2($request->getStoreId(), $customer_style);
        }

        if($contractor->role == Contractor::COMMON_CONTRACTOR && isset($store['contractor_id']) && $store['contractor_id'] && $store['contractor_id'] != $contractor->entity_id){
            ContractorException::contractorStoreInfoForbidden();
        }

        $store_types = ["便利店","连锁便利店","餐饮店","烟酒店","网吧酒吧","批零店","其他"];
        $store_areas = ["30平米以下","30-60平米","60-100平米","100平米以上"];

        //该城市全部区域
        $city = $request->getCity();
        if($city < 1){
            if(!empty($store)){
                $city = $store['city'];
            }else{
                $city = $contractor->city;
            }
        }
        Tools::log('final_city ===== '.$city, 'hl.log');
        $region_areas = RegionArea::findAll(['city' => $city]);
        $areas = [];
        /** @var RegionArea $region_area */
        foreach ($region_areas as $region_area) {
            $area['key'] = $region_area->entity_id;
            $area['value'] = $region_area->area_name;
            $areas[] = $area;
        }
        //全部类型
        $customer_types = CustomerType::find()->all();
        $types = [];
        /** @var CustomerType $customer_type */
        foreach ($customer_types as $customer_type) {
            $type['key'] = $customer_type->entity_id;
            $type['value'] = $customer_type->type;
            $types[] = $type;
        }
        //全部等级
        $customer_levels = CustomerLevel::find()->all();
        $levels = [];
        /** @var CustomerLevel $customer_level */
        foreach ($customer_levels as $customer_level) {
            $level['key'] = $customer_level->entity_id;
            $level['value'] = $customer_level->level;
            $levels[] = $level;
        }

        $responseData = [
            'store' => $store,
            'areas' => $areas,
            'types' => $types,
            'levels' => $levels,
            'store_types' => $store_types,
            'store_areas' => $store_areas,
            'operations' => [
                [
                    'key' => 2,
                    'value' => '审核不通过'
                ],
                [
                    'key' => 1,
                    'value' => '审核通过'
                ]
            ]
        ];
        $response = new EditStoreInfoResponse();
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new EditStoreInfoRequest();
    }

    public static function response()
    {
        return new EditStoreInfoResponse();
    }

}