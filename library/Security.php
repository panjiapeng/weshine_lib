<?php
//安全认证
class Security{

	//接口允许时间戳间隔:5分钟
	private $maxTimestampDiff = 5*60;

	private $openId = '';	

	private $clientTimestamp = 0;

	private	$clientSignature = '';

	private $openData = array();

	function __construct(){
		$this->openId = $this->getOpenId();
		$this->clientTimestamp = $this->getTimestamp();
		$this->clientSignature = $this->getSignature();
		$this->antiSpider();
	}
	
	//对请求参数进行检查
	public function checkSignature(){
		$serverSignature = $this->makeSignature($this->openId, $this->clientTimestamp,APP_SECRET);
		if($this->clientSignature != $serverSignature){
			error(10005, '签名错误');
		}
		return $serverSignature;
	}

	public function getOpenData(){
		return $this->openData;
	}

	//服务端签名生成
	private function makeSignature($openId, $timestamp, $secret){
		return md5($openId.'#'.$secret.'#'.$timestamp);
	}


	private function getTimestamp(){
		//时间戳，请求方的时间戳和服务器的时间戳相差不能超过5分钟
		$clientTimestamp = isset($_GET['timestamp']) ? (int)($_GET['timestamp']) : 0;
		//服务器当前时间	
		$serverTimestamp = time();

		$clientTimestamp1 = ($clientTimestamp > 9999999999) ?  $clientTimestamp / 1000 : $clientTimestamp;

		if(abs($serverTimestamp - $clientTimestamp1) > $this->maxTimestampDiff){
			error(10003, '接口已经过期');
		}
		return $clientTimestamp;	
	}

	private function getOpenId(){
		$openId = isset($_GET['openid']) ? (int)($_GET['openid']) : APP_KEY;
		return $openId;	
	}

	private function getSignature(){
		$clientSignature = isset($_GET['sign']) ? $_GET['sign'] : '';
		return $clientSignature;
	}

	private function antiSpider(){
		$ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
		if(strpos($ua, 'java') !== false){
			error(10005, '签名错误');
		}
	}

}
