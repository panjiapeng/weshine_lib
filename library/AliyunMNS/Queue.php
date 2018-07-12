<?php
require_once('mns-autoloader.php');

use AliyunMNS\Client;
use AliyunMNS\Requests\SendMessageRequest;
use AliyunMNS\Requests\CreateQueueRequest;
use AliyunMNS\Exception\MnsException;

class Queue
{
    private $accessId;
    private $accessKey;
    private $endPoint;
    private $client;
    private $queueName;


    public function __construct($data)
    {
        if(!isset($data) || empty($data)){
            return false;
        }
        $this->accessId = 'Y6ANfhyoAuiNWErB';
        $this->accessKey = 'xDrsuNfJfCAMhgwUAlJ5Ezk49Ml3j8';
        $this->endPoint = 'https://1116012930077680.mns.cn-beijing.aliyuncs.com/';
        $this->queueName = $data['queueName'];
        $this->client = new Client($this->endPoint, $this->accessId, $this->accessKey);
    }


    /**
     * 创建队列
     * @param string $queueName
     * @return bool
     */
    public function createQueue(){
        $request = new CreateQueueRequest($this ->queueName);

        try
        {
            $res = $this->client->createQueue($request);
            return $res;
        }
        catch (MnsException $e)
        {
            return false;
        }
    }

    /**
     * 写入队列
     * @param $msg
     * @return bool
     */
    public function sendQueue($msg){
        if(!isset($msg) || empty($msg)) {
            return false;
        }
        $queue = $this->client->getQueueRef($this -> queueName);
        $request = new SendMessageRequest($msg);

        try
        {
            return $queue->sendMessage($request);

        }
        catch (MnsException $e)
        {
            return false;
        }
    }

    /**
     * 获取队列信息
     * @return bool
     */
    public function getQueue(){
        if(!isset($this -> queue)){
            $this -> queue = $this->client->getQueueRef($this -> queueName);
        }

        try
        {
            $res = $this ->queue ->receiveMessage(30);
            $request['msg'] = $res->getmessageBody();
            $request['handle'] = $res->getreceiptHandle();
            return $request;
        }
        catch (MnsException $e)
        {
            return false;
        }

    }

    /**
     * 删除队列中的元素
     * @param $handle
     * @return \AliyunMNS\Responses\ReceiveMessageResponse|bool
     */
    public function delQueue($handle){
        if(!isset($handle) || empty($handle)) {
            return false;
        }
        if(!isset($this -> queue)){
            $this -> queue = $this->client->getQueueRef($this -> queueName);
        }
        try
        {
            $res = $this -> queue->deleteMessage($handle);
            return $res;
        }
        catch (MnsException $e)
        {
            return false;
        }

    }

}
?>
