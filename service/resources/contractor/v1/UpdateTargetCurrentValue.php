<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/7/19
 * Time: 17:07
 */
namespace service\resources\contractor\v1;

use common\models\contractor\ContractorTaskHistory;
use common\models\contractor\ContractorTasks;
use common\models\LeContractor;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\TargetCenterRequest;
use service\message\contractor\TargetCenterResponse;
use service\message\contractor\UpdateTargetCurrentValueRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use yii\base\Exception;


/**
 * 业绩中心-手动更新指标当前值
 *
 * @author zqy
 * @package service\resources\contractor\v1
 */
class UpdateTargetCurrentValue extends Contractor
{
    /**
     * 入口
     *
     * @param string $data
     * @return null
     * @throws ContractorException
     */
    public function run($data)
    {
        /** @var UpdateTargetCurrentValueRequest $request */
        $request = self::parseRequest($data);
        $contractor = $this->initContractor($request);

        /* 验证contractor */
        $cityArr = array_filter(explode('|', $contractor->city_list));
        if (!$cityArr) {
            ContractorException::contractorCityListEmpty();
        }

        /* 验证参数 */
        if (empty($request->getItemValue()) || empty($request->getItemValue()->getCity())
            || empty($request->getItemValue()->getMetricId()) || empty($request->getDate())
            || !preg_match('/^\d{4}-\d{2}/', $request->getDate())
        ) {
            ContractorException::invalidParam();
        }

        $target = $request->getItemValue();
        if (!in_array((string)$target->getCity(), $cityArr)) {
            throw new ContractorException('不在该城市管理范围', 401);
        }

        if ($this->isRegularContractor($contractor)) {
            throw new ContractorException('无权设置', 401);
        }

        $result = ContractorTaskHistory::saveManualValue(
            $target->getHistoryId(),
            $target->getCity(),
            $target->getContractorId(),
            $target->getMetricId(),
            $request->getDate(),
            $target->getCurrentValue()
        );
        if (!$result) {
            throw new \Exception('更新失败');
        }

        $response = self::response();
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
        return (string)$contractor->role === self::COMMON_CONTRACTOR;
    }

    /**
     * @return UpdateTargetCurrentValueRequest
     */
    public static function request()
    {
        return new UpdateTargetCurrentValueRequest();
    }

    /**
     * @return TargetCenterResponse
     */
    public static function response()
    {
        return null;
    }
}