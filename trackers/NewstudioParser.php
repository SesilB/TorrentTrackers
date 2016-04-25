<?php
class NewstudioParser extends TrackerParser{
	private static $cookiefile = 'newstudio_cookie.txt';
	protected function authorization(){
		$params = array('login_username'=>$this->login,'login_password'=>$this->password,'autologin'=>1,'login'=>1);
		$result = parent::curl('http://newstudio.tv/login.php',$params,self::$cookiefile);
	}
	public function browse($args = array()){
		$page = 1;
		if(isset($args['page']) && intval($args['page']) >= 1){
			$page = (intval($args['page']) - 1)*50;
		}
		$result = parent::curl('http://newstudio.tv/tracker.php?start='.$page,false,self::$cookiefile);
		$data = $this->parse($result['out']);
		return $data;
	}
	public function getNews(){
		$result = parent::curl('http://newstudio.tv/tracker.php',false,self::$cookiefile);
		$data = $this->parse($result['out']);
		return $data['torrents'];
	}
	private function parse($result){
		$data = $this->get_pages($result);
		preg_match_all("/href=\"\.\/viewtopic\.php\?t=(.*)\D.*<b>(.*)<\/b>[\s\S]*download\.php\?id=(.*)\">/U",$result,$matches);
		foreach($matches[2] as $i=>$title){
			$resolution = parent::get_resolution($title);
			$title = explode('/',$title);
			$serial_name = trim(strip_tags(substr($title[0],0,strpos($title[0],'('))));
			$eng_name = trim(strip_tags(substr($title[1],0,strpos($title[1],'('))));
			$series_name = substr($title[0],strpos($title[0],'(')+1); 
			$series_info = parent::get_series_info("/\D*(\d{1,})\,\D*(\d{1,})/",substr($series_name,0,strpos($series_name,')')));
			$link = 'http://newstudio.tv/download.php?id='.$matches[3][$i].'&login_username='.$this->login .'&login_password='.$this->password;
			$data['torrents'][] = array('serial_name' => $serial_name,                      //название сериала на руском
																	'eng_name'    => $eng_name,                         //название сериала на английском
																	'season'      => $series_info['season'],            //сезон
																	'series'      => $series_info['series'],            //серия
																	'resolution'  => $resolution,                       //тип ссылки (400p/720p/1080p/2160p)
																	'link'        => $link,                             //ссылка на файл .torrent
																	'sourse'      => 'newstudio.tv');                    //источник
		}
		return $data;
	}
	public function search($args){
		$result = parent::curl('http://newstudio.tv/tracker.php',array('nm'=>$args['query']));
		$result = $result['out'];
		$data = $this->parse($result);
		return $data;
	}
	private function get_pages($str){
		$page = substr($str,strpos($str,'class="active"><span>')+21);
		$page = substr($page,0,strpos($page,'</span>'));
		preg_match("/pagination pagination-small[\s\S]*>([\d]{1,})<\/a>.*<\/ul>/",$str,$matches);
		if(!isset($matches[0])){
			return array('page'=>1,'pages'=>1);
		}
		return array('page'=>$page,'pages'=>$matches[1]);
	}
	public function download($args){
		if(isset($args['url'])){
			parent::download_file($args['url'],false,self::$cookiefile);
		}
	}
}
?>