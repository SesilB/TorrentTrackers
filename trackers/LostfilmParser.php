<?php
class LostfilmParser extends TrackerParser{
	private static $cookiefile = 'lostfilm_cookie.txt';
	protected function authorization(){
		$params = array('login'=>$this->login,'password'=>$this->password,'module'=>1,'target'=>'http%3A%2F%2Flostfilm.tv%2F','repage'=>'user','act'=>'login');
		$result = parent::curl('http://login1.bogi.ru/login.php?referer=https%3A%2F%2Fwww.lostfilm.tv%2F',$params,self::$cookiefile,false,array('Origin: http://www.lostfilm.tv','Referer: http://www.lostfilm.tv/','Content-Type: application/x-www-form-urlencoded'));
		$params = array();
		preg_match_all("/type=\"hidden\" name=\"(.*)\" value=\"(.*)\"/U",$result['out'],$matches);
		foreach($matches[1] as $i=>$name){
			$params[$name] = $matches[2][$i];
		}
		$url = substr($result['out'],strpos($result['out'],'action="')+8);
		$url = substr($url,0,strpos($url,'"'));
		$result = parent::curl($url,$params,self::$cookiefile);
	}
	public function browse($args = array()){
		$page = 1;
		if(isset($args['page']) && intval($args['page']) >= 1){
			$page = intval($args['page']);
		}
		$page = ($page - 1)*15;
		$result = parent::curl('http://www.lostfilm.tv/browse.php?o='.$page);
		$result = $result['out'];
		$page = substr($result,strpos($result,'d_pages_link_selected">')+23);
		$page = substr($page,0,strpos($page,'<'));
		$data['page'] = $page;
		$pages = substr($result,strripos($result,'d_pages_link'));
		$pages = substr($pages,strpos($pages,'>')+1);
		$pages = substr($pages,0,strpos($pages,'<'));
		$data['pages'] = $pages;
		$result = substr($result,strpos($result,'<div class="content_body">'));
		$result = substr($result,0,strpos($result,'<div class="block_bottoml">'));
		$retre_content = '';
		preg_match_all("/discuss.php\?cat=(.*)\"/U",$result,$matches);
		foreach($matches[1] as $id){
			$result = parent::curl('http://www.lostfilm.tv/nrdr2.php?c='.$id,false,self::$cookiefile);
			$result = $result['out'];
			$result = substr($result,strpos($result,'url=')+4);
			$result = substr($result,0,strpos($result,'"'));
			$result = parent::curl($result);
			$result = $result['out'];
			$result = iconv('windows-1251','utf-8',$result);
			$result = substr($result,strripos($result,'<div style="width:100%">'));
			$result = substr($result,0,strripos($result,'<div style="position:absolute;font-family:verdana;font-size:12px;color:#585757;top:100%;'));
			$retre_content .=$result;
		}
		preg_match_all("/<td>[\s\S]*\">(.*)<\/a>[\s\S]*<a href=\"(.*)\"[\s\S]*<\/tr>/U",$retre_content,$matches);
		foreach($matches[1] as $i=>$title){
			$link = $matches[2][$i];
			$resolution = parent::get_resolution($title);
			$serial_name = trim(strip_tags(substr($title,0,strripos($title,'('))));
			$eng_name = substr($title,strripos($title,'(')+1);
			$eng_name = trim(strip_tags(substr($eng_name,0,strripos($eng_name,')'))));
			$series_info = parent::get_series_info("/([\d]*) сезон, ([\d]*) серия/",substr($title,strripos($title,').')+2));
			$data['torrents'][] = array('serial_name' => $serial_name,                      //название сериала на руском
																	'eng_name'    => $eng_name,                         //название сериала на английском
																	'season'      => $series_info['season'],            //сезон
																	'series'      => $series_info['series'],            //серия
																	'resolution'  => $resolution,                       //тип ссылки (400p/720p/1080p/2160p)
																	'link'        => $link,                             //ссылка на файл .torrent
																	'sourse'      => 'lostfilm.tv');                    //источник
		}
		return $data;
	}
	public function getNews(){
		$data = $this->browse();
		return $data['torrents'];
	}
	public function download($args = array()){
		if(isset($args['url'])){
			parent::download_file($args['url'],false,self::$cookiefile);
		}
	}
}
?>