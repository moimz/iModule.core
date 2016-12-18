<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * iModule의 API요청을 처리한다.
 * 
 * @file index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.161110
 */

header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control:post-check=0, pre-check=0', false);
header('Pragma:no-cache');
REQUIRE_ONCE str_replace('/api','',dirname($_SERVER['SCRIPT_FILENAME'])).'/configs/init.config.php';

$IM = new iModule();
$headers = getallheaders();

header('Access-Control-Allow-Origin:'.$headers['Origin']);
header('Access-Control-Allow-Credentials:true');
header('Access-Control-Allow-Headers:Authorization');
header('Access-Control-Allow-Methods:*');

if (isset($headers['Authorization']) == true) {
	$IM->getModule('member')->authorizationToken($headers['Authorization']);
}

$results = new stdClass();
$module = Request('module');
if ($module != null && $IM->Module->isInstalled($module) == true) {
	$mModule = $IM->getModule($module);
	$method = $_SERVER['REQUEST_METHOD'];
	
	$api = Request('api');
	$idx = Request('idx');
	if ($method == 'POST' && method_exists($mModule,'postApi') == true) {
		$params = $_POST;
		$data = $mModule->postApi($api,$idx,$params);
	} elseif ($method == 'GET' && method_exists($mModule,'getApi') == true) {
		$params = $_GET;
		unset($params['module'],$params['api'],$params['idx']);
		$data = $mModule->getApi($api,$idx,$params);
	} else {
		$data = null;
	}
	
	if ($data !== null) {
		$results = $data;
	} else {
		$results->success = false;
		$results->message = $IM->getErrorText('UNREGISTED_API_NAME');
	}
} else {
	$results->success = false;
	$results->message = $IM->getErrorText('UNREGISTED_API_NAME');
}

header("Content-type: text/json; charset=utf-8",true);
exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
?>