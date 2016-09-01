<?php
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'language.js.php','',$_SERVER['SCRIPT_FILENAME']).'/configs/init.config.php';
header('Content-Type: application/x-javascript; charset=utf-8');

$languages = explode(',',Request('languages'));

for ($i=0, $loop=count($languages);$i<$loop;$i++) {
	list($module,$language,$defaultLanguage) = explode('@',$languages[$i]);
	
	$oLang = null;
	if (is_file(__IM_PATH__.'/modules/'.$module.'/languages/'.$language.'.json') == true) {
		$lang = file_get_contents(__IM_PATH__.'/modules/'.$module.'/languages/'.$language.'.json');
		if ($language != $defaultLanguage) $oLang = file_get_contents(__IM_PATH__.'/modules/'.$module.'/languages/'.$defaultLanguage.'.json');
	} elseif (is_file(__IM_PATH__.'/modules/'.$module.'/languages/'.$defaultLanguage.'.json') == true) {
		$lang = file_get_contents(__IM_PATH__.'/modules/'.$module.'/languages/'.$defaultLanguage.'.json');
	} else {
		$lang = null;
	}
	
	if ($lang != null) echo 'iModule.addLanguage("'.ucfirst($module).'",'.$lang.','.($oLang == null ? 'null' : $oLang).');'.PHP_EOL;
}
?>