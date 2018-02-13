<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\ContractorVisitWholesaler;
use common\models\LeCustomers;
use common\models\LeMerchantStore;
use framework\components\Date;
use framework\components\es\Console;
use framework\components\ToolsAbstract;
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
 * 此接口适配APP版本 1.7.x xiayiyong
 */
class addVisitRecord2 extends Contractor
{
    const ACTION_NEW = 1;
    const ACTION_SAVE = 2;

    public function run($data)
    {
        /** @var addVisitRecordRequest $request */
        $request = self::parseRequest($data);

        $response = self::response();
        $contractor = $this->initContractor($request);

//        if (!ContractorPermission::contractorStoreVisitNewCreatePermission($this->role_permission)) {
//            ContractorException::contractorPermissionError();
//        }

        if ($request->getAction() == self::ACTION_SAVE) {
            $record = $request->getRecord();
            $visitRecord = false;
            $saveModel = false;
            if ($record->getRecordId() > 0) {
                // 根据参数 判断是新增的供货商wholesaler的拜访记录还是超市customer的拜访记录
                if ($request->getRole() == 0) {
                    $saveModel = new VisitRecords();
                } else {
                    $saveModel = new ContractorVisitWholesaler();
                }
                $visitRecord = $saveModel::find()->where(['entity_id' => $record->getRecordId()])->one();
            } else {
                // 拜访记录id不存在
                ContractorException::visitingRecordIdExist();
            }

            if (!$visitRecord) {
                Exception::visitRecordNotFound();
            }

            $visitRecord->contractor_id = $contractor->getPrimaryKey();
            $date = new Date();
            $visitRecord->is_intended = $record->getIsIntended();
            if ($visitRecord->visit_way != '上门拜访') {
                $visitRecord->locate_address = '';
                $visitRecord->leave_address = '';
                $visitRecord->leave_time = '';// 离开时间
                $visitRecord->visit_status = 1;// 拜访状态
                // 先查询出店铺的经纬度
                $visitRecord->leave_distance = 0;// 离开距离差
            } else {
                $visitRecord->locate_address = $record->getLocateAddress();
                if ($visitRecord->leave_address == '' && $request->getOperation() == 1) {
                    $visitRecord->leave_address = $visitRecord->locate_address;
                    if (isset($visitRecord->leave_address) && $visitRecord->leave_address) {
                        $visitRecord->leave_time = $date->date();// 离开时间
                        $visitRecord->visit_status = 1;// 拜访状态
                        // 同时修改超市或者供应商的最后拜访时间
                        if ($request->getRole() == 0) {
                            $customer = LeCustomers::find()->where(['entity_id' => $visitRecord->customer_id])->one();
                            $customer->last_visited_at = ToolsAbstract::getDate()->date('Y-m-d H:i:s');// 最后拜访时间
                            $customer->save();
                        } else {
                            $merchant = LeMerchantStore::find()->where(['entity_id' => $visitRecord->customer_id])->one();
                            $merchant->last_visited_at = ToolsAbstract::getDate()->date('Y-m-d H:i:s');// 最后拜访时间
                            $merchant->save();
                        }
                    }
                    $visitRecord->lat = $record->getLat() ?: $visitRecord->lat;
                    $visitRecord->lng = $record->getLng() ?: $visitRecord->lng;
                    // 先查询出店铺的经纬度
                    if ($visitRecord->lat && $visitRecord->lng) {
                        // 根据参数 判断是新增的供货商wholesaler的拜访记录还是超市customer的拜访记录
                        if ($request->getRole() == 0) {
                            $store = LeCustomers::find()->where(['entity_id' => $visitRecord->customer_id])->one();
                        } else {
                            $store = LeMerchantStore::find()->where(['entity_id' => $visitRecord->customer_id])->one();
                        }

                        if (!$store) {
                            throw new ContractorException('没有该超市或者供应商', 40000);
                        }

                        $leave_distance = Tools::getDistance($visitRecord->lat, $visitRecord->lng, $store->lat, $store->lng);
                        $visitRecord->leave_distance = $leave_distance;// 离开距离差
                    }
                }
            }

            $visitRecord->visit_purpose = $record->getVisitPurpose();
            $visitRecord->visit_content = $record->getVisitContent() ? $record->getVisitContent() : '-';
            $visitRecord->feedback = $record->getFeedback();
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
            if ($visit_result > 0) {
                $visitRecord->visit_result = $visit_result;// 拜访结果
            }

            $gallery = [];
            /** @var Image $image */
            if ($record->getGallery()) {
                foreach ($record->getGallery() as $image) {
                    $gallery[] = $image->getSrc();
                }
            }

            $visitRecord->gallery = implode(';', $gallery);

            if ($visitRecord) {
                if ($request->getRole() == 0) {
                    if (!$saveModel->isEditable()) {
                        Exception::visitRecordOutOfEditableTime();
                    }
                }
                $visitRecord->save();
            }
            Tools::log("record_id====" . $visitRecord->contractor_id, 'hl.log');
            $errors = $visitRecord->getErrors();
            if (count($errors) > 0) {
                Console::get()->log($errors, $this->getTraceId(), [__METHOD__], Console::ES_LEVEL_WARNING);
            } else {
                // 只有超市有图片
                if ($request->getRole() == 0) {
                    $store_front_img = ['src' => $saveModel->getStoreFrontImage()];
                } else {
                    $store_front_img = '';
                }
                $visitRecord = Tools::ObjToArr($visitRecord);
                $responseData = $this->convertVisitRecordArray($visitRecord);
                $responseData['store_front_img'] = $store_front_img;
                Tools::log($responseData, 'xiayy.log');
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