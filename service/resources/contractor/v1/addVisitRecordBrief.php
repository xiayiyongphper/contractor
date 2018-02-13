<?php
namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use framework\components\Date;
use framework\components\es\Console;
use service\components\ContractorPermission;
use service\components\Tools;
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
 */
class addVisitRecordBrief extends Contractor
{
    const ACTION_NEW = 1;
    const ACTION_SAVE = 2;

    public function run($data)
    {
        /** @var addVisitRecordBriefRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        $contractor = $this->initContractor($request);

//        Tools::log($this->role_permission,'wangyang.log');

        if (!ContractorPermission::contractorStoreVisitBriefCreatePermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }

        $date = new Date();
        $record = $request->getRecord();
        $visitRecord = new VisitRecords();
        $visitRecord->contractor_id = $contractor->getPrimaryKey();
        $visitRecord->contractor_name = $contractor->name;
        $visitRecord->customer_id = $record->getCustomerId() ? $record->getCustomerId() : 0;
        $visitRecord->store_name = $record->getStoreName();
        $visitRecord->created_at = $date->date();
        $visitRecord->visited_at = $date->date();
        $visitRecord->is_intended = $record->getIsIntended();
        $visitRecord->locate_address = $record->getLocateAddress();
        $visitRecord->lat = $record->getLat();
        $visitRecord->lng = $record->getLng();
        $visitRecord->save();
        $errors = $visitRecord->getErrors();
        if (count($errors) > 0) {
            Console::get()->log($errors, $this->getTraceId(), [__METHOD__], Console::ES_LEVEL_WARNING);
        } else {
            $responseData = $this->convertVisitRecordArray($visitRecord);
            $responseData['store_front_img'] = ['src' => $visitRecord->getStoreFrontImage()];
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