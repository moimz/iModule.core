<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 사이트 최초 접속시 실행되는 파일로 기본설정을 불러오고 iModule core 클래스를 선언하여 사이트 레이아웃을 불러온다.
 * 사이트 메뉴는 .htaccess 에 의해 설정된 방식인 domain.com/[언어]/[menu]/[view]/[idx] 형태의 주소로 동작한다.
 * 
 * @file index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 4. 20.
 */
define('__IM_SITE__',true);

/**
 * 파일의 절대경로를 계산하여 init.confing.php 파일을 불러온다.
 *
 * @see /configs/init.config.php
 */

REQUIRE_ONCE dirname($_SERVER['SCRIPT_FILENAME']).'/configs/init.config.php';

/**
 * iModule 코어를 선언하고, 레이아웃을 불러온다.
 * 
 * @see /classes/iMdoule.class.php
 */
$IM = new iModule();
$IM->doLayout();
?>