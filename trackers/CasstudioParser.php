<?php
class CasstudioParser extends TrackerParser{
	private static $cookiefile = 'casstudio_cookie.txt';
	protected function authorization(){
		$params = array('username'=>$this->login,'password'=>$this->password,'login'=>'¬ход');
		$result = parent::curl('http://casstudio.tv/ucp.php?mode=login',$params,self::$cookiefile);
	}
	public function browse($args=array()){
		$page = 1;
		if(isset($args['page']) && intval($args['page']) >= 1){
			$page = intval($args['page']); 
		}
		$result = parent::curl('http://casstudio.tv/?l=24&p='.$page,false,self::$cookiefile);
		preg_match_all("/viewtopic\.php\?t.*title=\"(.*)\"[\s\S]*btn-success\" href=\"(.*)\"[\s\S]*<\/i>([\s\S]*)<\/a>/U",$result['out'],$matches);
		foreach($matches[1] as $i=>$title){
			echo $title.' '.$matches[2][$i].' '.trim($matches[3][$i]).'<br>';
		}
		//echo $result['out'];
	}
	public function search($args=array()){
		$result = parent::curl('http://casstudio.tv/search.php?keywords='.$args['query'].'&sr=topics&tracker_search=torrent',false,self::$cookiefile);
		preg_match_all("/download\/file\.php\?id=(.*)\"[\s\S]*topictitle\">(.*)<\/a>[\s\S]*змер\: <b>(.*)<\/b>/U",$result['out'],$matches);
		foreach($matches[2] as $i=>$title){
			echo $title.' '.$matches[1][$i].' '.$matches[3][$i].'<br>';
		}
	}
}
?>