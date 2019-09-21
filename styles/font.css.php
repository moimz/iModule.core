<?php
/**
 * 이 파일은 MoimzTools 의 일부입니다. (https://www.moimz.com)
 *
 * 사용하려고 하는 웹폰트명을 받아, 웹폰트파일 및 폰트스타일시트를 불러온다.
 *
 * @file /styles/font.css.php
 * @author Arzz
 * @license MIT License
 * @version 1.0.0
 * @modified 2019. 6. 26.
 */
header("Content-Type:text/css");

$language = isset($_GET['language']) == true ? $_GET['language'] : 'ko';
$font = isset($_GET['font']) == true ? explode(',',$_GET['font']) : array();
$default = isset($_GET['default']) == true ? $_GET['default'] : null;

$css = '';
for ($i=0, $loop=count($font);$i<$loop;$i++) {
	if (is_file('./fonts/'.$font[$i].'.css') == true) $css.= file_get_contents('./fonts/'.$font[$i].'.css');
}
echo str_replace('../../fonts','../fonts',$css);

if ($default != null) $fontFamily = $default.', ';
else $fontFamily = '';

if ($language == 'ko') $fontFamily.= '"Apple SD Neo Gothic", "malgun gothic", dotum';
else $fontFamily.= 'Arial';
$fontFamily.= ', sans-serif';
echo 'html, body {font-family:'.$fontFamily.';}';
?>