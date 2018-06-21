<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 자바스크립트내에서 언어셋을 사용하기 위한 함수를 정의한다.
 * 
 * @file /scripts/language.js.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 5. 27.
 */
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'scripts','',__DIR__).'/configs/init.config.php';
header('Content-Type: application/x-javascript; charset=utf-8');

$language = Request('language');
$languages = Request('languages') ? explode(',',Request('languages')) : array();

/**
 * iModule 코어 언어셋을 불러온다.
 */
$package = json_decode(file_get_contents(__IM_PATH__.'/package.json'));
$lang = null;
$oLang = null;
if (is_file(__IM_PATH__.'/languages/'.$language.'.json') == true) {
	$lang = file_get_contents(__IM_PATH__.'/languages/'.$language.'.json');
	if ($language != $package->language) $oLang = file_get_contents(__IM_PATH__.'/languages/'.$package->language.'.json');
} elseif (is_file(__IM_PATH__.'/languages/'.$package->language.'.json') == true) {
	$lang = file_get_contents(__IM_PATH__.'/languages/'.$package->language.'.json');
}

if ($lang != null) echo 'iModule.addLanguage("core","",'.$lang.','.($oLang == null ? 'null' : $oLang).');'.PHP_EOL;

for ($i=0, $loop=count($languages);$i<$loop;$i++) {
	list($type,$target,$defaultLanguage) = explode('@',$languages[$i]);
	
	$lang = null;
	$oLang = null;
	
	if ($type == 'module') {
		if (is_file(__IM_PATH__.'/modules/'.$target.'/languages/'.$language.'.json') == true) {
			$lang = file_get_contents(__IM_PATH__.'/modules/'.$target.'/languages/'.$language.'.json');
			if ($language != $defaultLanguage) $oLang = file_get_contents(__IM_PATH__.'/modules/'.$target.'/languages/'.$defaultLanguage.'.json');
		} elseif (is_file(__IM_PATH__.'/modules/'.$target.'/languages/'.$defaultLanguage.'.json') == true) {
			$lang = file_get_contents(__IM_PATH__.'/modules/'.$target.'/languages/'.$defaultLanguage.'.json');
		} else {
			$lang = null;
		}
		
		if ($lang != null) echo 'iModule.addLanguage("'.$type.'","'.ucfirst($target).'",'.$lang.','.($oLang == null ? 'null' : $oLang).');'.PHP_EOL;
	} elseif ($type == 'plugin') {
		if (is_file(__IM_PATH__.'/plugins/'.$target.'/languages/'.$language.'.json') == true) {
			$lang = file_get_contents(__IM_PATH__.'/plugins/'.$target.'/languages/'.$language.'.json');
			if ($language != $defaultLanguage) $oLang = file_get_contents(__IM_PATH__.'/plugins/'.$target.'/languages/'.$defaultLanguage.'.json');
		} elseif (is_file(__IM_PATH__.'/plugins/'.$target.'/languages/'.$defaultLanguage.'.json') == true) {
			$lang = file_get_contents(__IM_PATH__.'/plugins/'.$target.'/languages/'.$defaultLanguage.'.json');
		} else {
			$lang = null;
		}
		
		if ($lang != null) echo 'iModule.addLanguage("'.$type.'","'.$target.'",'.$lang.','.($oLang == null ? 'null' : $oLang).');'.PHP_EOL;
	}
}
?>