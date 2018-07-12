<?php
function stopSql($CON,$parameter){
    if (get_magic_quotes_gpc()){
        $parameter = stripslashes($parameter);
	}else{
        $parameter = mysqli_real_escape_string($CON,$parameter);
	}	
	$filter = array("drop ","select ","delete ","truncate","insert ","update ","alter "," table ");
	$parameter = str_replace($filter,"",$parameter);
	return trim(htmlspecialchars($parameter));
}

function microtime_float() {
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function logger($logfile, $data){
	if(!defined('LOG_ENABLE') || !LOG_ENABLE ){
		return false;
	}
	$data = date('Y-m-d H:i:s').' -- '.$data."\n";
	file_put_contents($logfile, $data, FILE_APPEND);
}

function output($data,$code = 200){
	$res = json_encode(array("code"=>$code,"data"=>$data));
	echo $res;
}

function error($code, $message){
	$request = isset($_GET['route']) ? '/'.$_GET['route'] : '';
	$response = array(
		'request' => $request,
		'code'	=> $code,
		'message' => $message,
	);	
	die(json_encode($response, JSON_UNESCAPED_UNICODE));
}

function getMemcached($_mc_config_list){
	if (class_exists('Memcached', FALSE)){
		$_memcached = new Memcached();
	} elseif (class_exists('Memcache', FALSE)){
		$_memcached = new Memcache();
	} else {
		return false;
	}
	foreach($_mc_config_list as $config){
		if ($_memcached instanceof Memcache) {
			// Third parameter is persistance and defaults to TRUE.
			$_memcached->addServer( $config['host'], $config['port'], TRUE, $config['weight']);
		} elseif ($_memcached instanceof Memcached) {
			$_memcached->addServer( $config['host'], $config['port'], $config['weight']);
		}
		$_memcached->setOption(Memcached::OPT_COMPRESSION, false); //关闭压缩功能
		$_memcached->setOption(Memcached::OPT_BINARY_PROTOCOL, true); //使用binary二进制协议
		#if($config['user'] && $config['pwd']){
		#	$res = $_memcached->setSaslAuthData( $config['user'], $config['pwd']);
		#}
	}

	return $_memcached;
}

function getRedisConn($config, $persistent = false){
	$redis = new Redis();
	if(!$persistent){
		$success = $redis->connect($config['host'], $config['port'], $config['timeout']);
	} else {
		$success = $redis->pconnect($config['host'], $config['port'] );
	}
	if ( ! $success) {
		throw new RuntimeException('Cache: Redis connection failed. Check your configuration.');
	}
	if (isset($config['password']) && ! $redis->auth($config['password'])) {
		throw new RuntimeException('Cache: Redis authentication failed.');
	}
	return $redis;
}

function getDbConn($db_config){
	$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pswd'],$db_config['db']);
	$conn->query('set names '.$db_config['charset'].';');
	return $conn;
}

function loadModel($modelName, $data){
	$modelFile = __DIR__.'/../models/'.strtolower(str_replace('Model', '', $modelName)).'.php';
	if(file_exists($modelFile)){
		require $modelFile;
		$i =  strrpos($modelName, '/');
		if($i > 0){
			$modelName = substr($modelName, $i + 1);
		}
		$class = new $modelName($data);
		return $class;
	}
	return false;
}

function ver2int($ver){
	$ver_new = '';
	$arr = explode('.', $ver);
	for($i=0; $i<4; $i++){
		$ver_new .= isset($arr[$i]) ? str_repeat('0', 5-strlen($arr[$i])).$arr[$i]: '00000';
	}
	return (int)$ver_new;
}

function array_sort_by() {
	$args = func_get_args();
	$data = array_shift($args);
	foreach ($args as $n => $field) {
		if (is_string($field)) {
			$tmp = array();
			foreach ($data as $key => $row)
				$tmp[$key] = $row[$field];
			$args[$n] = $tmp;
		}
	}
	$args[] = &$data;
	call_user_func_array('array_multisort', $args);
	return array_pop($args);
}

//分词结果
function getSegment($keyword){
	$parse_url = SEGMENT_QUERY_URL.urlencode($keyword);
	$result = file_get_contents($parse_url);
	$data = json_decode($result);
	return $data;
}

function getIP() { 
	if (getenv('HTTP_CLIENT_IP')) {
		$ip = getenv('HTTP_CLIENT_IP');
	} elseif (getenv('HTTP_X_FORWARDED_FOR')) { 
		$ip = getenv('HTTP_X_FORWARDED_FOR'); 
	} elseif (getenv('HTTP_X_FORWARDED')) {
		$ip = getenv('HTTP_X_FORWARDED');
	} elseif (getenv('HTTP_FORWARDED_FOR')) { 
		$ip = getenv('HTTP_FORWARDED_FOR'); 
	} elseif (getenv('HTTP_FORWARDED')) { 
		$ip = getenv('HTTP_FORWARDED'); 
	} else { 
		$ip = $_SERVER['REMOTE_ADDR']; 
	} 
	return $ip; 
} 

function reqLimitByIP($redis){
	$ip = getIP();
	$date = date('Ymd');
	$hkey = $date.'_'.$ip;
	$reqCount = (int)$redis->hget('h:ip:limit', $hkey);
	if($reqCount >= 2500){
		return false;
	}
	$redis->hIncrBy('h:ip:limit', $hkey, 1);
	return true;
}

//发送短信
function sendMessage($phoneNumber, $message){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://sms-api.luosimao.com/v1/send.json");

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);

	curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD  , 'api:key-a7ae1237d1e4adca9ed83d70abd94d55');

	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, array('mobile' => $phoneNumber, 'message' => $message));

	$res = curl_exec( $ch );
	curl_close( $ch );
	if($res == '{"error":0,"msg":"ok"}') {
		return true;
	} else {
		return false;
	}
}

