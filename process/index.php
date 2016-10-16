<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 모듈에서 작업을 처리하기 위해 모든 작업요청을 받는다.
 * 
 * @file /process/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160905
 */
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'process'.DIRECTORY_SEPARATOR.'index.php','',$_SERVER['SCRIPT_FILENAME']).'/configs/init.config.php';

/**
 * iModule 코어클래스를 선언한다.
 */
$IM = new iModule();

/**
 * 요청작업을 수행할 모듈 및 요청작업코드
 */
$module = Request('module');
$action = Request('action');

/**
 * 관리자모듈과 첨부파일열기에서는 사이트 데이터를 초기화하지 않는다.
 */
if ($module == 'admin' || ($module == 'attachment' && in_array($action,array('original','view','thumbnail','download')) == true)) {
	
} else {
	$site = $IM->getSite();
}

/**
 * 작업코드가 @ 로 시작할 경우 관리자권한으로 동작하는 작업으로 관리자권한을 확인한다.
 */
if (preg_match('/^@/',$action) == true && $IM->getModule('member')->isAdmin() == false) {
	header('Content-type:text/json; charset=utf-8',true);
	header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
	header('Cache-Control:post-check=0, pre-check=0', false);
	header('Pragma:no-cache');
	exit(json_encode(array('success'=>false,'message'=>$IM->getErrorText('FORBIDDEN')),JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));
} else {
	if (Request('module') != null) {
		$results = $IM->getModule(Request('module'),true)->doProcess(Request('action'));
		
		if ($results !== null) {
			header('Content-type:text/json; charset=utf-8',true);
			header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
			header('Cache-Control:post-check=0, pre-check=0', false);
			header('Pragma:no-cache');
			exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));
		}
	}
}
?>