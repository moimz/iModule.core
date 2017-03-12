<?php
/**
 * This file is part of Moimz Tools - https://www.moimz.com
 *
 * @file functions.class.php
 * @author Arzz
 * @version 1.1.8
 * @license MIT License
 */
function Request($var,$type='request') {
	global $_REQUEST, $_SESSION;

	switch ($type) {
		case 'request' :
			$value = isset($_REQUEST[$var]) == true ? $_REQUEST[$var] : null;
		break;

		case 'session' :
			$value = isset($_SESSION[$var]) == true ? $_SESSION[$var] : null;
		break;

		case 'cookie' :
			$value = isset($_COOKIE[$var]) == true ? $_COOKIE[$var] : null;
		break;
	}

	if ($value === null) return null;
	if (is_array($value) == false) return trim($value);
	return $value;
}

function Encoder($value,$key='') {
	global $_CONFIGS;
	
	$key = $key ? md5($key) : md5($_CONFIGS->key);
	$padSize = 16 - (strlen($value) % 16);
	$value = $value.str_repeat(chr($padSize),$padSize);
	$output = mcrypt_encrypt(MCRYPT_RIJNDAEL_128,$key,$value,MCRYPT_MODE_CBC,str_repeat(chr(0),16));
	return base64_encode($output);
}

function Decoder($value,$key='') {
	global $_CONFIGS;
	
	$key = $key ? md5($key) : md5($_CONFIGS->key);
	$value = base64_decode(str_replace(' ','+',$value));
	
	$output = mcrypt_decrypt(MCRYPT_RIJNDAEL_128,$key,$value,MCRYPT_MODE_CBC,str_repeat(chr(0),16));
	$valueLen = strlen($output);
	if ($valueLen % 16 > 0) return false;

	$padSize = ord($output{$valueLen - 1});
	if (($padSize < 1) || ($padSize > 16)) return false;

	for ($i=0;$i<$padSize;$i++) {
		if (ord($output{$valueLen - $i - 1}) != $padSize) return false;
	}
	
	return substr($output,0,$valueLen-$padSize);
}

function FileReadLine($path,$line) {
	if (is_file($path) == true) {
		$file = @file($path);
		if (isset($file[$line]) == false) throw new Exception('Line Overflow : '.$path.'(Line '.$line.')');
		return trim($file[$line]);
	} else {
		throw new Exception('Not Found : '.$path);
		return '';
	}
}

/**
 * 이메일이 형식에 맞는지 확인한다.
 *
 * @param string $email 이메일
 * @return boolean $isValid
 */
function CheckEmail($email) {
	return preg_match('/^[[:alnum:]]+([_.-\]\+[[:alnum:]]+)*[_.-]*@([[:alnum:]]+([.-][[:alnum:]]+)*)+.[[:alpha:]]{2,4}$/',$email);
}

/**
 * 실명에 포함될 수 없는 문자열이 있는지 확인한다.
 *
 * @param string $name 실명
 * @return boolean $isValid
 */
function CheckName($name) {
	if (preg_match('/[~!@#\$%\^&\*\(\)\-_\+\=\[\]\<\>\/\?\'":;\{\}\x{25a0}-\x{25ff}\x{2600}-\x{26ff}]+/u',$name) == true) return false;
	if (mb_strlen($name,'utf-8') < 2) return false;
	
	return true;
}

/**
 * 닉네임이 형식에 맞는지 확인한다.
 *
 * @param string $nickname 닉네임
 * @return boolean $isValid
 */
function CheckNickname($nickname) {
	if (preg_match('/[~!@#\$%\^&\*\(\)\-_\+\=\[\]\<\>\/\?\'":;\{\}\x{25a0}-\x{25ff}\x{2600}-\x{26ff}[:space:]]+/u',$nickname) == true) return false;
	if (mb_strlen($nickname,'utf-8') < 2 || mb_strlen($nickname,'utf-8') > 10) return false;
	
	return true;
}

/**
 * 전화번호 형식이 맞는지 확인한다.
 *
 * @param string $phone
 * @return boolean $isValid
 */
