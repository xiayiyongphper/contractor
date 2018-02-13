<?php

namespace service\resources\contractor\v1;

use common\models\contractor\VisitRecords;
use common\models\LeContractor;
use service\components\Tools;
use service\message\contractor\getVisitFilterItemsRequest;
use service\message\contractor\getVisitFilterItemsResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


/**
 * Class contractorList
 * 业务员列表2
 * 业务员V1.6版本创建
 * @package service\resources\contractor\v1
 */
class contractorList2 extends Contractor
{

    public function run($data)
    {
        /** @var getVisitFilterItemsRequest $request */
        $request = self::parseRequest($data);
        $contractor_id = $request->getContractorId();
        $location = $request->getLocation();
        $customer_id = $request->getCustomerId();

        $city = $request->getCity();
        if (!$city) {
            ContractorException::contractorCityEmpty();
        }

        $response = self::response();
        $contractor = $this->initContractor($request);

        if ($contractor->role == self::COMMON_CONTRACTOR) {
            //如果是普通业务员，业务员列表里只有自己
            $responseData['contractors'][] = [
                'contractor_id' => $contractor_id,
                'name' => $contractor->name
            ];
        } else {
            //    1 超市模块-超市列表-筛选业务员：不显示“已停用”的业务员。
            //    2 超市模块-超市列表-超市详情页-拜访记录-筛选：不显示“无此超市拜访记录”的业务员。
            //    3 超市模块-超市列表-超市详情页-查看近期订单-筛选业务员：不显示“30天内无此超市订单”的业务员。
            //    4 订单模块-订单列表-筛选业务员：不显示“最近30天没有订单”的业务员。
            //    5 工作模块-全部拜访记录-拜访列表-筛选：不显示“已停用且无拜访记录”的业务员。
            //    6 工作模块-全部拜访记录-拜访列表-查看拜访轨迹-拜访轨迹页-选择业务员：不显示“已停用且无拜访记录”的业务员。
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
                    $contractors = [
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