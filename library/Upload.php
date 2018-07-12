<?php
class Upload{

	private $upload_dir = '';

	private $oss_sdk_service = false;

	//定义允许上传的类型
	private $allow_type = array('jpg','jpeg','gif','png');

	private $file_name = '';

	private $max_file_size = 1024*2048;

	public function __construct(){
		$this->upload_dir = DIR_UPLOAD.date('Ymd').'/';
		if(!is_dir($this->upload_dir)){
			mkdir($this->upload_dir);
		}
		require ROOT_DIR.'/library/Aliyun/sdk.class.php';

		//阿里云CDN
		$this->oss_sdk_service = new ALIOSS();

		$this->file_name = time().'_'.uniqid().'_'.rand(1000, 9999);
	}

	public function setImageAllowType($arr=array('gif')){
		$this->allow_type = $arr;
	}

	public function setMaxFileSize($filesize){
		$this->max_file_size = $filesize;
	}

	private function saveBase64ToFile($base64_string){
		if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_string, $result)){ 
			$suffix = $result[2]; 
			$this->file_name .= '.'.$suffix;
			$file_path = $this->upload_dir.$this->file_name;
			$file_content = base64_decode(str_replace($result[1], '', $base64_string)); 
			if (file_put_contents($file_path, $file_content)){ 
				return $file_path;
			}
		}
		return false;
	}

	private function saveBinaryToFile($stream){
		$imageType = $this->checkImageType($stream);	
		if(!$imageType || !in_array($imageType, $this->allow_type)){
			error(50102, '图片格式非法');
		}
	}

	private function saveRemoteFile($url){
		$content = file_get_contents($url);
		if(strlen($content) > $this->max_file_size){
			error(50103, '文件大小超过'.($this->max_file_size / 1024 / 1024).'M');
		}
		$imageType = strtolower($this->checkImageType($content));
		$file_path = $this->upload_dir.$this->file_name.'.'.$imageType;
		file_put_contents($file_path, $content);
		if(file_exists($file_path)){
			return $file_path;
		}
		
	}

	private function saveUploadFile($file){
		$name = $file['name'];
		$type = strtolower(substr($name,strrpos($name,'.')+1)); //得到文件类型，并且都转化成小写
		//判断文件类型是否被允许上传
		if(!in_array($type, $this->allow_type)){
  			//如果不被允许，则直接停止程序运行
			error(50102, '图片格式非法');
		}
		if(!is_uploaded_file($file['tmp_name'])){
  			//如果不是通过HTTP POST上传的
			error(50102, '上传方式非法');
		}

		if(filesize($file['tmp_name']) > $this->max_file_size){
			error(50103, '文件大小超过'.($this->max_file_size / 1024 / 1024).'M');
		}

		$this->file_name .= '.'.$type;

		$file_path = $this->upload_dir.$this->file_name;

		if(move_uploaded_file($file['tmp_name'], $file_path)){
			return $file_path;
		}
	}

	public function uploadCDN($type, $data, $showmd5 = false){
		$img_url = '';
		$file_md5 = '';
		$file_size = 0;
		$width = 0;
		$height = 0;
		if('base64' == $type){
			$base64_string = $data['base64_string'];
			$img_file = $this->saveBase64ToFile($base64_string);
		} else if('url' == $type){
			$img_file = $this->saveRemoteFile($data['url']);
		} else {
			$img_file = $this->saveUploadFile($data['file']);
		}
		if(!$img_file){return false;}
		$dir = (isset($data['from']) && !empty($data['from'])) ? $data['from'] : 'upload';
		$cdn_path = $dir.'/'.date('Ymd').'/'.basename($img_file);
		if(file_exists($img_file) && filesize($img_file) > 1024){
			$file_md5 = md5_file($img_file);
			$file_size = filesize($img_file);
			$dimension = getimagesize($img_file);
			$width = $dimension[0];
			$height = $dimension[1];
			$img_url = upload_aliyun_cdn($this->oss_sdk_service, $cdn_path, $img_file);
		}
		if($showmd5){
			return array('imgurl'=>$img_url, 'md5'=>$file_md5, 'filesize'=>$file_size, 'width'=>$width, 'height'=>$height);
		}
		return $img_url;
	}

	function checkImageType($image){
		$bits = array( 'JPEG' => "\xFF\xD8\xFF", 'GIF' => "GIF", 'PNG' => "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a", 'BMP' => 'BM', );
		foreach ($bits as $type => $bit) {
			if (substr($image, 0, strlen($bit)) === $bit) {
				return $type;
			}
		}
		return false; 
	}
}
