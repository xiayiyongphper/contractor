<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\ContractorVisitWholesaler;
use common\models\LeCustomers;
use common\models\LeMerchantStore;
use framework\components\Date;
use framework\components\es\Console;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\common\Image;
use service\message\contractor\addVisitRecordBriefRequest;
use service\message\contractor\VisitRecord;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-7-22
 * Time: 上午11:32
 * Email: henryzxj1989@gmail.com
 * 此接口适配APP版本 1.7.x xiayiyong
 */
class addVisitRecordBrief2 extends Contractor
{
    const ACTION_NEW = 1;
    const ACTION_SAVE = 2;

    public function run($data)
    {
        /** @var addVisitRecordBriefRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);


        if (!ContractorPermission::contractorStoreVisitBriefCreatePermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }

        $date = new Date();
        $record = $request->getRecord();
        Tools::log($record, 'xiayy.log');
        // 根据参数 判断是新增的供货商wholesaler的拜访记录还是超市customer的拜访记录
        if ($request->getRole() == 0) {
            $visitRecord = new VisitRecords();
            $storeModel = LeCustomers::find();
        } else {
            $visitRecord = new ContractorVisitWholesaler();
            $storeModel = LeMerchantStore::find();
        }
        $visitRecord->contractor_id = $contractor->getPrimaryKey();
        $visitRecord->contractor_name = $contractor->name;
        $visitRecord->customer_id = $record->getCustomerId() ? $record->getCustomerId() : 0;
        $visitRecord->store_name = $record->getStoreName();
        $visitRecord->created_at = $date->date();
        $visitRecord->visited_at = $date->date();
        $visitRecord->arrival_time = $date->date();// 到达时间
        $visitRecord->is_intended = $record->getIsIntended();
        $visitRecord->visit_way = $record->getVisitWay();// 拜访方式
        if ($visitRecord->visit_way == '上门拜访') {
            $visitRecord->visit_status = 0;// 0拜访中 1已拜访
            $visitRecord->locate_address = $record->getLocateAddress();
            $visitRecord->arrival_address = $record->getLocateAddress();// 到达地址
            $visitRecord->lat = $record->getLat();
            $visitRecord->lng = $record->getLng();
            // 先查询出店铺的经纬度
            $store = $storeModel->where(['entity_id' => $visitRecord->customer_id])->one();
            $arrival_distance = Tools::getDistance($visitRecord->lat, $visitRecord->lng, $store->lat, $store->lng);
            $visitRecord->arrival_distance = $arrival_distance;// 到达距离差
        } else {
            $visitRecord->locate_address = '';
            $visitRecord->arrival_address = '';// 到达地址
            $visitRecord->lat = 0;
            $visitRecord->lng = 0;
            $visitRecord->visit_status = 1;// 0拜访中 1已拜访
            $visitRecord->arrival_distance = 0;// 到达距离差
            $visitRecord->leave_address = '';// 离开地址
            $visitRecord->leave_distance = 0;// 离开定位距离
            $visitRecord->leave_time = $date->date();// 离开时间
            $visitRecord->gallery = '';// 相册也为空
            $visitRecord->visit_content = $record->getVisitContent() ? $record->getVisitContent() : '-';
            $visitRecord->feedback = $record->getFeedback();
            // 同时修改超市或者供应商的最后拜访时间
            if ($request->getRole() == 0) {
                $customer = LeCustomers::find()->where(['entity_id' => $visitRecord->customer_id])->one();
                $customer->last_visited_at = date('Y-m-d H:i:s');// 最后拜访时间
                $customer->save();
            } else {
                $merchant = LeMerchantStore::find()->where(['entity_id' => $visitRecord->customer_id])->one();
                $merchant->last_visited_at = date('Y-m-d H:i:s');// 最后拜访时间
                $merchant->save();
            }
        }
        $visitRecord->visit_purpose = $record->getVisitPurpose();// 拜访目的

        // 拜访结果保存
        $visit_result = 0;
        if ($record->getSeeBoss() == 1) {
            $visit_result += 1 << 3;
        }
        if ($record->getInstallApp() == 1) {
            $visit_result += 1 << 2;
        }
        if ($record->getPlaceOrder() == 1) {
            $visit_result += 1 << 1;
        }
        if ($record->getConveyPromotion() == 1) {
            $visit_result += 1 << 0;
        }
        $visitRecord->visit_result = $visit_result;// 拜访结果

        $gallery = [];
        /** @var Image $image */
        if ($record->getGallery()) {
            foreach ($record->getGallery() as $image) {
                $gallery[] = $image->getSrc();
            }
            $visitRecord->gallery = implode(';', $gallery);// 图片保存
        }

        $visitRecord->save();
        $errors = $visitRecord->getErrors();
        if (count($errors) > 0) {
            Console::get()->log($errors, $this->getTraceId(), [__METHOD__], Console::ES_LEVEL_WARNING);
        } else {
            // 只有超市有图片
            if ($request->getRole() == 0) {
                $store_front_img = ['src' => $visitRecord->getStoreFrontImage()];
            } else {
                $store_front_img = '';
            }
            $visitRecord = Tools::ObjToArr($visitRecord);
            $responseData = $this->convertVisitRecordArray($visitRecord);
            $responseData['store_front_img'] = $store_front_img;
            $response->setFrom(Tools::pb_array_filter($responseData));
        }

        return $response;
    }

    public static function request()
    {
        return new addVisitRecordBriefRequest();
    }

    public static function response()
    {
        return new VisitRecord();
    }

}