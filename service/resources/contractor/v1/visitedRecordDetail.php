<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\ContractorVisitWholesaler;
use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LeCustomersIntention;
use common\models\LeMerchantStore;
use framework\components\ToolsAbstract;
use framework\data\Pagination;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\visitedRecordDetailRequest;
use service\message\contractor\visitedRecordsRequest;
use service\message\contractor\visitedRecordsResponse;
use service\message\contractor\VisitRecord;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use yii\db\ActiveRecord;


/**
 * Class visitedRecordDetail
 * 拜访记录详情
 * @package service\resources\contractor\v1
 */
class visitedRecordDetail extends Contractor
{

    public function run($data)
    {
        /** @var visitedRecordDetailRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);

        $recodeId = $request->getRecordId();
        $role = $request->getRole();

        /** @var VisitRecords $model */
        $model = null;
        if ($role == 0) {  //普通超市
            $model = VisitRecords::find()->where(['entity_id' => $recodeId])->one();
        } else {
            //供应商
            $model = ContractorVisitWholesaler::find()->where(['entity_id' => $recodeId])->one();
        }
        Tools::log($request->toArray(), 'visitedRecordDetail.log');
        Tools::log($model, 'visitedRecordDetail.log');

        if (!$model) {
            ContractorException::visitingRecordIdExist();
        }

        if ($role == 0) {  //普通超市
            $store = Tools::getCustomerBrief($model->customer_id);
        } else {
            //供应商
            $store = Tools::getWholesalerBrief($model->customer_id);
        }

        if (!$store) {
            ContractorException::visitingRecordIdExist();
        }

        $visit_result = str_pad(decbin($model->visit_result), 4, 0);
        $see_boss = $visit_result[0];
        $install_app = $visit_result[1];
        $place_order = $visit_result[2];
        $convey_promotion = $visit_result[3];

        $visitRecord = [
            'record_id' => $model->entity_id,
            'contractor_id' => $model->contractor_id,
            'contractor_name' => $model->contractor_name,
            'customer_id' => $model->customer_id,
            'store_name' => $model->store_name,
            'visit_way' => $model->visit_way,
            'visit_purpose' => $model->visit_purpose,
            'visit_content' => $model->visit_content,
            'feedback' => $model->feedback,
            'created_at' => $model->created_at,
            'visited_at' => $model->visited_at,
            'visit_status' => $model->visit_status,
            'lng' => $model->lng,
            'lat' => $model->lat,
            'status' => $store['status'],
            'locate_address' => $model->locate_address,
            'is_intended' => $model->is_intended,
            'editable' => ToolsAbstract::dateSub(null, $model->created_at) >= 24 * 3600 ? false : true,
            'store_front_img' => ['src' => isset($store['store_front_img']) ? $store['store_front_img'] : ''],
            'arrival_time' => $model->arrival_time,// 到达时间
            'leave_time' => $model->leave_time,// 离开时间
            'use_minutes' => intval(ceil((strtotime($model->leave_time) - strtotime($model->leave_time)) / 60)),// 用时分钟
            'arrival_distance' => $model->arrival_distance,
            'leave_distance' => $model->leave_distance,
            'gallery' => $this->getGallery($model->gallery),
            'see_boss' => $see_boss,
            'install_app' => $install_app,
            'place_order' => $place_order,
            'convey_promotion' => $convey_promotion,
            'arrival_address' => $model->arrival_address,// 到达地址
            'leave_address' => $model->leave_address,// 离开地址
            'store_lat' => $store['lat'],//
            'store_lng' => $store['lng'],// 店铺地址
            'last_ordered_label' => isset($store['last_ordered_label']) ? $store['last_ordered_label'] : '',
            'last_visit_label' => isset($store['last_visit_label']) ? $store['last_visit_label'] : '',
            'classify_tag' => isset($store['classify_tag']) ? $store['classify_tag'] : '',
            'address' => isset($store['address']) ? $store['address'] : '',
            'phone' => isset($store['phone']) ? $store['phone'] : '',
            'visit_task' => isset($store['visit_task']) ? $store['visit_task'] : '',
        ];

        $response->setFrom(Tools::pb_array_filter($visitRecord));
        return $response;
    }

    private function getGallery($gallery)
    {
        $images = [];

        if (!$gallery) {
            return $images;
        }

        $links = explode(';', $gallery);

        if (!$links) {
            return $images;
        }

        foreach ($links as $link) {
            $image['src'] = $link;
            array_push($images, $image);
        }

        return $images;
    }

    public static function request()
    {
        return new visitedRecordDetailRequest();
    }

    public static function response()
    {
        return new VisitRecord();
    }
}