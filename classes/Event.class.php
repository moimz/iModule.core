<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * iModule 내부에서 발생하는 모든 이벤트를 관리한다.
 * 
 * @file /classes/Event.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 12. 21.
 */
class Event {
	/**
	 * iModule 코어클래스
	 */
	private $IM;
	
	/**
	 * iModule 내에서 등록된 모든 이벤트리스너 객체
	 */
	private $listeners = array();
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @see /classes/iModule.class.php
	 */
	function __construct($IM) {
		$this->IM = $IM;
	}
	
	/**
	 * 이벤트리스너를 등록시킨다.
	 *
	 * @param string $target 이벤트가 발생하는 대상 모듈명
	 * @param string $event 이벤트명
	 * @param string $caller 이벤트를 발생시킨 객체명
	 * @param string $listeners 이벤트 리스너
	 */
	function addEventListener($target,$event,$caller,$listener) {
		if (empty($this->listeners[$target]) == true) $this->listeners[$target] = array();
		if (empty($this->listeners[$target][$event]) == true) $this->listeners[$target][$event] = array();
		if (empty($this->listeners[$target][$event][$caller]) == true) $this->listeners[$target][$event][$caller] = array();
		if (in_array($listener,$this->listeners[$target][$event][$caller]) == false) $this->listeners[$target][$event][$caller][] = $listener;
	}
	
	/**
	 * 특정 이벤트리스너를 가지고 온다.
	 *
	 * @param string $event 이벤트 타입 (afterInitContext or afterDoProcess ... etc.)
	 * @param string $target 이벤트 대상 (core 또는 모듈명)
	 * @param string $caller 이벤트 지점 (보통 이벤트를 발생시킨 함수명)
	 * @param object[] $listeners
	 */
	function getEventListeners($event,$target,$caller) {
		$listeners = array();
		
		if (isset($this->listeners[$target][$event]['*']) == true) {
			for ($i=0, $loop=count($this->listeners[$target][$event]['*']);$i<$loop;$i++) {
				$listeners[] = $this->listeners[$target][$event]['*'][$i];
			}
		}
		
		if ($caller == null || empty($this->listeners[$target][$event][$caller]) == true) return $listeners;
		
		for ($i=0, $loop=count($this->listeners[$target][$event][$caller]);$i<$loop;$i++) {
			$listeners[] = $this->listeners[$target][$event][$caller][$i];
		}
		
		return $listeners;
	}
	
	/**
	 * 이벤트를 발생시킨다.
	 *
	 * @param string $event 이벤트명
	 * @param string $target 이벤트가 발생한 모듈명
	 * @param string $caller 이벤트가 발생한 객체명
	 * @param &object $values 이벤트가 발생한 시점에서 정의된 모든 변수객체
	 * @param &object $results 이벤트가 발생한 시점에서 정의된 모든 결과변수
	 * @return boolean $result 이벤트 결과, 이벤트리스너에서 false 가 반환될 경우 다음 이벤트동작이 모두 중지되며, 경우에 따라 이벤트 발생대상에서 이벤트가 발생한 시점 이후 코드실행이 중단된다. (보통 before 가 붙은 이벤트)
	 */
	function fireEvent($event,$target,$caller,&$values=null,&$results=null) {
		if (isset($this->listeners[$target][$event]['*']) == true) {
			for ($i=0, $loop=count($this->listeners[$target][$event]['*']);$i<$loop;$i++) {
				if ($this->execEvent($event,$target,$caller,$this->listeners[$target][$event]['*'][$i],$values,$results) === false) return false;
			}
		}
		
		if ($caller == null || is_string($caller) == false || isset($this->listeners[$target][$event][$caller]) == false) return true;
		
		for ($i=0, $loop=count($this->listeners[$target][$event][$caller]);$i<$loop;$i++) {
			if ($caller == '*') continue;
			if ($this->execEvent($event,$target,$caller,$this->listeners[$target][$event][$caller][$i],$values,$results) === false) return false;
		}
		
		return true;
	}
	
