<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/7/18
 * Time: 11:44
 */
namespace service\resources\contractor\v1;

use common\models\contractor\ContractorMetrics;
use common\models\contractor\ContractorTaskHistory;
use common\models\contractor\ContractorTasks;
use common\models\contractor\TargetHelper;
use common\models\LeContractor;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\TargetListRequest;
use service\message\contractor\TargetListResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;


/**
 * 业绩中心-指标列表
 *
 * @author zqy
 * @package service\resources\contractor\v1
 */
class TargetList extends Contractor
{
    const TWO_WEEK_DAYS = 14;
    /**
     * 入口
     *
     * @param string $data
     * @return TargetListResponse
     * @throws ContractorException
     */
    public function run($data)
    {
        /** @var TargetListRequest $request */
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

        /* 如果传了TargetContractorId（可能为0：城市指标），而且不等于当前业务员id，则验证TargetContractorId是否在管理范围*/
        if ($request->getTargetContractorId() != $contractor->entity_id && $this->isRegularContractor($contractor)) {
            throw new ContractorException('无权查看', 401);
        }

        $respData['targets'] = (new TargetHelper($contractor, $request->getTargetContractorId(), $request->getCity()))
            ->getTargets($request->getDate(), self::TWO_WEEK_DAYS);
        $response = self::response();
        $response->setFrom(Tools::pb_array_filter($respData));
        return $response;
    }

    /**
     * 是否普通业务员
     *
     * @param LeContractor $contractor
     * @return bool
     */
    private function isRegularContractor(LeContractor $contractor)
    {
        return $contractor->role === self::COMMON_CONTRACTOR;
    }

    /**
     * @return TargetListRequest
     */
    public static function request()
    {
        return new TargetListRequest();
    }

    /**
     * @return TargetListResponse
     */
    public static function response()
    {
        return new TargetListResponse();
    }
}