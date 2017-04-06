<?php
/**
 * 이 파일은 iModule 사이트템플릿(default)의 일부입니다. (https://www.imodule.kr)
 *
 * iModule 사이트 템플릿으로 iModule 코어에 포함되어 있는 기본템플릿은 주석이 있다.
 * 주석이 없는 템플릿은 iModule 웹사이트에서 다운로드 받을 수 있다.
 * 
 * @file /templets/default/header.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160905
 */
REQUIRE_ONCE '../../../configs/init.config.php';
header("Content-Type:text/css; charset=utf-8");

$IM = new iModule();

/**
 * 사이트템플릿을 가져온다.
 */
$templet = $IM->getSiteTemplet();
$thema_color = $templet->getConfig('thema_color');
?>
header > nav[data-role=navigation] {background:<?php echo $thema_color; ?>;}