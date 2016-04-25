<?php
class HamsterstudioParser extends TrackerParser{
	private $passkey;
	private static $cookiefile = 'hamsterstudio_cookie.txt';
	protected function authorization(){
		$params = array('username'=>$this->login,'password'=>$this->password);
		$result = parent::curl('http://hamsterstudio.org/takelogin.php',$params,self::$cookiefile);
		$this->passkey = $this->get_passkey();
	}
	private function get_passkey(){
		$result = parent::curl('http://hamsterstudio.org/getrss.php',array('feed'=>'web','login'=>'passkey'),self::$cookiefile);
		$passkey = substr($result['out'],strpos($result['out'],'passkey=')+8);
		$passkey = substr($passkey,0,strpos($passkey,'>'));
		return $passkey;
	}
	public function getNews(){
		$result = parent::curl('http://hamsterstudio.org/rss.php?passkey='.$this->passkey);
		$xml = simplexml_load_string($result['out']);
		$items = $xml->channel->item;
		foreach($items as $item){
			$title =  strval($item->title);
			$link = $item->link;
			$data['torrents'][] = $this->make_array($title,$link);
		}
		return $data;
	}
	public function browse($args = array()){
		$page = 0;
		if(isset($args['page']) && intval($args['page']) >= 1){
			$page = intval($args['page']) - 1;
		}
		$result = parent::curl('http://hamsterstudio.org/browse.php?page='.$page,false,self::$cookiefile);
		$result = iconv('windows-1251','utf-8',$result['out']);
		$result = substr($result,strpos($result,'<table class="main">'));
		$data = $this->get_pages($result);
		preg_match_all("/<td align=\"left\"><a.*<b>(.*)<\/b><\/a>[\s\S]*download\.php\?id=(.*)\"[\s\S]*<\/td>/U",$result,$matches);
		foreach($matches[1] as $i=>$title){
			$data['torrents'][] = $this->make_array($title,'http://hamsterstudio.org/download.php?id='.$matches[2][$i]);
		}
		return $data;
	}
	public function search($args = array()){
		$page = 0;
		if(isset($args['page']) && intval($args['page']) >= 1){
			$page = intval($args['page']) - 1;
		}
		$result = parent::curl('http://hamsterstudio.org/browse.php?search='.iconv('utf-8','windows-1251',$args['query']).'&page='.$page,false,self::$cookiefile);
		$result = iconv('windows-1251','utf-8',$result['out']);
		$result = substr($result,strpos($result,'<table class="main">'));
		$data = $this->get_pages($result);
		preg_match_all("/<td align=\"left\"><a.*<b>(.*)<\/b><\/a>[\s\S]*download\.php\?id=(.*)\"[\s\S]*<\/td>/U",$result,$matches);
		foreach($matches[1] as $i=>$title){
			$data['torrents'][] = $this->make_array($title,'http://hamsterstudio.org/download.php?id='.$matches[2][$i]);
		}
		return $data;
	}
	public function download($args){
		if(isset($args['url'])){
			parent::download_file($args['url'],false,self::$cookiefile);
		}
	}
	private function make_array($title,$link){
		$serial_name = substr($title,0,strpos($title,'/'));
		if(stristr($serial_name,'(')){
			$series_name = substr($serial_name,strripos($serial_name,'('));
			$serial_name = trim(str_replace($series_name,'',$serial_name));
			$series_name = trim(str_replace(array('(',')'),'',$series_name));
		}else{
			$series_name = '';
		}
		$series_info = parent::get_series_info("/езон ([\d]*)[\D]{1,}ерия ([\d]*)/",$title);
		if($series_info['season'] == null){
			$series_info['series'] = $series_name;
		}
		$eng_name = substr($title,strpos($title,'/')+1);
		if(strstr($eng_name,'/')){
			$eng_name = substr($eng_name,0,strpos($eng_name,'/'));
		}
		preg_match("/(^.*)\(\d{4}\)/ui",$eng_name,$match);
		if(isset($match[0])){
			$eng_name = trim(strip_tags($match[1]));
		}
		$resolution = parent::get_resolution($title);
		return  array('serial_name' => $serial_name,                      //название сериала на руском
									'eng_name'    => $eng_name,                         //название сериала на английском
									'season'      => $series_info['season'],            //сезон
									'series'      => $series_info['series'],            //серия
									'resolution'  => $resolution,                       //тип ссылки (400p/720p/1080p/2160p)
									'link'        => $link,                             //ссылка на файл .torrent
									'sourse'      => 'hamsterstudio');                  //источник
	}
	private function get_pages($str){
		$str = substr($str,0,strpos($str,'</table>'));
		preg_match("/[\s\S]*<b>(\d*)<\/b>/",$str,$matches);
		$pages = $matches[1];
		preg_match("/\"highlight\"><b>(\d*)<\/b>/",$str,$matches);
		$page = $matches[1];
		return array('page'=>$page,'pages'=>$pages);
	}
}
?>