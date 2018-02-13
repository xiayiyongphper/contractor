<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use framework\components\Date;
use framework\components\es\Console;
use service\components\ContractorPermission;
use service\components\Tools;
use service\message\common\Image;
use service\message\contractor\addVisitRecordRequest;
use service\message\contractor\VisitRecord;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use service\resources\Exception;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-7-22
 * Time: 上午11:32
 * Email: henryzxj1989@gmail.com
 */
class addVisitRecord extends Contractor
{
    const ACTION_NEW = 1;
    const ACTION_SAVE = 2;

    public function run($data)
    {
        /** @var addVisitRecordRequest $request */
        $request = self::parseRequest($data);

        $response = self::response();
        $contractor = $this->initContractor($request);

        if (!ContractorPermission::contractorStoreVisitNewCreatePermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }

        if ($request->getAction() == self::ACTION_SAVE) {
            $record = $request->getRecord();
            $isNewRecord = true;
            $visitRecord = false;
            if ($record->getRecordId() > 0) {
                $visitRecord = VisitRecords::findOne(['entity_id' => $record->getRecordId()]);
                if ($visitRecord) {
                    $isNewRecord = false;
                }
            }

            if ($isNewRecord) {
                $visitRecord = new VisitRecords();
            }

            if (!$visitRecord) {
                Exception::visitRecordNotFound();
            }

            if ($isNewRecord) {
                $visitRecord->contractor_id = $contractor->getPrimaryKey();
                $visitRecord->contractor_name = $contractor->name;
                $visitRecord->customer_id = $record->getCustomerId() ? $record->getCustomerId() : 0;
                $visitRecord->store_name = $record->getStoreName();
                $date = new Date();
                $visitRecord->created_at = $date->date();
                $visitRecord->visited_at = $date->date();
                $visitRecord->is_intended = $record->getIsIntended();
                $visitRecord->locate_address = $record->getLocateAddress();
                $visitRecord->lat = $record->getLat();
                $visitRecord->lng = $record->getLng();
            }
            $visitRecord->visit_purpose = $record->getVisitPurpose();
            $visitRecord->visit_way = $record->getVisitWay();
            $visitRecord->visit_content = $record->getVisitContent() ? $record->getVisitContent() : '-';
            $visitRecord->feedback = $record->getFeedback();
            $gallery = [];
            /** @var Image $image */
            foreach ($record->getGallery() as $image) {
                $gallery[] = $image->getSrc();
            }
            $visitRecord->gallery = implode(';', $gallery);

            if ($visitRecord) {
                if (!$visitRecord->isEditable()) {
                    Exception::visitRecordOutOfEditableTime();
                }
                $visitRecord->save();
            }
            Tools::log("record_id====" . $visitRecord->contractor_id, 'hl.log');
            $errors = $visitRecord->getErrors();
            if (count($errors) > 0) {
                Console::get()->log($errors, $this->getTraceId(), [__METHOD__], Console::ES_LEVEL_WARNING);
            } else {
                $responseData = $this->convertVisitRecordArray($visitRecord);
                Tools::log($responseData, 'hl.log');
                $responseData['store_front_img'] = ['src' => $visitRecord->getStoreFrontImage()];
                $response->setFrom(Tools::pb_array_filter($responseData));
            }
        } else {
            $data = Tools::getAssetsFile('visit_purpose.json', true);
            if (is_array($data) && count($data) > 0) {
                $options = [];
                foreach ($data as $key => $value) {
                    $options[] = [
                        'key' => $key,
                        'value' => $value,
                    ];
                }
                $response->setFrom(Tools::pb_array_filter(
                    [
                        'visit_purpose_options' => $options,
                    ]
                ));
            }

            $visitWay = [
                [
                    'key' => 1,
                    'value' => '上门拜访',
                ],
                [
                    'key' => 2,
                    'value' => '电话拜访',
                ],
                [
                    'key' => 3,
                    'value' => '微信拜访',
                ],
            ];
            $response->setFrom(Tools::pb_array_filter(
                [
                    'visit_way_options' => $visitWay,
                ]
            ));

        }
        return $response;
    }

    public static function request()
    {
        return new addVisitRecordRequest();
    }

    public static function response()
    {
        return new VisitRecord();
    }

}