<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 템플릿폴더 접근을 막기 위한 인덱스파일
 * 
 * @file /templets/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 3. 29.
 */
header("HTTP/1.1 403 Forbidden");
header("location:../");
?>