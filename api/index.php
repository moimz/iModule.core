<?php
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers:Authorization');
header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control:post-check=0, pre-check=0', false);
header('Pragma:no-cache');
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'index.php','',$_SERVER['SCRIPT_FILENAME']).'/configs/init.config.php';

$IM = new iModule();
$headers = getallheaders();
if (isset($headers['Authorization']) == true) {
	$IM->getModule('member')->loginByToken($headers['Authorization']);
}

if (Request('module') != null) {
	$results = $IM->getModule(Request('module'))->getApi(Request('api'));
	
	if ($results !== null) {
		header("Content-type: text/json; charset=utf-8",true);
		exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}
}
?>