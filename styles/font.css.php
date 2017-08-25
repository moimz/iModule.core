<?php
header("Content-Type:text/css");

$language = isset($_GET['language']) == true ? $_GET['language'] : 'ko';
$font = isset($_GET['font']) == true ? explode(',',$_GET['font']) : array();
$default = isset($_GET['default']) == true ? $_GET['default'] : null;

for ($i=0, $loop=count($font);$i<$loop;$i++) {
	if (is_file('./fonts/'.$font[$i].'.css') == true) echo file_get_contents('./fonts/'.$font[$i].'.css');
}

if ($default != null) $fontFamily = $default.', ';
else $fontFamily = '';

if ($language == 'ko') $fontFamily.= '"Apple SD Neo Gothic", "malgun gothic", dotum';

$fontFamily.= ', sans-serif';
echo 'body {font-family:'.$fontFamily.';}';
?>