<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 25/1/2016
 * Time: 11:19 AM
 */

namespace service\resources\contractor\v1;

use service\components\Tools;
use service\message\contractor\getRecordShareTextRequest;
use service\message\contractor\getRecordShareTextResponse;
use service\models\common\Contractor;


class getRecordShareText extends Contractor
{
    public function run($data)
    {
        /** @var getRecordShareTextRequest $request */
        $request = $this->request();
        $request->parseFromString($data);

        $response = $this->response();

        $customerId = $request->getCustomerId();

        $responseData = [
            'text' => '我为您推荐了一些待补货的商品，快来看看吧! http://www.lelai.com/lelai/install',
        ];
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    public static function request()
    {
        return new getRecordShareTextRequest();
    }

    public static function response()
    {
        return new getRecordShareTextResponse();
    }
}