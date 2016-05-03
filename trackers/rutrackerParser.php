<?php
class rutrackerParser extends TrackerParser{
	private static $cookiefile = 'rutracker_cookie.txt';
	protected function authorization(){
		$params = array('login_username'=>$this->login,'login_password'=>$this->password,'login'=>'%D0%92%D1%85%D0%BE%D0%B4');
		$result = parent::curl('http://rutracker.org/forum/login.php',$params,self::$cookiefile);
	}
	public function search($args){
		$query = $args['query'];
		if(isset($args['page'])){
			$page = $args['page'];
		}else{
			$page = 1;
		}
		if(isset($args['categories'])){
			$categories = $args['categories'];
		}else{
			$categories = '-1';
		}
		$start = (intval($page) - 1)*50;
		$result = $this->curl('http://rutracker.org/forum/tracker.php?nm='.$query.'&start='.$start.'&f='.$categories,array('search'=>'dd'),self::$cookiefile);
		//$result = iconv('windows-1251','utf-8',$result['out']);
		$result = $result['out'];
		echo $result;
		preg_match("/med bold.*>.*: (\d*) \D/",$result,$matches);
		$pages = ceil(intval($matches[1])/50);
		$result = substr($result,strpos($result,'<table class="forumline tablesorter" id="tor-tbl">'));
		$result = substr($result,0,strpos($result,'</table>'));

		preg_match_all("/<a class=\"gen f\" href=\"tracker\.php\?f=(.*)\">(.*)<\/a[\s\S]*class=\"med tLink hl-tags bold\"[\s\S]*\">(.*)<\/a[\s\S]*class=\"small tr-dl dl-stub\" href=\".*t=(.*)\">(.*)<\/a>/U",$result,$matches);
		$data = array();
		foreach($matches[2] as $key=>$category){
			$title = $matches[3][$key];
			$link = 'http://dl.rutracker.org/forum/dl.php?t='.$matches[4][$key];
			$size = $matches[5][$key];
			$category_id = $matches[1][$key];
			$size = trim(str_replace('&#8595;','',$size));
			$data[] = array('title'=>$title,'category'=>$category,'category_id'=>$category_id,'link'=>$link,'size'=>$size);
		}
		return array('page'=>intval($page),'pages'=>$pages,'torrents'=>$data);
	}
	public function getCategories(){
		$items['groups'] = array();
		$result = parent::curl('http://rutracker.org/forum/search.php',false,self::$cookiefile);
		$result = iconv('windows-1251','utf-8',$result['out']);
		$result = substr($result,strpos($result,'<fieldset id="fs">'));
		$result = substr($result,0,strpos($result,'</fieldset>'));
		preg_match_all("/<optgroup label=\"&nbsp;(.*)\">([\s\S]*)<\/optgroup>/U",$result,$group_matches);
		foreach($group_matches[1] as $i=>$group_title){
			$categories = array();
			$subcategories = array();
			preg_match_all("/<option.*value=\"(.*)\"(.*)>(.*)<\/option>/U",$group_matches[2][$i],$category_matches);
			foreach($category_matches[3] as $n=>$subcategory_title){
				if(stristr($category_matches[2][$n],'root_forum')){
					$category_title = $subcategory_title;
					$categories[$group_title][] = array('title'=>$category_title,'id'=>$category_matches[1][$n]);
				}else{
					$subcategories[$category_title][] = array('title'=>str_replace(array(' |- ','&nbsp;'),'',$subcategory_title),'id'=>$category_matches[1][$n]);
				}
			}
			foreach($categories[$group_title] as $key=>$group_category){
				if(isset($subcategories[$group_category['title']])){
					$categories[$group_title][$key]['subcategories'] =  $subcategories[$group_category['title']];
				}
			}
			$items['groups'][] = array('title'=>$group_title,'categories'=>$categories[$group_title]);
		}
		return $items;
	}
	public function download($args){
		if(isset($args['url'])){
			parent::download_file($args['url'],false,self::$cookiefile);
		}
	}
}
?>
