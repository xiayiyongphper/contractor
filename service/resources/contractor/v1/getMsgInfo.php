<?php
/**
 * 获取消息概况
 *
 */
namespace service\resources\contractor\v1;

use common\models\LeContractor;
use common\models\ContractorMessage;
use service\components\Tools;
use service\message\contractor\ContractorMsgInfoRequest;
use service\message\contractor\ContractorMsgInfoResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * 获取业务员消息概况（首页用到）
 * @author zqy
 * @package service\resources\contractor\v1
 */
class getMsgInfo extends Contractor
{
    /**
     * @param string $data
     * @return ContractorMsgInfoResponse
     */
    public function run($data)
    {
        /* @var $request ContractorMsgInfoRequest */
        $request = self::parseRequest($data);
        $response = self::response();
        /* @var $contractor LeContractor */
        $contractor = $this->initContractor($request);
        $responseData = $this->getInitData();
        $cityListArr = array_filter(explode('|', $contractor->city_list));

        $models = ContractorMessage::find()->alias('m')
            ->select('type, count(distinct entity_id) as `count`')
            ->leftJoin('contractor_message_role mr', 'm.entity_id=mr.msg_id')
            ->leftJoin('contractor_message_read r', 'm.entity_id=r.msg_id AND r.contractor_id=' . $contractor->entity_id)
            ->where([
                'm.status' => ContractorMessage::STATUS_ENABLE,
                'mr.city_id' => $cityListArr,
                'mr.role_id' => ['', $contractor->role], // 包括空的（不限制角色）
                'r.contractor_id' => null,
            ])
            ->andWhere(['<', 'publish_at', strtotime('+8 HOURS')])
            ->groupBy('type')
            ->all();

        foreach ($models as $model) {
            /* @var $model ContractorMessage*/
            $type = $model->type;
            if (! in_array($type, [1, 2, 3])) {
                continue;
            }
            $num = min((int)$model->count, 30); // 最多30条
            $responseData['unread_list'][$type - 1]['count'] = $num;
            $responseData['unread_count'] += $num;
        }

        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    /**
     * @return array
     */
    private function getInitData()
    {
        return [
            'unread_list' => [
                [
                    'type' => [
                        'id' => 1,
                        'name' => '活动促销',
                    ],
                    'count' => 0
                ],
                [
                    'type' => [
                        'id' => 2,
                        'name' => '公司公告',
                    ],
                    'count' => 0
                ],
                [
                    'type' => [
                        'id' => 3,
                        'name' => '内部公告',
                    ],
                    'count' => 0
                ],
            ],
            'unread_count' => 0,
        ];
    }

    /**
     * @return ContractorMsgInfoRequest
     */
    public static function request()
    {
        return new ContractorMsgInfoRequest();
    }

    /**
     * @return ContractorMsgInfoResponse
     */
    public static function response()
    {
        return new ContractorMsgInfoResponse();
    }
}