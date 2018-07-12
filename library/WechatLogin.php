<?php
#define('WECHAT_APP_KEY', 'wx4d4a2a82f244ba58');

#define('WECHAT_APP_SECRET', '91d83c1d7a6940a55adaf37c4047ed08');

class WechatLogin {
        private $appId;
        private $appSecret;
        private $token;

        public function __construct($app='mobile') {
		//审核通过的移动应用所给的AppID和AppSecret
		if($app == 'mobile'){
			$this->appId = WECHAT_APP_KEY;
			$this->appSecret = WECHAT_APP_SECRET;
		} elseif($app == 'pc'){
			$this->appId = WECHAT_APP_KEY_PC;
			$this->appSecret = WECHAT_APP_SECRET_PC;
		}
	}

	public function getWechatAccessToken($code) {
		$url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->appId.'&secret='.$this->appSecret.'&code='.$code.'&grant_type=authorization_code';
		$result = json_decode(file_get_contents($url));
		if(!isset($result->access_token)){
			return false;
		}
		return $result;
	}

	public function getWechatUserInfo($openId, $token){
		$url = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$token.'&openid='.$openId.'&lang=zh-CN';
		$result = json_decode(file_get_contents($url));
		if(!isset($result->openid)){
			return false;
		}
		return $result;

	}
}
/*
$code = '051ix1Ug1rlFaw0ID0Rg1kSjUg1ix1UN';
$weixin = new WechatLogin();
//var_dump($weixin->getWechatAccessToken($code));
var_dump($weixin->getWechatUserInfo('o5QWXv2MZj7PpEErouibfak5JZt4','b7rt6t-r3nBe8lUayDe9l2cboCIPQuHMZMkM92-2LsGi1MeV2XiVGVLUbwOaIoY06ApubtOEFLYkCVZRrLJoaYBheKRVjLWhcV1YwCwI5IA'));
*/
