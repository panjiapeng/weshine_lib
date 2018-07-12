<?php
define('QQ_APP_KEY', '1105281671');

define('QQ_APP_SECRET', '6mmQiIWxOtDmqYiV');

class QQLogin{
        private $appId;
        private $appSecret;
        private $token;

        public function __construct() {
		$this->appId = QQ_APP_KEY;
		$this->appSecret = QQ_APP_SECRET;
	}

	public function getAccessToken($code, $redirect_uri){
		$accessToken = '';
		$url = 'https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id='.$this->appId.'&client_secret='.$this->appSecret.'&code='.$code.'&redirect_uri='.urlencode($redirect_uri);
		$str = file_get_contents($url);
		$temparr = explode('&', $str);
		if(strpos($temparr[0], 'access_token') !== false){
			$accessToken = str_replace('access_token=', '', strtolower($temparr[0]));
		}
		return $accessToken;
	}

	public function getOpenId($token) {
		$result = array();
		$url = 'https://graph.qq.com/oauth2.0/me?access_token='.$token;
		$str = file_get_contents($url);
		$str = str_replace(array('', 'callback(', ');'), array(), $str);
		$result = json_decode($str);	
		if(isset($result->client_id) && !empty($result->client_id)){
			if($result->client_id == $this->appId){
				return $result;
			}
		}
		return false;
	}

	public function getUserInfo($openId, $token){
		$url = 'https://graph.qq.com/user/get_user_info?access_token='.$token.'&oauth_consumer_key='.$this->appId.'&openid='.$openId.'&format=json';
		$result = json_decode(file_get_contents($url));
		if(isset($result->ret) && 0 == $result->ret){
			return $result;
		}
		return false;

	}
}
