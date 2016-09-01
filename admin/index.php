<?php
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'admin'.DIRECTORY_SEPARATOR.'index.php','',$_SERVER['SCRIPT_FILENAME']).'/configs/init.config.php';

$IM = new iModule();
$IM->getModule('admin')->doLayout();
?>