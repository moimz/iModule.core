<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 위젯코어 클래스는 서버에 설치된 모든 위젯을 관리하고 화면상에 출력한다.
 * 이 클래스는 모든 위젯의 index.php 파일이나, 위젯 템플릿에서 $Widget 변수로 접근할 수 있다.
 * 
 * @file /classes/Widget.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160831
 */
class Widget {
	/**
	 * iModule 코어클래스 및 위젯을 호출한 모듈클래스
	 */
	private $IM;
	private $caller = null;
	
	/**
	 * 언어셋을 정의한다.
	 * 
	 * @private object $lang 현재 사이트주소에서 설정된 언어셋
	 * @private object $oLang package.json 에 의해 정의된 기본 언어셋
	 */
	private $lang = null;
	private $oLang = null;
	
	/**
	 * 각 위젯에서 이 클래스를 호출하였을 경우 사용되는 위젯정보
	 *
	 * @private string $widgetPath 위젯 절대경로
	 * @private string $widgetDir 위젯 상대경로
	 * @private string $widgetPackage 위젯 package.json 정보
	 * @private object $templet 위젯 템플릿 정보 (false 일 경우 템플릿이 존재하지 않음)
	 * @private object $values 위젯에서 사용되는 변수정보
	 * @private string[] $attributes 위젯 컨테이터에 사용되는 변수정보
	 */
	private $widgetPath;
	private $widgetDir;
	private $widgetPackage = null;
	private $templet = null;
	private $values = null;
	private $attributes = array();
	
	/**
	 * 호출된 위젯이 있을 경우 해당 위젯명
	 */
	private $loaded = false;
	
	/**
	 * 위젯에서 사용할 랜덤 ID
	 */
	private $randomId = null;
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @see /classes/iModule.class.php
	 */
	function __construct($IM) {
		$this->IM = $IM;
		$this->values = new stdClass();
	}
	
	/**
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @param string $replacement 일치하는 언어코드가 없을 경우 반환될 메세지 (기본값 : null, $code 반환)
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getText($code,$replacement=null) {
		/**
		 * 위젯이 호출된 상태가 아니라면 iModule 코어의 getText 함수를 이용한다.
		 */
		if ($this->loaded == false) return $this->IM->getText($code,$replacement=null);
		
		if ($this->lang == null) {
			if (is_file($this->getPath().'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getPath().'/languages/'.$this->IM->language.'.json'));
				if ($this->IM->language != $this->getPackage()->language && is_file($this->getPath().'/languages/'.$this->getPackage()->language.'.json') == true) {
					$this->oLang = json_decode(file_get_contents($this->getPath().'/languages/'.$this->getPackage()->language.'.json'));
				}
			} elseif (is_file($this->getPath().'/languages/'.$this->getPackage()->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->getPath().'/languages/'.$this->getPackage()->language.'.json'));
				$this->oLang = null;
			}
		}
		
		$returnString = null;
		$temp = explode('/',$code);
		
		$string = $this->lang;
		for ($i=0, $loop=count($temp);$i<$loop;$i++) {
			if (isset($string->{$temp[$i]}) == true) {
				$string = $string->{$temp[$i]};
			} else {
				$string = null;
				break;
			}
		}
		
