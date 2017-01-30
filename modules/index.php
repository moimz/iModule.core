<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 사이트에 영향을 받지 않고 모듈의 외부컨테이너를 호출한다.
 * 
 * @file /modules/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.161110
 */
REQUIRE_ONCE str_replace('/modules','',dirname($_SERVER['SCRIPT_FILENAME'])).'/configs/init.config.php';

/**
 * iModule 코어를 선언하고, 모듈 컨테이너를 불러온다.
 * 
 * @see /classes/iMdoule.class.php
 */
$IM = new iModule();

/**
 * 컨테이너 호출변수
 */
$module = Request('module');
$container = Request('container');
$view = Request('view');
$idx = Request('idx');
$IM->setContainerMode($module,$container);

define('__IS_MODULE_CONTAINER__',true);

/**
 * 호출변수가 없거나 호출하려는 모듈이 설치가 되어 있지 않은 경우, 에러메세지를 출력한다.
 */
if ($module === null || $container == null || $IM->getModule($module) === null) {
	return $IM->printError('NOT_FOUND_MODULE');
}

/**
 * 모듈에 getContainer 가 없다면 해당모듈은 외부컨테이너를 지원하지 않으므로, 에러메세지를 출력한다.
 */
if (method_exists($IM->getModule($module),'getContainer') === false) {
	return $IM->printError('NOT_SUPPORT_CONTAINER');
}

/**
 * 외부 컨테이너를 호출하여 출력한다.
 */
echo $IM->getModule($module)->getContainer($container);
?>