<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 사이트관리자 접속시 실행되는 파일로 iModule core 를 통하여 사이트관리자모듈을 불러오고 사이트관리자 레이아웃을 출력한다.
 * 사이트관리자는 .htaccess 파일에 정의에 따라 2차 메뉴까지 지원하며 domain.com/admin/[menu]/[page] 형태의 주소로 동작한다.
 * 
 * @file /admin/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160903
 */

/**
 * 현재파일의 절대경로를 계산하여 init.confing.php 파일을 불러온다.
 *
 * @see /configs/init.config.php
 */
REQUIRE_ONCE str_replace('/admin','',dirname($_SERVER['SCRIPT_FILENAME'])).'/configs/init.config.php';

$IM = new iModule();
$IM->getModule('admin')->doLayout();
?>