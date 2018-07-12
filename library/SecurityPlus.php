<?php
//安全认证
class Security{

	//接口允许时间戳间隔:5分钟
	private $maxTimestampDiff = API_EXPIRED_TIME;

	private $clientTimestamp = 0;
	
	private $clientSignature = '';

	function __construct(){
		$this->openId = $this->getOpenId();
		$this->clientTimestamp = $this->getTimestamp();
		$this->clientSignature = $this->getSignature();
		$this->antiSpider();
	}

	public function getQueryStringSorted(){
		$queryStringList = array();
		$tempArr = explode('?', $_SERVER['REQUEST_URI']);
		$queryString = isset($tempArr[1]) ? $tempArr[1] : '';
		if($queryString != ''){
			$queryStringList = explode('&', $queryString);
			$n = 0;
			foreach($queryStringList as $string){
				if(strpos($string, 'sign') !== false){
					unset($queryStringList[$n]);
				}
				$n ++;
			}
		}
		sort($queryStringList);
		return urldecode(implode('&', $queryStringList));
	}
	
	//对请求参数进行检查
	public function checkSignature(){
		$serverSignature = $this->makeSignature();
		if($this->clientSignature != $serverSignature){
			error(10005, '签名错误');
		}
		return $serverSignature;
	}

	//服务端签名生成
	private function makeSignature(){
		return strtoupper(md5($this->openId.'#'.md5($this->getQueryStringSorted()).'#'.APP_SECRET));
	}


	private function getTimestamp(){
		//时间戳，请求方的时间戳和服务器的时间戳相差不能超过5分钟
		$clientTimestamp = isset($_GET['timestamp']) ? (int)($_GET['timestamp']) : 0;
		//服务器当前时间	
		$serverTimestamp = time();
		if(abs($serverTimestamp - $clientTimestamp/1000) > $this->maxTimestampDiff){
			error(10006, '接口时间戳已经过期');
		}
		return $clientTimestamp;	
	}

	private function getSignature(){
		$clientSignature = isset($_GET['sign']) ? strtoupper($_GET['sign']) : '';
		return $clientSignature;
	}

	private function getOpenId(){
		$openId = isset($_GET['openid']) ? (int)($_GET['openid']) : APP_KEY;
		return $openId;	
	}

	public function getOpenData(){
		return $this->openData;
	}

	private function antiSpider(){
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
		if(strpos($ua, 'java') !== false){
			error(10005, '签名错误');
		}
	}
}
