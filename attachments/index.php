<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일폴더 접근을 막기 위한 인덱스파일
 * 
 * @file /attachments/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 12. 21.
 */
header("HTTP/1.1 403 Forbidden");
header("location:../");
?>