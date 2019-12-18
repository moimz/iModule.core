<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * 모듈에서 작업을 처리하기 위해 모든 작업요청을 받는다.
 * 
 * @file /process/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 12. 18.
 */
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'process','',__DIR__).'/configs/init.config.php';

set_time_limit(0);
@ini_set('memory_limit',-1);
@ini_set('zlib.output_compression','Off');
@ini_set('output_buffering','Off');
@ini_set('output_handler','');
if (function_exists('apache_setenv') == true) {
	@apache_setenv('no-gzip',1);
}

/**
 * iModule 코어클래스를 선언한다.
 */
$IM = new iModule();
$IM->setLanguage(Request('_language') ? Request('_language') : 'default');

/**
 * 요청작업을 수행할 모듈 및 요청작업코드
 */
$_module = Request('_module');
$_action = Request('_action');

/**
 * 관리자모듈과 첨부파일열기에서는 사이트맵을 초기화하지 않는다.
 */
if ($_module == 'admin' || ($_module == 'attachment' && in_array($_action,array('original','view','thumbnail','download')) == true)) {
	$site = $IM->getSite(false);
} else {
	$site = $IM->getSite();
}

/**
 * 작업코드가 @ 로 시작할 경우 관리자권한으로 동작하는 작업으로 관리자권한을 확인한다.
 */
if (preg_match('/^@/',$_action) == true && $IM->getModule('member')->isAdmin() == false && (method_exists($IM->getModule($_module),'isAdmin') === false || $IM->getModule($_module)->isAdmin() === false) && $IM->getModule('admin')->checkProcessPermission($_module,$_action) == false) {
	header('Content-type:text/json; charset=utf-8',true);
	header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
	header('Cache-Control:post-check=0, pre-check=0', false);
	header('Pragma:no-cache');
	
	exit(json_encode(array('success'=>false,'message'=>$IM->getErrorText('FORBIDDEN')),JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
} else {
	if ($_module != null) {
		if (preg_match('/^@/',$_action) == true) $IM->getModule('admin')->saveProcessLog($_module,$_action);
		if (preg_match('/^@?(check|delete|download|move|save|update)/',$_action) == true) session_write_close();
		$results = $IM->getModule($_module,true)->doProcess($_action);
		
		if ($results !== null) {
			header('Content-type:text/json; charset=utf-8',true);
			header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
			header('Cache-Control:post-check=0, pre-check=0', false);
			header('Pragma:no-cache');
			exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		}
	}
}
?>