function CheckPhoneNumber($phone) {
	$phone = str_replace('-','',$phone);
	return preg_match('/^0[0-9]{9,11}$/',$phone);
}

function GetAntiSpamEmail($email,$isLink=true) {
	$email = str_replace('@','<i class="fa fa-at"></i>',$email);
	return $isLink == true ? '<span class="iModuleEmail">'.$email.'</span>' : $email;
}

function GetTime($format,$time=null) {
	$time = $time === null ? time() : $time;

	$replacements = array(
		'd' => 'DD',
		'D' => 'ddd',
		'j' => 'D',
		'l' => 'dddd',
		'N' => 'E',
		'S' => 'o',
		'w' => 'e',
		'z' => 'DDD',
		'W' => 'W',
		'F' => 'MMMM',
		'm' => 'MM',
		'M' => 'MMM',
		'n' => 'M',
		't' => '', // no equivalent
		'L' => '', // no equivalent
		'o' => 'YYYY',
		'Y' => 'YYYY',
		'y' => 'YY',
		'a' => 'a',
		'A' => 'A',
		'B' => '', // no equivalent
		'g' => 'h',
		'G' => 'H',
		'h' => 'hh',
		'H' => 'HH',
		'i' => 'mm',
		's' => 'ss',
		'u' => 'SSS',
		'e' => 'zz', // deprecated since version 1.6.0 of moment.js
		'I' => '', // no equivalent
		'O' => '', // no equivalent
		'P' => '', // no equivalent
		'T' => '', // no equivalent
		'Z' => '', // no equivalent
		'c' => '', // no equivalent
		'r' => '', // no equivalent
		'U' => 'X'
	);
	$momentFormat = strtr($format,$replacements);
	return '<time datetime="'.date('c',$time).'" data-time="'.$time.'" data-format="'.$format.'" data-moment="'.$momentFormat.'">'.date($format,$time).'</time>';
}

function GetPhoneNumber($phone) {
	$phone = str_replace('-','',$phone);
	if (strlen($phone) < 9) return '';

	if (substr($phone,0,2) == '02') {
		if (strlen($phone) == 10) {
			$value = substr($phone,0,2).'-'.substr($phone,2,4).'-'.substr($phone,6,4);
		} else {
			$value = substr($phone,0,2).'-'.substr($phone,2,3).'-'.substr($phone,5,4);
		}
	} else {
		if (strlen($phone) == 11) {
			$value = substr($phone,0,3).'-'.substr($phone,3,4).'-'.substr($phone,7,4);
		} else {
			$value = substr($phone,0,3).'-'.substr($phone,3,3).'-'.substr($phone,6,4);
		}
	}

	return $value;
}

