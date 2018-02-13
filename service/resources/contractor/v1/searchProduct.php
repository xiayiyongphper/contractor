<?php
/**
 * Created by PhpStorm.
 * User: zgr0629
 * Date: 21/1/2016
 * Time: 5:58 PM
 */

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use framework\message\Message;
use service\components\ElasticSearchExt;
use service\components\Tools;
use service\message\merchant\searchProductRequest;
use service\message\merchant\searchProductResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class searchProduct extends Contractor
{

    /**
     * Function: run
     * Author: Jason Y. Wang
     * 加入sphinx搜索
     * @param Message $data
     * @return null|searchProductResponse
     */
    public function run($data)
    {
        /** @var searchProductRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);
        $customer = LeCustomers::findOne(['entity_id' => $request->getCustomerId()]);

        if (!$customer || $customer->contractor_id != $contractor->entity_id) {
            ContractorException::customerNotMatch();
        }

        $search = new ElasticSearchExt($customer->city);
        $search->area_id = $customer->area_id;

        $products = $search->search($request);

        return $products;

    }

    public static function request()
    {
        return new searchProductRequest();
    }

    public static function response()
    {
        return new searchProductResponse();
    }
}