	/**
	 * 실제로 이벤트리스너를 호출하여 실행시킨다.
	 *
	 * @param string $event 이벤트명
	 * @param string $target 이벤트가 발생한 모듈명
	 * @param string $caller 이벤트가 발생한 객체명
	 * @param string $listener 이벤트리스너 대상
	 * @param &object $values 이벤트가 발생한 시점에서 정의된 모든 변수객체
	 * @param &object $results 이벤트가 발생한 시점에서 정의된 모든 결과변수
	 * @return boolean $result 이벤트 결과, 이벤트리스너에서 false 가 반환될 경우 다음 이벤트동작이 모두 중지되며, 경우에 따라 이벤트 발생대상에서 이벤트가 발생한 시점 이후 코드실행이 중단된다. (보통 before 가 붙은 이벤트)
	 */
	function execEvent($event,$target,$caller,$listener,&$values,&$results) {
		$IM = $this->IM;
		
		/**
		 * 이벤트리스너를 분류한다.
		 * @listenerType 이벤트리스너를 추가한 대상의 타입 (module, plugin, widget)
		 * @listenerName 이벤트리스너를 추가한 대상의 이름 (모듈명, 플러그인명, 위젯명)
		 */
		$temp = explode('/',$listener);
		$listenerType = array_shift($temp);
		$listenerName = array_shift($temp);
		
		/**
		 * 이벤트리스너를 추가한 대상이 모듈일 경우, 자기 자신의 모듈을 호출한다.
		 */
		if ($listenerType == 'module') {
			$me = $this->IM->getModule($listenerName);
		}
		
		/**
		 * 이벤트리스너를 추가한 대상이 플러그인일 경우, 해당 플러그인 객체를 호출한다.
		 */ 
		if ($listenerType == 'plugin') {
			$me = $this->IM->getPlugin($listenerName);
		}
		
		/**
		 * 이벤트리스너를 추가한 대상이 위젯인 경우, 해당 플러그인 객체를 호출한다.
		 */ 
		if ($listenerType == 'widget') {
			$me = $this->IM->getWidget($listenerName);
		}
		
		/**
		 * 이벤트가 발생한 대상의 객체를 정의한다.
		 */
		if ($target === 'core') {
			$Target = $this->IM;
		} else {
			$Target = $this->IM->getModule($target);
		}
		
		/**
		 * 이벤트 종류에 따라 이벤트 발생대상으로 부터 넘어온 변수를 적절히 변환한다.
		 */
		if ($event == 'init') {
			$init = $caller;
			unset($caller,$results);
		}
		
		/**
		 * 회원인증
		 */
		if ($event == 'authorization') {
			$type = $caller;
			$token = $values;
			unset($caller,$results,$values);
		}
		
		/**
		 * 언어팩처리
		 */
		if ($event == 'afterGetText') {
			$code = $caller;
			$string = &$values;

			unset($caller);
		}
		
		/**
		 * 문자열파싱처리
		 */
		if ($event == 'afterParseString') {
			$code = $caller;
			$string = &$values;

			unset($caller);
		}
		
		/**
		 * 주소처리
		 */
		if ($event == 'afterGetContextPage') {
			$get = $caller;
			$matches = &$results;
		}
		
		/**
		 * 데이터처리
		 */
		if ($event == 'beforeGetData' || $event == 'afterGetData') {
			$get = $caller;
			unset($caller);
		}
		
		/**
		 * 프로세스 처리
		 */
		if ($event == 'beforeDoProcess' || $event == 'afterDoProcess') {
			unset($values->action);
			unset($values->results);
			
			foreach ($values as $key=>$value) {
				if (is_object($value) == true && get_class($value) == 'Event') unset($values->$key);
			}
		
			$action = $caller;
			unset($caller);
		}
		
		/**
		 * 컨텍스트 헤더 및 푸터를 호출할때
		 */
		if ($event == 'beforeGetHeader' || $event == 'afterGetHeader' || $event == 'beforeGetFooter' || $event == 'afterGetFooter') {
			/**
			 * 호출한 컨텍스명
			 */
			$context = $values->context;
			unset($values->context);
			
			/**
			 * 사이트관리자 사이트맵 관리에서 설정된 환경설정
			 */
			$configs = $values->configs;
			unset($values->configs);
			
			if (strpos($event,'before') === 0) unset($results);
			else $html = &$results;
		}
		
		/**
		 * 컨텍스트를 호출할 때
		 */
		if ($event == 'beforeGetContext' || $event == 'afterGetContext') {
			/**
			 * 호출한 컨텍스명
			 */
			$context = $caller;
			unset($caller);
			
			/**
			 * 사이트관리자 사이트맵 관리에서 설정된 환경설정
			 */
			$configs = isset($values->configs) == true ? $values->configs : null;
			unset($values->configs);
			
			if (strpos($event,'before') === 0) unset($results);
			else $html = &$results;
		}
		
		/**
		 * 사이트내용을 출력할 때
		 */
		if ($event == 'afterDoLayout') {
			$html = &$results;
		}
		
		/**
		 * 위젯을 출력할 때
		 */
		if ($event == 'afterDoWidgetLayout') {
			$widget = $caller;
			$templet = $values->templet;
			$html = &$results;
			unset($caller);
			unset($values);
		}
		
		/**
		 * 모듈에 포함된 컨텍스트 목록을 불러올 때
		 */
		if ($event == 'afterGetContextList') {
			$action = $caller;
			$site = $values;
			unset($caller);
			unset($values);
		}
		
		/**
		 * 모듈에 포함된 컨텍스트의 설정항목을 불러올 때
		 */
		if ($event == 'afterGetContextConfigs') {
			$context = $caller;
			$site = $values;
			$configs = $results;
			unset($caller);
			unset($values);
			unset($results);
		}
		
		/**
		 * API 가 호출될 때
		 */
		if ($event == 'beforeGetApi' || $event == 'afterGetApi') {
			$api = $caller;
			$data = $results;
			unset($caller,$results);
		}
		
		/**
		 * 관리자 패널을 가져올 때
		 */
		if ($event == 'afterGetAdminPanel') {
			$panel = &$results;
			unset($values,$results,$caller);
		}
		
		/**
		 * 권한을 확인할 때
		 */
		if ($event == 'checkPermission' || $event == 'checkProcessPermission') {
			$action = $values;
			$permission = &$results;
		}
		
		/**
		 * 이벤트리스너가 정의된 파일의 경로를 정의한다.
		 */
		$listenerPath = '';
		if ($listenerType == 'module') {
			$listenerPath = __IM_PATH__.'/modules/'.$listenerName.'/events/'.$event.'.php';
		} elseif ($listenerType == 'plugin') {
			$listenerPath = __IM_PATH__.'/plugins/'.$listenerName.'/events/'.$event.'.php';
		}
		
		if ($listenerPath != '' && is_file($listenerPath) == true) {
			$returnValue = INCLUDE $listenerPath;
			
			if ($returnValue === false) return false;
			return true;
		} else {
			return null;
		}
	}
}
?>