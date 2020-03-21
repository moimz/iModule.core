<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * iModule의 API요청을 처리한다.
 * 
 * @file /api/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2020. 3. 21.
 */
header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control:post-check=0, pre-check=0', false);
header('Pragma:no-cache');
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'api','',__DIR__).'/configs/init.config.php';

$IM = new iModule();
$headers = getallheaders();

header('Access-Control-Allow-Origin:'.(isset($headers['Origin']) == true ? $headers['Origin'] : '*'));
header('Access-Control-Allow-Credentials:true');
header('Access-Control-Allow-Headers:Authorization');
header('Access-Control-Allow-Methods:*');

if (isset($headers['Authorization']) == true || isset($headers['authorization']) == true) {
	$IM->getModule('member')->authorizationToken(isset($headers['Authorization']) == true ? $headers['Authorization'] : $headers['authorization']);
}

$site = $IM->getSite();

$results = new stdClass();
$_module = Request('_module');
if ($_module != null && $IM->Module->isInstalled($_module) == true) {
	$mModule = $IM->getModule($_module);
	$protocol = strtolower($_SERVER['REQUEST_METHOD']);
	
	$_api = Request('_api');
	$_idx = Request('_idx');
	
	if (method_exists($mModule,'getApi') == true) {
		$params = $_REQUEST;
		unset($_REQUEST['_module']);
		unset($_REQUEST['_api']);
		unset($_REQUEST['_idx']);
		
		$params = count($params) == 0 ? null : (object)$params;
		$data = $mModule->getApi($protocol,$_api,$_idx,$params);
	} else {
		$data = null;
	}
	
	if ($data !== null && isset($data->success) == true) {
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
exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
?>