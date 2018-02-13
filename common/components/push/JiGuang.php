<?php

namespace common\components\push;

use common\components\push\lib\JiGuangPush;
use service\components\Tools;

use yii\base\Exception;


/**
 * Author: Jason Y. Wang
 * Class JiGuang
 * @package common\components\push
 */
class JiGuang
{
    protected $_apiKey;
    protected $_secretKey;

    public function process()
    {

    }

    /**
     * Function: push
     * Author: Jason Y. Wang
     *
     * @param $data = array(
     * 'user_id' => $parts,
     * 'title' => $params['title'],
     * 'content' => $params['content'],
     * 'scheme' => $params['scheme'],
     * 'mobile' => $mobile,
     * 'sendno' => $this->entity_id,
     * );
     * @return bool
     * @throws Exception
     */
    public function push($data)
    {
        $n_title = $data['title'];
        $n_content = $data['content'];
        $sendno = $data['sendno'];
        $receiver_value = $data['user_id'];
        $mobilesys = $data['mobilesys'];
        $scheme = $data['scheme'];
        $obj = new JiGuangPush($this->_apiKey, $this->_secretKey);
        if($mobilesys=='ios'){
            $msg_content = json_encode(array('n_title'=>$n_title,'n_content'=>$n_content,'n_extras'=>array('scheme'=>$scheme)));
            $res = $obj->send($sendno, 5, $receiver_value, 1, $msg_content, $mobilesys);
        }else{
            $msg_content = json_encode(array('message'=>json_encode(array('title'=>$n_title,'scheme'=>urlencode($scheme),'content'=>$n_content))));
            Tools::log($msg_content,'wangyang.txt');
            $res = $obj->send($sendno, 5, $receiver_value, 2, $msg_content, $mobilesys);
        }
        Tools::log('#######length################','wangyang.txt');
        Tools::log(strlen($msg_content),'wangyang.txt');
        Tools::log($res['errcode'],'wangyang.txt');
        if (0 !== $res['errcode']) {
            Tools::log($res['errmsg'],'wangyang.txt');
            throw new Exception(sprintf('ERROR NUMBER: %s,ERROR MESSAGE: %s', $res['errcode'],$res['errmsg']));
        }
        return true;
    }
}