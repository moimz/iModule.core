<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 위젯코어 클래스는 서버에 설치된 모든 위젯을 관리하고 화면상에 출력한다.
 * 이 클래스는 모든 위젯의 widget.php 파일이나, 위젯 템플릿에서 $Widget 변수로 접근할 수 있다.
 * 
 * @file /classes/Widget.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160831
 */
class Widget {
	private $IM;
	private $Module;
	
	private $lang = null;
	private $module = null;
	private $widget = null;
	
	private $templetFile;
	private $templetPath;
	private $templetDir;
	private $widgetPath;
	private $widgetDir;
	private $widgetInfo;
	private $values = array();
	
	function __construct($IM) {
		$this->IM = $IM;
		$this->Module = null;
		$this->templet = null;
	}
	
	function load($widget) {
		$this->templetFile = 'templet.php';
		
		$temp = explode('/',$widget);
		if (sizeof($temp) == 1) {
			$this->widget = $widget;
			$this->widgetPath = __IM_PATH__.'/widgets/'.$this->widget;
			$this->widgetDir = __IM_DIR__.'/widgets/'.$this->widget;
		} else {
			$this->module = $temp[0];
			$this->widget = $temp[1];
			$this->widgetPath = __IM_PATH__.'/modules/'.$this->module.'/widgets/'.$this->widget;
			$this->widgetDir = __IM_DIR__.'/modules/'.$this->module.'/widgets/'.$this->widget;
			$this->Module = $this->IM->getModule($this->module);
		}
		
		$this->widgetInfo = json_decode(file_get_contents($this->widgetPath.'/'.$this->widget.'.json'));
		
		return $this;
	}
	
	function setTemplet($templet) {
		if (preg_match('/^@/',$templet) == true) {
			$this->templetPath = $this->IM->getTempletPath().'/templets'.($this->module != null ? '/modules/'.$this->module.'/widgets' : '/widgets').'/'.$this->widget.'/'.preg_replace('/^@/','',$templet);
			$this->templetDir = $this->IM->getTempletDir().'/templets'.($this->module != null ? '/modules/'.$this->module.'/widgets' : '/widgets').'/'.$this->widget.'/'.preg_replace('/^@/','',$templet);
		} elseif (preg_match('/\.php$/',$templet) == true) {
			$temp = explode('/',$templet);
			$this->templetFile = array_pop($temp);
			$this->templetPath = implode('/',$temp);
			$this->templetDir = str_replace(__IM_PATH__,__IM_DIR__,$this->templetPath);
		} else {
			$this->templetPath = $this->widgetPath.'/templets/'.$templet;
			$this->templetDir = $this->widgetDir.'/templets/'.$templet;
		}
		
		return $this;
	}
	
	function getLanguage($code) {
		if (is_dir($this->widgetPath.'/languages') == false) return null;
		
		if ($this->lang == null) {
			if (file_exists($this->widgetPath.'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->widgetPath.'/languages/'.$this->IM->language.'.json'));
			} else {
				$this->lang = json_decode(file_get_contents($this->widgetPath.'/languages/'.$this->getInfo()->languages[0].'.json'));
			}
		}
		
		$temp = explode('/',$code);
		if (sizeof($temp) == 1) {
			return isset($this->lang->$code) == true ? $this->lang->$code : '';
		} else {
			$string = $this->lang;
			for ($i=0, $loop=sizeof($temp);$i<$loop;$i++) {
				if (isset($string->$temp[$i]) == true) $string = $string->$temp[$i];
				else return '';
			}
			return $string;
		}
	}
	
	function getInfo() {
		return $this->widgetInfo;
	}
	
	function getTempletPath() {
		return $this->templetPath;
	}
	
	function getTempletDir() {
		return $this->templetDir;
	}
	
	function getTempletFile() {
		return $this->templetPath.'/'.$this->templetFile;
	}
	
	function getPath() {
		return $this->widgetPath;
	}
	
	function getDir() {
		return $this->widgetDir;
	}
	
	function cacheCheck() {
		return $this->IM->cache()->check('widget',$this->widget,sha1(json_encode($this->values)));
	}
	
	function cacheStore($data) {
		$this->IM->cache()->store('widget',$this->widget,sha1(json_encode($this->values)),$data);
	}
	
	function cache() {
		return $this->IM->cache()->get('widget',$this->widget,sha1(json_encode($this->values)));
	}
	
	function setValue($name,$value) {
		$this->values[$name] = $value;
		
		return $this;
	}
	
	function getValue($name) {
		return empty($this->values[$name]) ? null : $this->values[$name];
	}
	
	function doLayout() {
		$IM = $this->IM;
		$Widget = $this;
		$Module = $this->Module;
		
		$values = new stdClass();
		INCLUDE $this->widgetPath.'/widget.php';
	}
}
?>