function GetString($str,$code) {
	switch ($code) {
		case 'inputbox' :
			$str = str_replace('<','&lt;',$str);
			$str = str_replace('>','&gt;',$str);
			$str = str_replace('"','&quot;',$str);
			$str = str_replace("'",'\'',$str);
		break;
		
		case 'input' :
			$str = str_replace('<','&lt;',$str);
			$str = str_replace('>','&gt;',$str);
			$str = str_replace('"','&quot;',$str);
			$str = str_replace("'",'\'',$str);
		break;
		
		case 'decode' :
			$str = str_replace('&lt;','<',$str);
			$str = str_replace('&gt;','>',$str);
			$str = str_replace('&#39;','\'',$str);
		break;

		case 'replace' :
			$str = str_replace('<','&lt;',$str);
			$str = str_replace('>','&gt;',$str);
			$str = str_replace('"','&quot;',$str);
		break;

		case 'xml' :
			$str = str_replace('&','&amp;',$str);
			$str = str_replace('<','&lt;',$str);
			$str = str_replace('>','&gt;',$str);
			$str = str_replace('"','&quot;',$str);
			$str = str_replace("'",'&apos;',$str);
		break;

		case 'default' :
			$allow = '<p>,<br>,<b>,<span>,<a>,<img>,<embed>,<i>,<u>,<strike>,<font>,<center>,<ol>,<li>,<ul>,<strong>,<em>,<div>,<table>,<tr>,<td>';
			$str = strip_tags($str, $allow);
		break;

		case 'delete' :
			$str = stripslashes($str);
			$str = strip_tags($str);
			$str = str_replace('&nbsp;','',$str);
			$str = str_replace('"','&quot;',$str);
		break;

		case 'encode' :
			$str = urlencode($str);
		break;
		
		case 'reg' :
			$str = str_replace('[','\[',$str);
			$str = str_replace(']','\]',$str);
			$str = str_replace('(','\(',$str);
			$str = str_replace(')','\)',$str);
			$str = str_replace('?','\?',$str);
			$str = str_replace('.','\.',$str);
			$str = str_replace('*','\*',$str);
			$str = str_replace('-','\-',$str);
			$str = str_replace('+','\+',$str);
			$str = str_replace('^','\^',$str);
			$str = str_replace('\\','\\\\',$str);
			$str = str_replace('$','\$',$str);
			$str = str_replace('/','\/',$str);
		break;
		
		case 'index' :
			$str = preg_replace('/<(P|p)>/',' <p>',$str);
			$str = strip_tags($str);
			$str = preg_replace('/&[a-z]+;/',' ',$str);
			$str = preg_replace('/\r\n/',' ',$str);
			$str = str_replace("\n",' ',$str);
			$str = str_replace("\t",' ',$str);
			$str = preg_replace('/[[:space:]]+/',' ',$str);
	}
	
	return trim($str);
}

function GetCutString($str,$limit,$is_html=false) {
	$str = strip_tags($str,'<b><span><strong><i><u><font>');
	$length = mb_strlen($str,'UTF-8');

	$tags = array();
	$htmlLength = 0;
	$countLength = 0;

	$tag = false;
	if ($is_html == true) {
		for ($i=0; $i<=$length && $countLength<$limit;$i++) {
			$LastStr = mb_substr($str,$i,1,'UTF-8');
			if ($LastStr == '<' && preg_match('/^(b|span|strong|i|u|font)+/i',mb_substr($str,$i+1,$length-$i,'UTF-8'),$matchs) == true) {
				$tag = true;
				$tempLength = mb_strlen($matchs[1]);
				$htmlLength = $htmlLength+$tempLength+1;
				$i = $i+$tempLength;
				$tags[] = $matchs[1];

				continue;
			}

			if ($LastStr == '<' && preg_match('/^\/(b|span|strong|i|u|font)+/i',mb_substr($str,$i+1,$length-$i,'UTF-8'),$matchs) == true) {
				$tag = true;
				$tempLength = mb_strlen($matchs[1]);
				$htmlLength = $htmlLength+$tempLength+2;
				$i = $i+$tempLength+1;

				if (strlen(array_search($matchs[1],$tags)) > 0) {
					$tags[array_search($matchs[1],$tags)] = '-1';
				}

				continue;
			}

			if ($tag == true && $LastStr == '>') {
				$tag = false;
				$htmlLength++;
				continue;
			}

			if ($tag == true) {
				$htmlLength++;
				continue;
			}

			if ($tag == false) {
				$countLength++;
			}

			if ($countLength > $limit) {
				break;
			}
		}

		$limit = $limit+$htmlLength;
	}

	$isCut = false;
	if ($length >= $limit) {
		$isCut = true;
		$str = mb_substr($str,0,$limit,"UTF-8");
	} else {
		$str = $str;
	}

	if (sizeof($tags) > 0) {
		$tags = array_reverse($tags);
		for ($i=0, $loop=sizeof($tags);$i<$loop;$i++) {
			if ($tags[$i] != '-1') $str.= '</'.$tags[$i].'>';
		}
	}

	if ($isCut == true) $str.= '...';

	return $str;
}

function GetRandomString($length=10) {
	return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),0,$length);
}

/**
 * change ip's 3rd number to *
 *
 * @param string $ip (ex : 127.0.0.1)
 * @return string $hiddenIp (ex : 127.0.***.1)
 */
