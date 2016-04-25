<?php
class TrackerParser{
	protected $login;
	protected $password;
	protected $cookiedir = 'cookies';
	public function __construct($args=array()){
		if(!isset($args['login'])|| !isset($args['password'])){
			$this->show_error('не указан login или password');
		}
		$this->login = $args['login'];
		$this->password = $args['password'];
		$this->authorization();
	}
	public function download_file($url,$post=false,$cookiefile=false,$cookies=false){
		$link = substr($url,0,strpos($url,'?'));
		$params = str_replace($link.'?','',$url);
		parse_str($params,$params);
		$url = $link.'?'.http_build_query($params);
		$url = str_replace('+','%2B',$url);
		$result = $this->curl($url,$post,$cookiefile,$cookies);
		if (preg_match("/name\d*\:(.*)\d*:piece/U", $result['out'], $matches)){
			$filename = $matches[1].'.torrent';
		}else{
			$filename = 'download.torrent';
		}
		$file = fopen($filename, "w+");
		fputs($file, $result['out']);
		fclose($file);
		if (file_exists($filename)){
			if (ob_get_level()){
				ob_end_clean();
			}
			header('Content-Description: File Transfer');
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=' . basename($filename));
			header('Content-Transfer-Encoding: binary');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($filename));
			if ($file = fopen($filename, 'rb')){
				while (!feof($file)){
					print fread($file, 1024);
				}
				fclose($file);
			}
			unlink($filename);
			exit;
		}
	}
	protected function curl($url,$post=false,$cookiefile=false,$cookies = false,$headers = false){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.76 Mobile Safari/537.36');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_FAILONERROR, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		if($headers){
			curl_setopt($curl ,CURLOPT_HTTPHEADER,$headers);
		}
		//curl_setopt($curl, CURLOPT_HEADER, true);
		//curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		if($post){
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		if($cookiefile){
			curl_setopt($curl, CURLOPT_COOKIEJAR, parent::$cookiedir.'/'.$cookiefile);
			curl_setopt($curl, CURLOPT_COOKIEFILE, parent::$cookiedir.'/'.$cookiefile);
		}
		if($cookies){
			curl_setopt($curl, CURLOPT_COOKIE, $cookies);
		}
		$out = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);
		return array('info'=>$info,'out'=>$out);
	}
	protected static function get_resolution($str){
		if(stristr($str,'720p')!=FALSE) {
			return '720p';
		}elseif(stristr($str,'1080p')!=FALSE) {
			return '1080p';
		}elseif(stristr($str,'2160p')!=FALSE){
			return '2160p';
		}else{
			return '400p';
		}
	}
	protected static function get_series_info($pattern,$str){
		preg_match($pattern,$str,$matches);
		if(isset($matches[0])){
			$season = $matches[1];
			$series = $matches[2];
		}else{
			$season = null;
			$series = $str;
		}
		return array('season'=>$season,'series'=>$series);
	}
	protected function show_error($message){
		echo json_encode(array('status'=>'error','message'=>$message));
		exit;
	}
}
?>
