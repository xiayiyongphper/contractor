<?php

namespace service\resources\contractor\v1;

use common\components\ContractorSms;
use common\models\LeCustomers;
use common\models\LeCustomersAddressBook;
use common\models\RecordList;
use common\models\RecordProducts;
use common\models\VerifyCode;
use service\components\Proxy;
use service\message\sales\OrderReviewRequest;
use service\message\sales\OrderReviewResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */
class orderReview extends Contractor
{

    public function run($data)
    {
        /** @var OrderReviewRequest $request */
        $request = self::request();
        $request->parseFromString($data);
        $this->initContractor($request);

        //判断下单码  如果该清单已经验证过，则直接转发到core。如果没有验证过，则抛出异常，业务员弹出输入下单码按钮
        $recordId = $request->getRecordId();
        $record_wholesalers_id = $request->getRecordWholesalersId();

        if (!$recordId || !$record_wholesalers_id) {
            ContractorException::recordListNotExist();
        }

        $record = RecordList::findOne(['entity_id' => $recordId]);
        //判断是否还有商品
        $record_wholesalers = RecordProducts::find()->where(['record_wholesaler_id' => $record_wholesalers_id])->one();

        if (!$record || !$record_wholesalers) {
            ContractorException::recordListNotExist();
        }

        $customer = LeCustomers::findByCustomerId($record->customer_id);

        $request->setAuthToken($customer->auth_token);
        //到此验证码正确，开始转发
        return $this->orderReviewByCore($request);
    }

    private function orderReviewByCore($request)
    {
        /** @var OrderReviewResponse $response */
        $orderResponse = Proxy::sendRequest('sales.orderReview1', $request);
        $response = self::response();
        $response->parseFromString($orderResponse->getPackageBody());
        return $response;
    }

    public static function request()
    {
        return new OrderReviewRequest();
    }

    public static function response()
    {
        return new OrderReviewResponse();
    }
}
