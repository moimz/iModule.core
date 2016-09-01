<?php
header('Content-Type:text/css');

$language = $_GET['language'];
$font = explode(',',$_GET['font']);
$default = isset($_GET['default']) == true ? $_GET['default'] : null;

for ($i=0, $loop=count($font);$i<$loop;$i++) echo file_get_contents('./fonts/'.$font[$i].'.css');

if ($default != null) $fontFamily = $default.', ';
else $fontFamily = '';

if ($language == 'ko') $fontFamily.= '"Apple SD Neo Gothic", "malgun gothic", dotum';

$fontFamily.= ', sans-serif';
echo 'body {font-family:'.$fontFamily.';}';
?>