<?php
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'process'.DIRECTORY_SEPARATOR.'index.php','',$_SERVER['SCRIPT_FILENAME']).'/configs/init.config.php';

$IM = new iModule();
$site = $IM->getSite();

$action = Request('action');
if (preg_match('/^@/',$action) == true && $IM->getModule('member')->getMember()->type != 'ADMINISTRATOR') { // For admin action, not administartor
	header('Content-type:text/json; charset=utf-8',true);
	header('Cache-Control:no-store, no-cache, must-revalidate, max-age=0');
	header('Cache-Control:post-check=0, pre-check=0', false);
	header('Pragma:no-cache');
	exit(json_encode(array('success'=>false,'message'=>$IM->getModule('member')->getLanguage('error/forbidden')),JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT));
} else {
	if (Request('module') != null) {
		$results = $IM->getModule(Request('module'))->doProcess(Request('action'));
		
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