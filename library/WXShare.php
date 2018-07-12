<?php
class WXHelper{

	#private $appid = 'wx5405456bdf6b412a';
	private $appid = 'wx424ed07559cfdc11';

	#private $secret = '2124550a51d1babf4a7c073cc54c1f15';
	private $secret = '4541592df3f7db56d6729d538a685ab5';

	private $wx_api_url = 'https://api.weixin.qq.com/cgi-bin/';

	private $access_token = '';

	private $timestamp = '';

	private $ticket = '';

	private $noncestr = '';

	private $signature = '';

	private $mc = false;

	function __construct($url, $mc){
		$this->mc = $mc;
		$this->access_token = $this->get_token();
		$this->ticket = $this->get_ticket($this->access_token);
		$this->timestamp = time();
		$this->noncestr = md5($this->timestamp);
		//$url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$string = 'jsapi_ticket='.$this->ticket.'&noncestr='.$this->noncestr.'&timestamp='.$this->timestamp.'&url='.$url;
		$this->signature = sha1($string);
		
	}

	function get_timestamp(){
		return $this->timestamp;
	}

	function get_noncestr(){
		return $this->noncestr;
	}

	function get_signature(){
		return $this->signature;
	}

	function get_jsapi_ticket(){
		return $this->jsapi_ticket;
	}
	
	private function get_token($force_refresh = 0){
		$access_token = '';
		if($force_refresh == 1)
			$access_token = false;
		else
			$access_token = $this->mc->get("weshine_access_token");

		if(!$access_token){
			$url = $this->wx_api_url.'token?grant_type=client_credential&appid='.$this->appid.'&secret='.$this->secret;
			$output = $this->curl($url);
			$output_array = json_decode($output,true);
			$access_token = $output_array["access_token"];
			$this->mc->set("weshine_access_token", $access_token, 7200);
		}
		return $access_token;
	}

	private function get_ticket($access_token){
		$jsapi_ticket = $this->mc->get("weshine_jsapi_ticket");
		if(!$jsapi_ticket){
			$url = $this->wx_api_url.'ticket/getticket?access_token='.$access_token.'&type=jsapi';
			$jsapi_ticket_json = $this->curl($url);
            		$jsapi_ticket_decode = json_decode( $jsapi_ticket_json, true );
            		$jsapi_ticket = isset( $jsapi_ticket_decode['ticket'] ) ? $jsapi_ticket_decode['ticket'] : false;
			if($jsapi_ticket) 
				$this->mc->set("weshine_jsapi_ticket",$jsapi_ticket,7200);
		}
		$this->jsapi_ticket = $jsapi_ticket;
		return $jsapi_ticket;
	}

	public function curl($url){
		//初始化
		$ch = curl_init();
		//设置选项，包括URL
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		//执行并获取HTML文档内容
		$output = curl_exec($ch);
		//释放curl句柄
		curl_close($ch);
		return $output;
	}
}

