<?php
/**
 * 获取消息概况
 *
 */
namespace service\resources\contractor\v1;

use common\models\LeContractor;
use common\models\ContractorMessage;
use common\models\ContractorMessageRead;
use common\models\ContractorMessageRole;
use service\components\Tools;
use service\message\contractor\ContractorMsgDetailRequest;
use service\message\contractor\ContractorMsgDetailResponse;
use service\models\common\Contractor;
use service\models\common\ContractorException;

/**
 * 获取消息详情
 * @author zqy
 * @package service\resources\contractor\v1
 */
class getMsgDetail extends Contractor
{
    /**
     * @param string $data
     * @return ContractorMsgDetailResponse
     */
    public function run($data)
    {
        /* @var $request ContractorMsgDetailRequest */
        $request = self::parseRequest($data);
        $response = self::response();
        /* @var $contractor LeContractor */
        $contractor = $this->initContractor($request);

        /* 验证角色是否可以查看 */
        $cityListArr = array_filter(explode('|', $contractor->city_list));
        $msgRoleModels = ContractorMessageRole::findAll(['msg_id' => $request->getMsgId(), 'city_id' => $cityListArr]);

        if (!$msgRoleModels) {
            throw new ContractorException('内容不存在或已删除', 404);
        }

        $canRead = false;
        foreach ($msgRoleModels as $msgRoleModel) {
            if ((string)$msgRoleModel->role_id === '' || (string)$msgRoleModel->role_id === $contractor->role) {
                $canRead = true;
                break;
            }
        }

        if (!$canRead) {
            throw new ContractorException('内容不存在或已删除', 404);
        }

        /* 获取消息 */
        $msgModel = ContractorMessage::findOne([
            'entity_id' => $request->getMsgId(),
            'status' => ContractorMessage::STATUS_ENABLE
        ]);
        if (!$msgModel) {
            throw new ContractorException('内容不存在或已删除', 404);
        }

        $publishAt = date('Y-m-d H:i:s', $msgModel->publish_at);

        /* 置为已读 */
        $msgReadModel = ContractorMessageRead::findOne([
            'contractor_id' => $request->getContractorId(),
            'msg_id' => $request->getMsgId()
        ]);

        if (!$msgReadModel) {
            $msgReadModel = new ContractorMessageRead();
            $msgReadModel->contractor_id = $request->getContractorId();
            $msgReadModel->msg_id = $request->getMsgId();
            $msgReadModel->read_at = date('Y-m-d H:i:s', strtotime('+8 HOURS'));
            $msgReadModel->save();
        }

        $html = <<<EOT
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width">
    <title>{$msgModel->getAttribute('title')}</title>
  </head>
  <body>
    <p style="display:block;white-space:nowrap;text-overflow:ellipsis;margin:8px 0;overflow:hidden;font-size:20px;font-weight:bold;color:#000">
        {$msgModel->getAttribute('title')}
    </p>
    <p style="display:block;white-space:nowrap;text-overflow:ellipsis;margin:0;overflow:hidden;font-size:16px;color:#999">
        {$publishAt}
    </p>
    {$msgModel->getAttribute('content')}
  </body>
</html>
EOT;


        /* 返回数据 */
        $responseData = [
            'detail' => $html,
//            'url' => 'https://www.baidu.com'
        ];
        $response->setFrom(Tools::pb_array_filter($responseData));
        return $response;
    }

    /**
     * @return ContractorMsgDetailRequest
     */
    public static function request()
    {
        return new ContractorMsgDetailRequest();
    }

    /**
     * @return ContractorMsgDetailResponse
     */
    public static function response()
    {
        return new ContractorMsgDetailResponse();
    }
}