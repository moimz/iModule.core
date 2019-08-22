<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * iModule 크론작업을 실행한다.
 * 
 * @file /cron/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 12. 21.
 */
define('__IM_CRON_PATH__',str_replace(DIRECTORY_SEPARATOR.'cron/index.php','',$_SERVER['PHP_SELF']));
REQUIRE_ONCE __IM_CRON_PATH__.'/classes/Cron.class.php';

$_SERVER['DOCUMENT_ROOT'] = str_replace(DIRECTORY_SEPARATOR.'cron','',__IM_CRON_PATH__);

$hosts = array_slice($argv,1);
$cron = new Cron($hosts);
$cron->run();
exit;
?>