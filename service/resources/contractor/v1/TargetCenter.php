<?php
/**
 * Created by PhpStorm.
 * User: ZQY
 * Date: 2017/7/17
 * Time: 19:59
 */
namespace service\resources\contractor\v1;

use common\models\contractor\ContractorTasks;
use common\models\LeContractor;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\TargetCenterRequest;
use service\message\contractor\TargetCenterResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use yii\base\Exception;


/**
 * 业绩中心
 *
 * @author zqy
 * @package service\resources\contractor\v1
 */
class TargetCenter extends Contractor
{
    /**
     * 入口
     *
     * @param string $data
     * @return TargetCenterResponse
     * @throws ContractorException
     */
    public function run($data)
    {
        /** @var TargetCenterRequest $request */
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

        /* 获取最近有设置指标的月份 */
        $date = $request->getDate();
        if (!$date) {
            if ($this->isRegularContractor($contractor)) {
                $task = ContractorTasks::getLastTask($request->getCity(), $contractor->entity_id);
            } else {
                $task = ContractorTasks::getLastTask($request->getCity(), 0); // 0：城市指標
            }
            $date = $task ? preg_replace('/(\d{4})(\d+)/', '$1-$2', $task->month) : ToolsAbstract::getDate()->date('Y-m');
        }

        /* 获取业务员列表，获取该城市的和当月设置过指标的业务员 */
        $contractorList = [];
        if (!$this->isRegularContractor($contractor)) {
            $condArr = [
                'status' => LeContractor::CONTRACTOR_STATUS_NORMAL,
                'city' => $request->getCity()
            ];

            $ids = ContractorTasks::find()->select('distinct(owner_id)')
                ->where([
                    'city' => $request->getCity(),
                    'month' => str_replace('-', '', $date)
                ])->column();

            if ($ids) {
                $contractorList = LeContractor::find()
                    ->select('entity_id AS contractor_id, name')
                    ->where([
                        'entity_id' => $ids,
                    ])->asArray()->all();
            }

            $contractorList = $contractorList ?: [];
            $excludeIds = array_column($contractorList, 'contractor_id');
            array_push($excludeIds, $contractor->entity_id);
            $normalContractors = LeContractor::find()
                ->select('entity_id AS contractor_id, name')->alias('l')
                ->join('INNER JOIN', 'auth_assignment aa', 'l.entity_id=aa.user_id')
                ->where($condArr)->andWhere(['not in', 'l.entity_id', $excludeIds])
                ->andWhere(['aa.item_name' => '普通业务员'])
                ->asArray()->all();

            if ($normalContractors) {
                $contractorList = array_merge($normalContractors, $contractorList);
            }
            array_unshift($contractorList, ['contractor_id' => '0', 'name' => '城市指标']);
        }

        $respData = [
            'contractors' => $contractorList,
            'has_target_date' => $date
        ];

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
        $managerArr = [
            Contractor::MANAGER_CONTRACTOR,    // 城市经理
            Contractor::AREA_CONTRACTOR,    // 大区经理
            Contractor::CEO_MANAGER,    // 总办
            Contractor::SYSTEM_MANAGER  // 管理员
        ];
        if (in_array((string)$contractor->role, $managerArr, true)) {
            return false;
        }
        return true;
    }

    /**
     * @return TargetCenterRequest
     */
    public static function request()
    {
        return new TargetCenterRequest();
    }

    /**
     * @return TargetCenterResponse
     */
    public static function response()
    {
        return new TargetCenterResponse();
    }
}