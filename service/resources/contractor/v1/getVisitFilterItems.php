<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\ContractorVisitWholesaler;
use common\models\LeContractor;

use service\components\ContractorPermission;
use service\components\Tools;
use service\message\contractor\getVisitFilterItemsRequest;
use service\message\contractor\getVisitFilterItemsResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Created by PhpStorm.
 * User: hongliang
 * Date: 17-3-29
 * Time: 上午11:43
 */

/**
 * Class getVisitFilterItems
 * 拜访记录列表
 * @package service\resources\contractor\v1
 */
class getVisitFilterItems extends Contractor
{
    public function run($data)
    {
        /** @var getVisitFilterItemsRequest $request */
        $request = self::parseRequest($data);
        $contractor_id = $request->getContractorId();
        $location = $request->getLocation();
        $customer_id = $request->getCustomerId();
        $response = self::response();
        $contractor = $this->initContractor($request);
        $city = $request->getCity();
        if (!ContractorPermission::contractorVisitStoreListCreatePermission($this->role_permission)) {
            ContractorException::contractorPermissionError();
        }

        $responseData = [
            'visit_purpose_options' => ['超市注册', '督促超市下单', '超市调研回访', '售后处理', '其他',],
            'visit_way_options' => ['上门拜访', '电话拜访', '微信拜访',],
            'contractors' => []
        ];

        if ($contractor->role == self::COMMON_CONTRACTOR) {
            //如果是普通业务员，业务员列表里只有自己
            $responseData['contractors'][] = [
                'contractor_id' => $contractor_id,
                'name' => $contractor->name
            ];
        } else {
            switch ($location) {
                case 1: //完成
                    $contractors = LeContractor::find()->select('entity_id as contractor_id,name')
                        ->where(['city' => $city])
                        ->andWhere(['status' => LeContractor::CONTRACTOR_STATUS_NORMAL])
                        ->asArray()->all();
                    break;
                case 2: //完成
                    $contractor_ids = VisitRecords::find()->select('contractor_id')
                        ->where(['customer_id' => $customer_id])->groupBy('contractor_id')->column();
                    $contractors = LeContractor::find()->select('entity_id as contractor_id,name')
                        ->where(['city' => $city])
                        ->andWhere(['in', 'entity_id', $contractor_ids])
                        ->asArray()->all();
                    break;
                case 3: //完成  一个超市的订单只能是自己的
                    $contractors[] = [
                        'contractor_id' => $contractor_id,
                        'name' => $contractor->name
                    ];
                    break;
                case 4:
                    $contractors = LeContractor::find()->select('entity_id as contractor_id,name')
                        ->where(['city' => $city])
                        ->andWhere(['status' => LeContractor::CONTRACTOR_STATUS_NORMAL])
                        ->asArray()->all();
                    break;
                case 5: //完成
                    //有拜访记录
                    $visited_contractor_ids = VisitRecords::find()->select('contractor_id')
                        ->where(['customer_id' => $customer_id])->groupBy('contractor_id')->column();
                    //显示启用或者有拜访记录的业务员
                    $contractors = LeContractor::find()->select('entity_id as contractor_id,name')
                        ->where(['city' => $city])
                        ->andWhere(['or', ['in', 'entity_id', $visited_contractor_ids], ['status' => LeContractor::CONTRACTOR_STATUS_NORMAL]])
                        ->asArray()->all();

                    break;
                case 6: //完成
                    //有拜访记录
                    $visited_contractor_ids = VisitRecords::find()->select('contractor_id')
                        ->where(['customer_id' => $customer_id])->groupBy('contractor_id')->column();
                    //显示启用或者有拜访记录的业务员
                    $contractors = LeContractor::find()->select('entity_id as contractor_id,name')
                        ->where(['city' => $city])
                        ->andWhere(['or', ['in', 'entity_id', $visited_contractor_ids], ['status' => LeContractor::CONTRACTOR_STATUS_NORMAL]])
                        ->asArray()->all();
                    break;
                case 7: //供应商的完成
                    //供应商的有拜访记录
                    $visited_contractor_ids = ContractorVisitWholesaler::find()->select('contractor_id')
                        ->where(['customer_id' => $customer_id])->groupBy('contractor_id')->column();
                    //显示启用或者有拜访记录的业务员
                    $contractors = LeContractor::find()->select('entity_id as contractor_id,name')
                        ->where(['city' => $city])
                        ->andWhere(['or', ['in', 'entity_id', $visited_contractor_ids], ['status' => LeContractor::CONTRACTOR_STATUS_NORMAL]])
                        ->asArray()->all();
                    break;
                default:
                    $contractors = LeContractor::find()->select('entity_id as contractor_id,name')
                        ->where(['city' => $city])
                        ->andWhere(['status' => LeContractor::CONTRACTOR_STATUS_NORMAL])
                        ->asArray()->all();
                    break;
            }

            $responseData['contractors'] = $contractors;
        }

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;

    }

    public static function request()
    {
        return new getVisitFilterItemsRequest();
    }

    public static function response()
    {
        return new getVisitFilterItemsResponse();
    }
}