function GetHiddenIp($ip) {
	$temp = explode('.',$ip);
	$temp[2] = '***';
	return implode('.',$temp);
}

function SaveFileFromUrl($url,$filename,$filetype=null) {
	$parseURL = parse_url($url);

	$scheme = isset($parseURL['scheme']) == true ? $parseURL['scheme'] : '';
	$host = isset($parseURL['host']) == true ? $parseURL['host'] : '';
	$port = isset($parseURL['port']) == true ? $parseURL['port'] : ($parseURL['scheme'] == 'https' ? '443' : '80');
	$path = isset($parseURL['path']) == true ? $parseURL['path'] : '';
	$query = isset($parseURL["query"]) == true ? $parseURL["query"] : '';

	$ch = curl_init();
	if ($scheme == 'https') curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_POST,0);
	curl_setopt($ch,CURLOPT_REFERER,$url);
	curl_setopt($ch,CURLOPT_TIMEOUT,30);
	curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
	curl_setopt($ch,CURLOPT_AUTOREFERER,1);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	$buffer = curl_exec($ch);
	$info = curl_getinfo($ch);
	$error = curl_error($ch);
	curl_close($ch);
	
	if ($info['http_code'] != 200 || ($filetype != null && preg_match('/'.$filetype.'/',$info['content_type']) == false)) {
		return false;
	}
	
	$fp = fopen($filename,'w');
	fwrite($fp,$buffer);
	fclose($fp);

	if (file_exists($filename) == false || filesize($filename) == 0) {
		unlink($filepath);
		$filepath = '';
	}

	return true;
}

function AntiXSS($data) {
	global $IM;
	
	REQUIRE_ONCE __IM_PATH__.'/classes/HTMLPurifier/HTMLPurifier.auto.php';

	$config = HTMLPurifier_Config::createDefault();
	$config->set('Cache.SerializerPath',$IM->getAttachmentPath().'/temp');
	$config->set('Attr.EnableID',false);
	$config->set('Attr.DefaultImageAlt','');
	$config->set('AutoFormat.Linkify',false);
	$config->set('HTML.MaxImgLength',null);
	$config->set('CSS.MaxImgLength',null);
	$config->set('CSS.AllowTricky',true);
	$config->set('Core.Encoding','UTF-8');
	$config->set('HTML.FlashAllowFullScreen',true);
	$config->set('HTML.SafeEmbed',true);
	$config->set('HTML.SafeIframe',true);
	$config->set('HTML.SafeObject',true);
	$config->set('Output.FlashCompat',true);

	$config->set('URI.SafeIframeRegexp', '#^(?:https?:)?//(?:'.implode('|', array(
		'www\\.youtube(?:-nocookie)?\\.com/',
		'maps\\.google\\.com/',
		'player\\.vimeo\\.com/video/',
		'www\\.microsoft\\.com/showcase/video\\.aspx',
		'(?:serviceapi\\.nmv|player\\.music)\\.naver\\.com/',
		'(?:api\\.v|flvs|tvpot|videofarm)\\.daum\\.net/',
		'v\\.nate\\.com/',
		'play\\.mgoon\\.com/',
		'channel\\.pandora\\.tv/',
		'www\\.tagstory\\.com/',
		'play\\.pullbbang\\.com/',
		'tv\\.seoul\\.go\\.kr/',
		'ucc\\.tlatlago\\.com/',
		'vodmall\\.imbc\\.com/',
		'www\\.musicshake\\.com/',
		'www\\.afreeca\\.com/player/Player\\.swf',
		'static\\.plaync\\.co\\.kr/',
		'video\\.interest\\.me/',
		'player\\.mnet\\.com/',
		'sbsplayer\\.sbs\\.co\\.kr/',
		'img\\.lifestyler\\.co\\.kr/',
		'c\\.brightcove\\.com/',
		'www\\.slideshare\\.net/',
	)).')#');

	$purifier = new HTMLPurifier($config);
	return $purifier->purify($data);
}

