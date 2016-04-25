<?php
class BaibakoParser extends TrackerParser{
	private $passkey;
	private static $cookiefile = 'baibako_cookie.txt';
	protected function authorization(){
		$params = array('username'=>$this->login,'password'=>$this->password,'commit'=>'%CF%F3%F1%F2%E8%F2%E5+%EC%E5%ED%FF');
		$result = parent::curl('http://baibako.tv/takelogin.php',$params,self::$cookiefile);
		$this->passkey = $this->get_passkey();
	}
	private function get_passkey(){
		$result = parent::curl('http://baibako.tv/getrss.php',array('feed'=>'dl','login'=>'pass'),self::$cookiefile);
		$passkey = substr($result['out'],strpos($result['out'],'feed=dl&passkey=')+16);
		$passkey = substr($passkey,0,strpos($passkey,'</b>'));
		return $passkey;
	}
	public function browse($args = array()){
		$page = 1;
		if(isset($args['page']) && intval($args['page']) >= 1){
			$page = intval($args['page']) - 1;
		}
		$result = parent::curl('http://baibako.tv/browse.php?page='.$page,false,self::$cookiefile);
		$result = $result['out'];
		$result = iconv('windows-1251','utf-8',$result);
		$data = $this->get_pages($result);
		$result = substr($result,strpos($result,'<tbody id="highlighted">'));
		$result = substr($result,0,strpos($result,'</tbody>'));
		preg_match_all("/<tr>[\s\S]*<td align=\"left\".*>(.*)<\/a>[\s\S]*<a href=\"download\.php\?id=(.*)\">[\s\S]*<\/tr>/U",$result,$matches);
		foreach($matches[1] as $i=>$title){
			$data['torrents'][] = $this->make_array($title,'http://baibako.tv/download.php?id='.$matches[2][$i]);
		}
		return $data;
	}
	public function getNews(){                                   //получение списка последних
		$items = array();
		$file = simplexml_load_file('http://baibako.tv/rss2.php?feed=dl&passkey='.$this->passkey);
		$items = $file->channel->item;
		foreach ($items as $item){
			$title = strval($item->title);
			$link = strval($item->link);
			$items[] = $this->make_array($title,$link);
		}
		return $items;
	}
	public function search($args = array()){                               //поиск по трекеру
		$page = 1;
		if(isset($args['page']) && intval($args['page']) >= 1){
			$page = intval($args['page']) - 1;
		}
		$result = parent::curl('http://baibako.tv/browse.php?search='.iconv('utf-8','windows-1251',$args['query']).'&page='.$page,false,self::$cookiefile);
		$result = $result['out'];
		$result = iconv('windows-1251','utf-8',$result);
		$data = $this->get_pages($result);
		$result = substr($result,strpos($result,'<tbody id="highlighted">'));
		$result = substr($result,0,strpos($result,'</tbody>'));
		preg_match_all("/<tr>[\s\S]*<td align=\"left\".*>(.*)<\/a>[\s\S]*<a href=\"download\.php\?id=(.*)\">[\s\S]*<\/tr>/U",$result,$matches);
		foreach($matches[1] as $i=>$title){
			$data['torrents'][] = $this->make_array($title,'http://baibako.tv/download.php?id='.$matches[2][$i]);
		}
		return $data;
	}
	private function make_array($title,$link){
		$resolution = parent::get_resolution($title);
		$title = explode('/',$title);
		$serial_name = trim(strip_tags($title[0]));
		$eng_name = trim(strip_tags($title[1]));
		$series_info = parent::get_series_info("/s([\d]*)e([\d]*)/",$title[2]);
		return  array('serial_name' => $serial_name,                      //название сериала на руском
									'eng_name'    => $eng_name,                         //название сериала на английском
									'season'      => $series_info['season'],            //сезон
									'series'      => $series_info['series'],            //серия
									'resolution'  => $resolution,                       //тип ссылки (400p/720p/1080p/2160p)
									'link'        => $link,                             //ссылка на файл .torrent
									'sourse'      => 'baibako.tv');                     //источник
	}
	private function get_pages($str){
		$str = substr($str,strpos($str,'<table class="main">'));
		$str = substr($str,0,strpos($str,'</table>'));
		$page = substr($str,strpos($str,'<td class="highlight"><b>')+25);
		$page = intval(substr($page,0,strpos($page,'</b>')));
		preg_match_all("/page=([\d]{0,})/",$str,$matches);
		foreach($matches[1] as $i=>$p){
			$pages[$i] = intval($p);
		}
		arsort($pages);
		$pages = array_shift($pages) + 1;
		return array('page'=>$page,'pages'=>$pages);
	}
	public function download($args){
		if(isset($args['url'])){
			parent::download_file($args['url'],false,self::$cookiefile);
		}
	}
}
?>