function  genUUID() {
	$chars = md5(uniqid(mt_rand(), true));
	$uuid = substr ( $chars, 0, 8 ) . '-'
		. substr ( $chars, 8, 4 ) . '-'
		. substr ( $chars, 12, 4 ) . '-'
		. substr ( $chars, 16, 4 ) . '-'
		. substr ( $chars, 20, 12 ); 
	return $uuid ;    
}  

function sendSuccReponse($response, $pagination = array(), $cb = ''){
	$result = array(
		'meta' => array(
			'status' => 200,
			'msg' => 'success',
		),
		'data' => $response
	);
	if($pagination){
		$result['pagination'] = $pagination;
	}
	$json_data = json_encode($result, JSON_UNESCAPED_UNICODE);
	echo ($cb == '') ? $json_data : $cb.'(\''.$json_data.'\')';
} 

function upload_aliyun_cdn($oss_sdk_service, $cdn_path, $local_file,$bucket=ALIYUN_CDN_BUCKET, $domain=IMG_DOMAIN,$options = array()){
	$response = $oss_sdk_service->upload_file_by_file($bucket, $cdn_path, $local_file, $options);
	if($response->status == 200){
		return $domain.'/'.$cdn_path;
	}
	return false;
}

function emoji_unicode_encode($str, $clear_emoji=false){
	$str_encode = '';
	$length = mb_strlen($str,'utf-8');
	for ($i=0; $i < $length; $i++) {
		$_tmpStr = mb_substr($str,$i,1,'utf-8');
		if(strlen($_tmpStr) >= 4){
			if(!$clear_emoji){
				$str_encode .= '[[MB_EMJ:'.rawurlencode($_tmpStr).']]';
			}
		}else{
			$str_encode .= $_tmpStr;
		}
	}
	return $str_encode;
}

function emoji_unicode_decode($str){
	$str_decode = preg_replace_callback("/\[\[MB_EMJ:(.*?)\]\]/", function($matches){
		return rawurldecode($matches[1]);
	}, $str);
	return $str_decode;
}

function checkEmail($email){
	$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
	if(preg_match($pattern, $email)) {
		return true;
	}
	return false;
}

function filterDBKeywords($str){
	$key_words = array('case','when','substr','data','base','then',
		    'end','select','update','from','where','else','delete','length', 
		    'substring', 'version', 'os', 'order', 'null', 'end', 'confirm', 
		    'prompt', 'window', 'insert', 'script', 'alert', '*', 'set');	
	
	$rst = str_replace($key_words, array(), $str);
	return $rst;
}

