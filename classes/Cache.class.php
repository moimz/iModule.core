<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * 사이트내에서 생성되는 캐시파일을 관리하는 클래스를 정의한다.
 * 
 * @file /classes/Cache.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 2. 27.
 */
class Cache {
	/**
	 * iModule 코어클래스
	 */
	private $IM;
	
	/**
	 * @private string $cachePath 캐시파일 경로
	 * @private boolean $enabled 캐시활성화 여부
	 */
	private $cachePath;
	private $enabled;
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 */
	function __construct($IM) {
		global $_CONFIGS;
		
		/**
		 * iModule 코어 선언
		 */
		$this->IM = $IM;
		
		/**
		 * 캐시파일 경로 및 캐시활성화 여부 정의
		 */
		$this->cachePath = $IM->getCachePath();
		$this->enabled = isset($_CONFIGS->enableCache) == false || $_CONFIGS->enableCache === true;
		
		/**
		 * 캐시파일 경로 확인 및 폴더 생성
		 */
		if (is_dir($this->cachePath) == false) {
			mkdir($this->cachePath);
			chmod($this->cachePath,0707);
		}
	}
	
	/**
	 * 캐시파일의 경로를 구한다.
	 *
	 * @param string $controller 캐시파일을 생성한 컨트롤러 (module, widget, plugin, core)
	 * @param string $component 캐시파일을 생성한 컴포넌트
	 * @param string $code 캐시파일 고유코드
	 * @return string $path 캐시파일경로
	 */
	function path($controller,$component,$code) {
		$path = $this->cachePath.'/'.$controller.'.'.$component.'.'.$code;
		
		/**
		 * 전체사이트에 적용되는 캐시의 경우에는 도메인과 언어코드를 추가하지 않는다.
		 */
		if ($controller == 'core' && in_array($component,array('event','module','plugin','site','sitemap')) == true && $code == 'all') {
			$path.= '.union';
		} else {
			$path.= '.'.$this->IM->domain.'.'.$this->IM->language;
		}
		$path.= '.cache';
		
		return $path;
	}
	
	/**
	 * 캐시파일 생성시각을 확인한다.
	 *
	 * @param string $controller 캐시파일을 생성한 컨트롤러 (module, widget, plugin, core)
	 * @param string $component 캐시파일을 생성한 컴포넌트
	 * @param string $code 캐시파일 고유코드
	 * @return int $time 캐시파일 생성시각(UNIXTIMESTAMP, 0 일 경우 캐시파일이 존재하지 않음)
	 */
	function check($controller,$component,$code) {
		if ($this->enabled === false || !$this->IM->domain || !$this->IM->language) return 0;
		
		if (is_file($this->path($controller,$component,$code)) == true) {
			return filemtime($this->path($controller,$component,$code));
		} else {
			return 0;
		}
	}
	
	/**
	 * 캐시파일 내용을 가져온다.
	 *
	 * @param string $controller 캐시파일을 생성한 컨트롤러 (module, widget, plugin, core)
	 * @param string $component 캐시파일을 생성한 컴포넌트
	 * @param string $code 캐시파일 고유코드
	 * @return string $data 캐시데이터
	 */
	function get($controller,$component,$code) {
		if ($this->enabled === false || !$this->IM->domain || !$this->IM->language) return null;
		
		if (is_file($this->path($controller,$component,$code)) == true) {
			return file_get_contents($this->path($controller,$component,$code));
		} else {
			return null;
		}
	}
	
	/**
	 * 캐시파일 내용을 업데이트한다.
	 *
	 * @param string $controller 캐시파일을 생성한 컨트롤러 (module, widget, plugin, core)
	 * @param string $component 캐시파일을 생성한 컴포넌트
	 * @param string $code 캐시파일 고유코드
	 * @param string $data 캐시데이터
	 * @return boolean $success
	 */
	function store($controller,$component,$code,$data) {
		if ($this->enabled === false || !$this->IM->domain || !$this->IM->language) return false;
		
		return file_put_contents($this->path($controller,$component,$code),$data);
	}
	
	/**
	 * 캐시파일 내용을 초기화한다.
	 *
	 * @param string $controller 캐시파일을 생성한 컨트롤러 (module, widget, plugin, core)
	 * @param string $component 캐시파일을 생성한 컴포넌트
	 * @param string $code 캐시파일 고유코드
	 * @return boolean $success
	 */
	function reset($controller,$component,$code) {
		if ($this->enabled === false || !$this->IM->domain || !$this->IM->language) return false;
		
		if (is_file($this->path($controller,$component,$code)) == true) unlink($this->path($controller,$component,$code));
	}
}
?>