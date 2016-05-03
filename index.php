<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
if(!is_dir('cookies')){
	mkdir('cookies');
}
$routes = explode('/',$_SERVER['REQUEST_URI']);
if(!empty($routes[1]) && file_exists('trackers/'.$routes[1].'Parser.php' )){
	require_once('TrackersParser.php');
	require_once('trackers/'.$routes[1].'Parser.php');
	$class_name = $routes[1].'Parser';
	$tracker = new $class_name($_REQUEST);
	if(!empty($routes[2]) && method_exists($tracker,$routes[2])){
		$method = $routes[2];
		$result = $tracker -> $method($_REQUEST);
		if(isset($_REQUEST['callback'])){
			echo $_REQUEST["callback"] . "(" . json_encode($result). ")";
		}else{
			echo json_encode($result);
		}
	}
}
?>