/**
 * 자동링크를 생성한다.
 *
 * @param string $text 본문내용
 * @param string $linkText 링크태그가 추가된 본문내용 
 */
function AutoLink($text) {
	$pattern = '/(http|https|ftp|mms):\/\/[0-9a-z-]+(\.[_0-9a-z-]+)+(:[0-9]{2,4})?\/?';
	$pattern.= '([\.~_0-9a-z-]+\/?)*';
	$pattern.= '(\S+\.[_0-9a-z]+)?';
	$pattern.= '(\?[_0-9a-z#%&=\-\+]+)*/i';
	$replacement = '<a href="\\0" target="_blank">\\0</a>';

	return preg_replace($pattern,$replacement,$text,-1);
}

/**
 * 폴더의 용량을 구한다.
 *
 * @param string $path 폴더
 * @param boolean $isKIB KiB 단위 사용여부
 * @return int $size 폴더용량
 */
function GetFolderSize($path) {
	$size = 0;
	$openDir = @opendir($path);
	while ($file = @readdir($openDir)) {
		if ($file != '.' && $file != '..' && is_dir($path.'/'.$file) == true) {
			$size+= GetFolderSize($path.'/'.$file);
		} elseif (is_file($path.'/'.$file) == true) {
			$size+= filesize($path.'/'.$file);
		}
	}
	@closedir($openDir);
	
	return $size;
}

/**
 * byte 단위의 파일용량을 적절한 단위로 변환한다.
 *
 * @param string $size byte 단위 용량
 * @param boolean $isKIB KiB 단위 사용여부
 * @return int $size 폴더용량
 */
function GetFileSize($size,$isKIB=false) {
	$depthSize = $isKIB === true ? 1024 : 1000;
	if ($size / $depthSize / $depthSize / $depthSize > 1) return sprintf('%0.2f',$size / $depthSize / $depthSize / $depthSize).($isKIB === true ? 'GiB' : 'GB');
	else if ($size / $depthSize / $depthSize > 1) return sprintf('%0.2f',$size / $depthSize / $depthSize).($isKIB === true ? 'MiB' : 'MB');
	else if ($size / $depthSize > 1) return sprintf('%0.2f',$size / $depthSize).($isKIB === true ? 'KiB' : 'KB');
	return $size.'B';
}

/**
 * Check installed module version
 *
 * @param string $dependency type of check version
 * @param string $version minimum version
 * @param object $check {boolean $installed,float $installedVersion}
 */
function CheckDependency($dependency,$version) {
	$check = new stdClass();
	
	if ($dependency == 'php') {
		$installed = explode('-',phpversion());
		$installed = array_shift($installed);
		$check->installed = version_compare($version,$installed,'<=');
		$check->installedVersion = $installed;
	} elseif ($dependency == 'mysql') {
		$installed = function_exists('mysqli_get_client_version') == true ? mysqli_get_client_version() : '0';
		$check->installed = version_compare($version,$installed,'<=');
		$check->installedVersion = $installed;
	} elseif ($dependency == 'curl') {
		$check->installed = function_exists('curl_init');
		$check->installedVersion = null;
	} elseif ($dependency == 'mcrypt_encrypt') {
		$check->installed = function_exists('mcrypt_encrypt');
		$check->installedVersion = null;
	} else {
		$check->installed = false;
		$check->installedVersion = null;
	}
	
	return $check;
}

/**
 * Check directory's permission
 *
 * @param string $dir directory name
 * @param string $permission minimum permission
 * @param boolean $check
 */
function CheckDirectoryPermission($dir,$permission) {
	if (is_dir($dir) == true) {
		$check = substr(sprintf('%o',fileperms($dir)),-4);
		for ($i=1;$i<4;$i++) {
			if (intval($check[$i]) < intval($permission[$i])) return false;
		}
		
		return true;
	}
	
	return false;
}

/**
 * Create Database from schema
 *
 * @param object $dbConnect database connector
 * @param object $schema database schema (from json)
 * @return boolean $success
 */
