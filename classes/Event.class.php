<?php
class Event {
	private $IM;
	private $targets = array();
	
	function __construct($IM) {
		$this->IM = $IM;
	}
	
	function addTarget($target,$event,$caller,$listener) {
		if (empty($this->targets[$target]) == true) $this->targets[$target] = array();
		if (empty($this->targets[$target][$event]) == true) $this->targets[$target][$event] = array();
		if (empty($this->targets[$target][$event][$caller]) == true) $this->targets[$target][$event][$caller] = array();
		if (in_array($listener,$this->targets[$target][$event][$caller]) == false) $this->targets[$target][$event][$caller][] = $listener;
	}
	
	function fireEvent($event,$target,$caller,&$values=null,&$results=null,&$context=null) {
		if (isset($this->targets[$target][$event]['*']) == true) {
			for ($i=0, $loop=count($this->targets[$target][$event]['*']);$i<$loop;$i++) {
				$this->execEvent($event,$target,$caller,$this->targets[$target][$event]['*'][$i],$values,$results,$context);
			}
			return;
		}
		
		if ($caller == null || empty($this->targets[$target][$event][$caller]) == true) return;
		for ($i=0, $loop=count($this->targets[$target][$event][$caller]);$i<$loop;$i++) {
			$this->execEvent($event,$target,$caller,$this->targets[$target][$event][$caller][$i],$values,$results,$context);
		}
	}
	
	function execEvent($event,$target,$caller,$listener,&$values,&$results,&$context) {
		$IM = $this->IM;
		
		$temp = explode('/',$listener);
		$listenerType = array_shift($temp);
		$listenerName = array_shift($temp);
		
		if ($listenerType == 'addon') {
			$Addon = new Addon($listenerName);
		} elseif ($listenerType == 'module') {
			$callerModule = $this->IM->getModule($listenerName);
		}
		
		if ($target !== 'core') $Module = $this->IM->getModule($target);
		else $Module = $IM;
		
		if ($event == 'init') {
			$init = $caller;
			unset($caller,$context,$results);
		}
		
		if ($event == 'beforeDoProcess' || $event == 'afterDoProcess') {
			$action = $caller;
			unset($caller,$context);
		}
		
		if ($event == 'beforeGetData' || $event == 'afterGetData') {
			$get = $caller;
			unset($caller,$results,$context);
		}
		
		if ($event == 'afterDoLayout') {
			$html = &$context;
			unset($results,$context);
		}
		
		if ($event == 'afterInitContext') {
			$view = $caller;
			unset($caller);
		}
		
		if ($event == 'beforeGetContext' || $event == 'beforeGetContainer') {
			$container = $caller;
			$configs = $values;
			unset($context);
			unset($caller);
			unset($values);
		}
		
		if ($event == 'afterGetContext' || $event == 'afterGetContainer') {
			$container = $caller;
			$configs = $values;
			unset($caller);
			unset($values);
		}
		
		if ($event == 'afterGetContextList') {
			$action = $caller;
			$site = $values;
			unset($caller);
			unset($values);
		}
		
		if ($event == 'afterGetContextConfigs') {
			$context = $caller;
			$site = $values;
			$configs = $results;
			unset($caller);
			unset($values);
			unset($results);
		}
		
		if ($event == 'beforeGetApi' || $event == 'afterGetApi') {
			$api = $caller;
			$data = $results;
			unset($caller,$results);
		}
		
		if ($event == 'afterGetAdminPanel') {
			unset($values,$results,$caller);
			$panel = &$context;
		}
		
		$listenerPath = '';
		if ($listenerType == 'addon') {
			$listenerPath = __IM_PATH__.'/addons/'.$listenerName.'/'.$event.'.php';
		} else {
			$listenerPath = __IM_PATH__.'/modules/'.$listenerName.'/events/'.$event.'.php';
		}
		
		if ($listenerPath != '' && file_exists($listenerPath) == true) {
			INCLUDE $listenerPath;
		}
	}
}
?>