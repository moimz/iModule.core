<?php
/**
 * 이 파일은 MoimzTools 의 일부입니다. (https://www.moimz.com)
 *
 * 웹 컨텐츠를 크롤링하기 위한 클래스를 정의한다.
 *
 * @file /classes/Crawler.class.php
 * @author Arzz
 * @license MIT License
 * @version 1.0.0
 * @modified 2018. 4. 18.
 */
class Crawler {
	/**
	 * 기본 크롤링 속성
	 *
	 * @private string $agent 사용자 에이전트
	 * @private string $referer HTTP_REFERER 주소
	 * @private string $cookie 쿠키데이터
	 * @private int $timeout 타임아웃(초)
	 */
	private $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.117 Safari/537.36';
	private $referer = null;
	private $cookie = null;
	private $timeout = 30;
	
	/**
	 * HTTP_REFERER 를 설정한다.
	 *
	 * @param string $referer
	 * @return Crawler $this
	 */
	function setReferer($referer) {
		$this->referer = $referer;
		return $this;
	}
	
	/**
	 * URL 의 컨텐츠를 가져온다. (GET 방식)
	 *
	 * @param string $url 컨텐츠를 가져올 URL 주소
	 * @return string $content 컨텐츠
	 */
	function getUrl($url) {
		$ch = curl_init();
//		if (empty($_SERVER['HTTP_HOST'])) echo $this->cookie;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,false);
		curl_setopt($ch,CURLOPT_USERAGENT,$this->agent);
		if ($this->referer != null) curl_setopt($ch,CURLOPT_REFERER,$this->referer);
		curl_setopt($ch,CURLOPT_TIMEOUT,$this->timeout);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		if (is_file($this->cookie) == true) {
			curl_setopt($ch,CURLOPT_COOKIEFILE,$this->cookie);
		}
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$buffer = curl_exec($ch);
		$cinfo = curl_getinfo($ch);
		curl_close($ch);
		
		if ($cinfo['http_code'] != 200) return null;
		else return $this->getUTF8($buffer);
	}
	
	/**
	 * URL 의 컨텐츠를 가져온다. (POST 방식)
	 *
	 * @param string $url 컨텐츠를 가져올 URL 주소
	 * @return string $content 컨텐츠
	 */
	function postUrl($url,$data=array()) {
		$ch = curl_init();
//		if (empty($_SERVER['HTTP_HOST'])) echo $this->cookie;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($data));
		curl_setopt($ch,CURLOPT_USERAGENT,$this->agent);
		if ($this->referer != null) curl_setopt($ch,CURLOPT_REFERER,$this->referer);
		curl_setopt($ch,CURLOPT_TIMEOUT,$this->timeout);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		if (is_file($this->cookie) == true) {
			curl_setopt($ch,CURLOPT_COOKIEFILE,$this->cookie);
		}
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$buffer = curl_exec($ch);
		$cinfo = curl_getinfo($ch);
		curl_close($ch);
		
		if ($cinfo['http_code'] != 200) return null;
		else return $this->getUTF8($buffer);
	}
	
	/**
	 * URL 경로상의 내용을 파일로 저장한다.
	 *
	 * @param string $url 컨텐츠를 가져올 URL 주소
	 * @param string $path 저장될 파일 경로
	 * @return boolean $success
	 */
	function saveFile($url,$path) {
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_POST,false);
		curl_setopt($ch,CURLOPT_USERAGENT,$this->agent);
		if ($this->referer != null) curl_setopt($ch,CURLOPT_REFERER,$this->referer);
		curl_setopt($ch,CURLOPT_TIMEOUT,$this->timeout);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
		if (is_file($this->cookie) == true) {
			curl_setopt($ch,CURLOPT_COOKIEFILE,$this->cookie);
		}
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$buffer = curl_exec($ch);
		$cinfo = curl_getinfo($ch);
		curl_close($ch);
		
		if ($cinfo['http_code'] != 200 || preg_match('/text\/html/',$cinfo['content_type']) == true) {
			return false;
		}

		$filepath = $path;
		$success = @file_put_contents($path,$buffer);
		
		if ($success === false) return false;
		
		if (is_file($path) == false || filesize($path) == 0) {
			@unlink($path);
			return false;
		}

		return true;
	}
	
	/**
	 * 웹페이지 컨텐츠의 캐릭터셋을 UTF-8로 변경한다.
	 *
	 * @param string $origin 원본 컨텐츠
	 * @return string $utf8 UTF-8 컨텐츠
	 */
	function getUTF8($origin) {
		/**
		 * 메타태그를 찾아 원본 컨텐츠의 캐릭터셋을 파악한다.
		 */
		if (preg_match('/<meta(.*?)charset=("|\')?(.*?)("|\')(.*?)>/i',$origin,$match) == true) {
			if (strpos(strtoupper($match[3]),'UTF') === 0) return $origin;
			
			$originEncode = strtoupper($match[3]);
		} else {
			/**
			 * 메타태그에서 캐릭터셋을 파악하지 못하였을 경우
			 */
			if (function_exists('mb_detect_encoding') == false) return $origin;
			
			$originEncode = mb_detect_encoding($origin,'EUC-KR,UTF-8,ASCII,EUC-JP,CP949,AUTO');
		}
		
		if ($originEncode == 'UTF-8' || $originEncode == '') return $origin;
		else return @iconv($originEncode,'UTF-8//IGNORE',$origin);
	}
}
?>