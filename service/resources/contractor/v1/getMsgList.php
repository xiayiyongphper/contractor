<?php
/**
 * 获取消息列表
 *
 */
namespace service\resources\contractor\v1;

use common\models\LeContractor;
use common\models\ContractorMessage;
use common\models\ContractorMessageRead;
use service\components\Tools;
use service\message\contractor\ContractorMsgListRequest;
use service\message\contractor\ContractorMsgListResponse;
use service\models\common\Contractor;


/**
 * 获取业务员消息列表
 * @author zqy
 * @package service\resources\contractor\v1
 */
class getMsgList extends Contractor
{
    /**
     * @param string $data
     * @return ContractorMsgListResponse
     */
    public function run($data)
    {
        /* @var $request ContractorMsgListRequest */
        $request = self::parseRequest($data);
        $response = self::response();
        /* @var $contractor LeContractor */
        $contractor = $this->initContractor($request);
        $cityListArr = array_filter(explode('|', $contractor->city_list));

        /* 验证角色是否可以查看 */
        $cond = [
            'm.status' => ContractorMessage::STATUS_ENABLE,
            'mr.city_id' => $cityListArr,
            'mr.role_id' => ['', $contractor->role], // 包括空的（不限制角色）
        ];
        if ($request->getType()) {
            $cond['type'] = $request->getType();
        }

        $msgModels = ContractorMessage::find()->alias('m')->join('INNER JOIN', 'contractor_message_role mr')
            ->select('entity_id, title, publish_at')
            ->where('m.entity_id=mr.msg_id')
            ->andWhere(['<=', 'publish_at', strtotime('+8 HOURS')])
            ->andWhere($cond)
            ->groupBy('entity_id')
            ->all();

        // 没有数据直接返回
        if (!$msgModels) {
            return $response;
        }

        /* 有数据，则判断已读未读 */
        $msgIds = [];
        foreach ($msgModels as $msgModel) {
            /* @var $msgModel ContractorMessage*/
            $msgIds[] = $msgModel->getAttribute('entity_id');
        }

        $msgReadModels = ContractorMessageRead::find()->select('msg_id,contractor_id')->where([
            'msg_id' => $msgIds,
            'contractor_id' => $request->getContractorId()
        ])->all();

        $readIds = [];
        foreach ($msgReadModels as $msgReadModel) {
            /* @var $msgReadModel ContractorMessageRead*/
            $readIds[$msgReadModel->msg_id] = 1;
        }

        /* 回应 */
        $msgList = [];
        foreach ($msgModels as $msgModel) {
            if (isset($readIds[$msgModel->entity_id])) {
                $msgList[] = [
                    'id' => $msgModel->entity_id,
                    'title' => $msgModel->title,
                    'create_time' => date('Y-m-d H:i:s', $msgModel->publish_at),
                    'is_read' => 1
                ];
            } else {
                $msgList[] = [
                    'id' => $msgModel->entity_id,
                    'title' => $msgModel->title,
                    'create_time' => date('Y-m-d H:i:s', $msgModel->publish_at),
                    'is_read' => 0
                ];
            }
        }

        $responseData['msg_list'] = $msgList;
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    /**
     * @return ContractorMsgListRequest
     */
    public static function request()
    {
        return new ContractorMsgListRequest();
    }

    /**
     * @return ContractorMsgListResponse
     */
    public static function response()
    {
        return new ContractorMsgListResponse();
    }
}