function CreateDatabase($dbConnect,$schema) {
	foreach ($schema as $table=>$structure) {
		if ($dbConnect->exists($table) == false) {
			if ($dbConnect->create($table,$structure) == false) return $table;
		} elseif ($dbConnect->compare($table,$structure) == false) {
			$rename = $table.'_BK'.date('YmdHis');
			if ($dbConnect->rename($table,$rename) == false) return $table;
			if ($dbConnect->create($table,$structure) == false) {
				$dbConnect->rename($rename,$table);
				return $table;
			}
			
			$data = $dbConnect->select($rename)->get();
			for ($i=0, $loop=count($data);$i<$loop;$i++) {
				$insert = array();
				foreach ($structure->columns as $column=>$type) {
					if (isset($data[$i]->$column) == true) {
						$insert[$column] = $data[$i]->$column;
					} else {
						if (isset($type->default) == true) $insert[$column] = $type->default;
						if (isset($type->value) == true) $insert[$column] = $type->value;
						if (isset($type->origin) == true && isset($data[$i]->{$type->origin}) == true) $insert[$column] = $data[$i]->{$type->origin};
					}
				}
				
				$dbConnect->insert($table,$insert)->execute();
			}
		}
		
		if (isset($structure->datas) == true && is_array($structure->datas) == true && count($structure->datas) > 0 && $dbConnect->select($table)->count() == 0) {
			for ($i=0, $loop=count($structure->datas);$i<$loop;$i++) {
				$dbConnect->insert($table,(array)$structure->datas[$i])->execute();
			}
		}
	}
	
	return true;
}

/**
 * Create folder
 *
 * @param string $path
 * @return boolean $success
 */
function CreateDirectory($path) {
	$success = true;
	$serverPath = '';
	$dir = explode('/',str_replace($_SERVER['DOCUMENT_ROOT'].'/','',$path));
	for ($i=0, $loop=count($dir);$i<$loop;$i++) {
		$serverPath.= '/'.$dir[$i];

		if (is_dir($_SERVER['DOCUMENT_ROOT'].$serverPath) == false) {
			@mkdir($_SERVER['DOCUMENT_ROOT'].$serverPath) or $success = false;
			@chmod($_SERVER['DOCUMENT_ROOT'].$serverPath,0707);
		}
	}

	return $success;
}

/**
 * 버퍼크기와 관계없이 강제로 출력된 데이터를 FLUSH한다.
 */
function ForceFlush() {
	ob_start();
	ob_end_clean();
	flush();
	set_error_handler(function() {});
	ob_end_flush();
	restore_error_handler();
}

/**
 * HEX 컬러값을 RGB 컬러값으로 변환한다.
 *
 * @param string $hex
 * @param int $opacity (옵션)
 * @return string $rgb
 */
function GetHexToRgb($hex,$opacity=null) {
	$hex = str_replace('#','',$hex);

	if (strlen($hex) == 3) {
		$r = hexdec(substr($hex,0,1).substr($hex,0,1));
		$g = hexdec(substr($hex,1,1).substr($hex,1,1));
		$b = hexdec(substr($hex,2,1).substr($hex,2,1));
	} else {
		$r = hexdec(substr($hex,0,2));
		$g = hexdec(substr($hex,2,2));
		$b = hexdec(substr($hex,4,2));
	}
	$rgb = array($r,$g,$b);
	
	if ($opacity != null) {
		return $opacity === 1 || $opacity === true ? 'rgb('.$r.','.$g.','.$b.')' : 'rgba('.$r.','.$g.','.$b.','.$opacity.')';
	} else {
		return $rgb;
	}
}

/**
 * getallheaders function is apache only
 *
 * @see http://php.net/getallheaders
 * @return $headers
 */
if (!function_exists('getallheaders')) {
	function getallheaders() { 
		$headers = array(); 
		foreach ($_SERVER as $name=>$value) {
			if (substr($name,0,5) == 'HTTP_') {
				$headers[str_replace(' ', '-',ucwords(strtolower(str_replace('_',' ',substr($name,5)))))] = $value;
			}
		}
		return $headers;
	}
}
?>