		if ($string != null) {
			$returnString = $string;
		} elseif ($this->oLang != null) {
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) {
						$string = $string->{$temp[$i]};
					} else {
						$string = null;
						break;
					}
				}
			}
			
			if ($string != null) $returnString = $string;
		}
		
		/**
		 * 언어셋 텍스트가 없을경우 모듈클래스나 iModule 코어에서 불러온다.
		 */
		if ($returnString != null) return $returnString;
		elseif ($this->caller != null) return $this->caller->getText($code,$replacement);
		elseif (in_array(reset($temp),array('text','button','action')) == true) return $this->IM->getText($code,$replacement);
		else return $replacement == null ? $code : $replacement;
	}
	
	/**
	 * [코어] 위젯을 불러온다.
	 *
	 * @param string $widget 위젯명
	 * @return Widget $Widget 위젯클래스
	 */
	function load($widget) {
		/**
		 * 위젯에 점(.)이 있을 경우 특정 모듈에 종속되는 위젯이며, 없을 경우 코어에서 호출가능한 위젯이다.
		 */
		$temp = explode('.',$widget);
		if (count($temp) == 1) {
			$this->widgetPath = __IM_PATH__.'/widgets/'.$widget;
			$this->widgetDir = __IM_DIR__.'/widgets/'.$widget;
		} else {
			$this->widgetPath = __IM_PATH__.'/modules/'.$temp[0].'/widgets/'.$temp[1];
			$this->widgetDir = __IM_DIR__.'/modules/'.$temp[0].'/widgets/'.$temp[1];
			$this->caller = $this->IM->getModule($temp[0]);
		}
		
		$this->loaded = $widget;
		
		/**
		 * 위젯의 package.json 파일이 없을 경우
		 */
		if (is_file($this->getPath().'/package.json') == false) return $this;
		$this->widgetPackage = json_decode(file_get_contents($this->getPath().'/package.json'));
		
		return $this;
	}
	
	/**
	 * 모듈에 종속되어 있는 위젯의 경우, 종속되어 있는 모듈 클래스를 반환한다.
	 *
	 * @return string $name 모듈명
	 */
	function getClass() {
		return $this->caller;
	}
	
	/**
	 * 현재 위젯명을 반환한다.
	 *
	 * @return string $name 위젯명
	 */
	function getName() {
		if ($this->loaded === false) return null;
		return $this->loaded;
	}
	
	/**
	 * 위젯의 절대경로를 반환한다.
	 */
	function getPath() {
		return $this->widgetPath;
	}
	
	/**
	 * 위젯의 상대경로를 반환한다.
	 */
	function getDir() {
		return $this->widgetDir;
	}
	
	/**
	 * 에러메세지를 반환한다.
	 *
	 * @param string $code 에러코드 (에러코드는 iModule 코어에 의해 해석된다.)
	 * @param object $value 에러코드에 따른 에러값
	 * @return $html 에러메세지 HTML
	 */
	function getError($code,$value=null) {
		/**
		 * iModule 코어를 통해 에러메세지를 구성한다.
		 */
		$error = $this->getErrorText($code,$value,true);
		return $this->IM->getError($error);
	}
	
	/**
	 * 상황에 맞게 에러코드를 반환한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param boolean $isRawData(옵션) RAW 데이터 반환여부
	 * @return string $message 에러 메세지
	 */
	function getErrorText($code,$value=null,$isRawData=false) {
		$message = $this->getText('error/'.$code,$code);
		if ($message == $code) return $this->IM->getErrorText($code,$value,null,$isRawData);
		
		$description = null;
		switch ($code) {
			default :
				if (is_object($value) == false && $value) $description = $value;
		}
		
		$error = new stdClass();
		$error->message = $message;
		$error->description = $description;
		
		if ($isRawData === true) return $error;
		else return $this->IM->getErrorText($error);
	}
	
	/**
	 * 위젯내부에서 사용할 랜덤 ID 값을 생성한다.
	 * 이미 생성된 랜덤 ID가 있을 경우 해당 값을 반환한다.
	 */
	function getRandomId() {
		if ($this->randomId == null) {
			$widget = ucfirst(preg_replace_callback('/\.([a-z]{1})/',create_function('$match','return strtoupper($match[1]);'),$this->loaded));
			$this->randomId = 'Widget'.ucfirst($widget).'-'.GetRandomString(10);
		}
		return $this->randomId;
	}
	
	/**
	 * 위젯의 package.json 정보를 가져온다.
	 *
	 * @return object $package package.json 정보
	 */
	function getPackage() {
		return $this->widgetPackage;
	}
	
	/**
	 * 위젯의 템플릿을 가져온다.
	 *
	 * @return Templet $templet 템플릿 클래스
	 */
	function getTemplet() {
		return $this->templet;
	}
	
	/**
	 * 위젯의 템플릿명을 설정한다.
	 *
	 * @param string $templet 템플릿명
	 * @return Widget $this
	 */
	function setTemplet($templet) {
		$this->templet = $this->IM->getTemplet($this,$templet);
		return $this;
	}
	
	/**
	 * 위젯의 설정값을 반환한다.
	 *
	 * @param string $key 설정값 이름
	 * @return object $value 설정값
	 */
	function getValue($key) {
		if (isset($this->values->$key) == true) return $this->values->$key;
		else return null;
	}
	
	/**
	 * 위젯의 설정값을 입력한다.
	 *
	 * @param string $key 설정값 이름
	 * @param object $value 설정값
	 * @return Widget $this
	 */
	function setValue($key,$value) {
		$this->values->$key = $value;
		return $this;
	}
	
	/**
	 * 위젯 컨테이너에 attribute 를 추가한다.
	 *
	 * @param string $name
	 * @param string $value
	 * @return Widget $this
	 */
	function setAttribute($name,$value) {
		$this->attributes[$name] = $value;
		return $this;
	}
	
	/**
	 * 위젯의 캐시 생성시간을 확인한다.
	 *
	 * @return int $unixtime 위젯 캐시가 생성된 시간
	 */
	function checkCache() {
		return $this->IM->cache()->check('widget',$this->loaded,sha1(json_encode($this->values)));
	}
	
	/**
	 * 위젯의 캐시데이터를 저장한다.
	 *
	 * @param string $data 저장할 데이터
	 */
	function storeCache($data) {
		$this->IM->cache()->store('widget',$this->loaded,sha1(json_encode($this->values)),$data);
	}
	
	/**
	 * 위젯의 캐시데이터를 가져온다.
	 *
	 * @return string $data 캐시된 데이터
	 */
	function getCache() {
		return $this->IM->cache()->get('widget',$this->loaded,sha1(json_encode($this->values)));
	}
	
	/**
	 * 위젯 컨텍스트를 가져온다.
	 *
	 * @reutn string $html 컨텍스트 HTML
	 */
	function getContext() {
		/**
		 * 위젯파일에서 iModule 코어클래스 및 모듈클래스, 템플릿 클래스에 접근하기 위한 변수 선언
		 */
		$IM = $this->IM;
		
		if ($this->getClass() !== null) {
			$me = $this->getClass();
			$Module = $me->getModule();
		}
		
		$Widget = $this;
		$Templet = $this->getTemplet();
		
		/**
		 * 위젯 템플릿의 기본 자바스크립트 및 스타일시트가 있을 경우 불러온다.
		 */
		$package = $Templet->getPackage();
		
		if (isset($package->styles) == true && is_array($package->styles) == true) {
			foreach ($package->styles as $style) {
				$style = preg_match('/^(http:\/\/|https:\/\/|\/\/)/',$style) == true ? $style : $Templet->getDir().$style;
				$this->IM->addHeadResource('style',$style);
			}
		}
		
		if (isset($package->scripts) == true && is_array($package->scripts) == true) {
			foreach ($package->scripts as $script) {
				$script = preg_match('/^(http:\/\/|https:\/\/|\/\/)/',$script) == true ? $script : $Templet->getDir().$script;
				$this->IM->addHeadResource('script',$script);
			}
		}
		
		return INCLUDE $this->getPath().'/index.php';
	}
	
	function doLayout() {
		/**
		 * 위젯이 로드되지 않았을 경우 에러메세지를 출력한다.
		 */
		if ($this->loaded == false) {
			echo $this->IM->getError('REQUIRED_WIDGET_NAME');
			return;
		}
		
		/**
		 * 위젯의 package.json 파일이 없을 경우
		 */
		if ($this->getPackage() == null) {
			echo $this->IM->getError('NOT_FOUND_WIDGET',$this->getPath().'/package.json');
			return;
		}
		
		/**
		 * 위젯 템플릿이 설정되지 않았을 경우
		 */
		if ($this->getTemplet() === null) {
			echo $this->IM->getError('REQUIRED_WIDGET_TEMPLET');
			return;
		}
		
		/**
		 * 위젯 템플릿을 불러올 수 없는 경우
		 */
		if ($this->getTemplet()->isLoaded() === false) {
			echo $this->getError('NOT_FOUND_WIDGET_TEMPLET',$this->getTemplet()->getPath());
			return;
		}
		
		/**
		 * 위젯의 필수값이 없을 경우
		 */
		if (isset($this->getPackage()->configs) == true) {
			foreach ($this->getPackage()->configs as $key=>$data) {
				if (isset($data->is_required) == true && $data->is_required === true && isset($this->values->$key) == false) {
					echo $this->IM->getError('REQUIRED_WIDGET_VALUE',$key);
					return;
				}
				
				if (isset($data->default) == true && isset($this->values->$key) == false) $this->values->$key = $data->default;
			}
		}
		
		/**
		 * 위젯파일이 없을 경우 에러메세지를 출력한다.
		 */
		if (is_file($this->getPath().'/index.php') == false) {
			$widget = $this->getError('NOT_FOUND_WIDGET_FILE',$this->getPath().'/index.php');
		} else {
			$widget = $this->getContext();
		}
		
		
		$html = PHP_EOL.'<!-- WIDGET : '.$this->loaded.' -->'.PHP_EOL;
		$html.= '<div data-role="widget" data-widget="'.str_replace('.','-',$this->loaded).'" data-templet="'.$this->getTemplet()->getName(true).'"'.$this->getTemplet()->getContainerName();
		foreach ($this->attributes as $key=>$value) $html.= ' '.$key.'='.$value;
		$html.= '>'.PHP_EOL;
		
		$html.= $widget;
		
		$html.= PHP_EOL.'</div>'.PHP_EOL;
		$html.= '<!--// WIDGET : '.$this->loaded.' -->'.PHP_EOL;
		
		echo $html;
	}
}
?>