function filter_nickname($raw_str){
	$str = '';
	$char_list = str_split_unicode($raw_str);
	foreach($char_list as $c) {
		if(preg_match('/^[\x{4e00}-\x{9fa5}a-zA-Z0-9-_]$/u', $c)){
			$str .= $c;
		}	
	}
	return $str;
}

function str_split_unicode($str, $l = 0) {
	if ($l > 0) {
		$ret = array();
		$len = mb_strlen($str, "UTF-8");
		for ($i = 0; $i < $len; $i += $l) {
			$ret[] = mb_substr($str, $i, $l, "UTF-8");
		}
		return $ret;
	}
	return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

function pushAliyunQueue($queueName, $data){
	require ROOT_DIR.'./library/AliyunMNS/Queue.php';
	$queue = new Queue(['queueName' => $queueName]);
	return $queue ->sendQueue(json_encode($data));
}


function sendPost($url, $data_string = '')
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'X-AjaxPro-Method:ShowList',
	'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/33.0.1750.154 Safari/537.36',
	'Content-Type: application/json; charset=utf-8',
	'Content-Length: ' . strlen($data_string) 
	));
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data; 
}


function ch2arr($str) {
	$length = mb_strlen($str, 'utf-8');
	$array = [];
	for ($i=0; $i<$length; $i++)
	$array[] = mb_substr($str, $i, 1, 'utf-8');
	return $array;
}

function getMapFromList($arrData, $strKey='id') {
    $arrValue = array();
    if(!is_array($arrData) || !count($arrData)) {
        return $arrValue;
    }
    foreach($arrData as $val) {
        if(isset($val[$strKey])) {
            $arrValue[$val[$strKey]] = $val;
        }
    }
    return $arrValue;
}

//概率算法
function get_rand($proArr,$emptyProVal) { 
    $result = '';   
	$proArr['EMPTY'] = $emptyProVal;  
    $proSum = array_sum($proArr);   
    foreach ($proArr as $key => $proCur) {   
        $randNum = mt_rand(0, $proSum);               
        if ($randNum <= $proCur) {   
            $result = $key;                         
            break;   
        } else {   
            $proSum -= $proCur;                       
        }   
    }   
    unset ($proArr);   
    return $result;   
}  


/**
 * $str 原始中文字符串
 * $encoding 原始字符串的编码，默认GBK
 * $prefix 编码后的前缀，默认"&#"
 * $postfix 编码后的后缀，默认";"
 */
function unicode_encode($str, $encoding = 'GBK', $prefix = '&#', $postfix = ';') {
    $str = iconv($encoding, 'UCS-2', $str);
    $arrstr = str_split($str, 2);
    $unistr = '';
    for($i = 0, $len = count($arrstr); $i < $len; $i++) {
        $dec = hexdec(bin2hex($arrstr[$i]));
        $unistr .= $prefix . $dec . $postfix;
    }
    return $unistr;
} 
 
/**
 * $str Unicode编码后的字符串
 * $decoding 原始字符串的编码，默认GBK
 * $prefix 编码字符串的前缀，默认"&#"
 * $postfix 编码字符串的后缀，默认";"
 */
function unicode_decode($unistr, $encoding = 'GBK', $prefix = '&#', $postfix = ';') {
    $arruni = explode($prefix, $unistr);
    $unistr = '';
    for($i = 1, $len = count($arruni); $i < $len; $i++) {
        if (strlen($postfix) > 0) {
            $arruni[$i] = substr($arruni[$i], 0, strlen($arruni[$i]) - strlen($postfix));
        }
        $temp = intval($arruni[$i]);
        $unistr .= ($temp < 256) ? chr(0) . chr($temp) : chr($temp / 256) . chr($temp % 256);
    }
    return iconv('UCS-2', $encoding, $unistr);
}

function subString($string){
	$strLen = mb_strlen($string,'utf-8');
	$maxLen = 16;
	$resArr = array();
	for ($i=0; $i < ceil($strLen/$maxLen); $i++) { 
		$resArr[] = mb_substr($string,$i*$maxLen,$maxLen,'utf-8');
	}
	return $resArr;
}

function mkdirs($dir, $mode = 0777)
{
    if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;
    if (!mkdirs(dirname($dir), $mode)) return FALSE;
    return @mkdir($dir, $mode);
} 