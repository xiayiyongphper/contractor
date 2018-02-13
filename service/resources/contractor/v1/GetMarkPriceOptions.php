<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/7/21
 * Time: 14:45
 */
namespace service\resources\contractor\v1;

use common\components\UserTools;
use common\models\OtherMerchant;
use common\models\Platform;
use framework\components\ToolsAbstract;
use service\message\contractor\MarkPriceOptionsRequest;
use service\message\contractor\MarkPriceOptionsResponse;
use service\models\common\Contractor;

/**
 * 价格上报选项
 * Class GetMarkPriceOptions
 * @package service\resources\contractor\v1
 */
class GetMarkPriceOptions extends Contractor
{
    /**
     * @param string $data
     * @return MarkPriceOptionsResponse
     */
    public function run($data)
    {
        /** @var MarkPriceOptionsRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);

        /* 验证contractor */
        $cityArr = array_filter(explode('|', $contractor->city_list));
        if (!$cityArr) {
            ContractorException::contractorCityListEmpty();
        }

        /* 验证城市参数 */
        if (!in_array((string)$request->getCity(), $cityArr)) {
            throw new ContractorException('不在该城市管理范围', 401);
        }

        $merchants = [];
        $proxyResponse = UserTools::getCityMerchants($request->getCity(), $contractor);
        if ($proxyResponse && $proxyResponse->getMerchant()) {
            foreach ($proxyResponse->getMerchant() as $keyValueItem) {
                $merchants[] = [
                    'name' => $keyValueItem->getValue(),
                    'value' => $keyValueItem->getValue()
                ];
            }
        }

        $otherMerchants = OtherMerchant::findAll(['city' => $request->getCity()]);
        if ($otherMerchants) {
            /** @var OtherMerchant $merchant */
            foreach ($otherMerchants as $merchant) {
                $merchants[] = [
                    'name' => $merchant->name,
                    'value' => $merchant->name
                ];
            }
        }
        $merchants[] = [
            'name' => '其他',
            'value' => '0'
        ];

        $platfroms = [];
        $platfromsResult = Platform::find()->all();
        if ($platfromsResult) {
            /** @var Platform $platfrom*/
            foreach ($platfromsResult as $platfrom) {
                $platfroms[] = [
                    'name' => $platfrom->name,
                    'value' => $platfrom->name
                ];
            }
        }
        $platfroms[] = [
            'name' => '其他',
            'value' => '0'
        ];

        $respData['options'] = [
            [
                'name' => '供应商',
                'value' => '1',
                'child_options' => $merchants
            ],
            [
                'name' => '平台',
                'value' => '2',
                'child_options' => $platfroms
            ]
        ];

        $response = self::response();
        $response->setFrom(ToolsAbstract::pb_array_filter($respData));
        return $response;
    }

    /**
     * @return MarkPriceOptionsRequest
     */
    public static function request()
    {
        return new MarkPriceOptionsRequest();
    }

    /**
     * @return MarkPriceOptionsResponse
     */
    public static function response()
    {
        return new MarkPriceOptionsResponse();
    }
}