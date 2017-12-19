<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 모든 플러그인을 관리한다.
 * 
 * @file /classes/Plugin.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160907
 */
class Plugin {
	private $IM;
	private $plugins = array();
	private $table;
	private $pluginPath;
	private $pluginDir;
	private $pluginPackage;
	private $pluginConfigs = null;
	private $pluginInstalled = null;
	private $loadedPackage = array();
	
	function __construct($IM) {
		$this->table = new stdClass();
		$this->table->plugin = 'plugin_table';
		return;
		if (is_object($IM) == true) {
			$this->IM = $IM;
			
			/**
			 * 설치된 에드온에서 사용하는 이벤트리스너를 모두 Event 클래스에 등록한다.
			 * DB접근을 줄이기 위하여 60초동안 모든 모듈에 대한 이벤트 리스너를 캐싱한다.
			 */
			if ($this->IM->cache()->check('core','plugins','all') > time() - 60) {
				$plugins = json_decode($this->IM->cache()->get('core','plugins','all'));
			} else {
				$plugins = $this->db()->select($this->table->plugin)->where('active','TRUE')->get();
				$this->IM->cache()->store('core','plugins','all',json_encode($plugins));
			}
			
			for ($i=0, $loop=sizeof($plugins);$i<$loop;$i++) {
				$targets = $plugins[$i]->targets ? json_decode($plugins[$i]->targets) : new stdClass();
				
				foreach ($targets as $target=>$events) {
					foreach ($events as $event=>$callers) {
						foreach ($callers as $caller) {
							$this->IM->Event->addEventListener($target,$event,$caller,'plugin/'.$plugins[$i]->plugin);
						}
					}
				}
			}
		} else {
			$this->IM = null;
			$this->pluginPath = __IM_PATH__.'/plugins/'.$IM;
			$this->pluginDir = __IM_DIR__.'/plugins/'.$IM;
			$this->pluginPackage = $this->getPackage($IM);
		
			// check installed
			$this->pluginInstalled = $this->db()->select($this->table->plugin)->where('plugin',$IM)->getOne();
			if ($this->pluginInstalled == null) return false;
			else $this->pluginConfigs = json_decode($this->pluginInstalled->configs);
		}
	}
	
	/**
	 * Get database for this Module
	 *
	 * @param string $db db's code
	 * @return object DB.class
	 */
	function db($db='default') {
		if ($this->IM == null) {
			$IM = new iModule();
			return $IM->db($db);
		} else {
			return $this->IM->db($db);
		}
	}
	
	/**
	 * Get language string from language code
	 *
	 * @param string $code language code (json key)
	 * @return string language string
	 */
	function getLanguage($code) {
		if ($this->IM !== null) return null;
		
		if (is_dir($this->pluginPath.'/languages') == false) return null;
		
		if ($this->lang == null) {
			if (file_exists($this->pluginPath.'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->pluginPath.'/languages/'.$this->IM->language.'.json'));
			} else {
				$this->lang = json_decode(file_get_contents($this->pluginPath.'/languages/'.$this->getPackage()->language.'.json'));
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
	
	/**
	 * Get plugin hash for plugin modified check
	 *
	 * @param string $plugin plugin's name
	 * @return string $hash plugin's hash
	 */
	function getHash($plugin) {
		return file_exists(__IM_PATH__.'/plugins/'.$plugin.'/package.json') == true ? md5_file(__IM_PATH__.'/plugins/'.$plugin.'/package.json') : false;
	}
	
	/**
	 * Get plugin info from package.json file
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return object $info
	 */
	function getPackage($plugin=null) {
		if ($plugin !== null) {
			if (isset($this->loadedPackage[$plugin]) == true) return $this->loadedPackage[$plugin];
			if (file_exists(__IM_PATH__.'/plugins/'.$plugin.'/package.json') == false) return null;
			$this->loadedPackage[$plugin] = json_decode(file_get_contents(__IM_PATH__.'/plugins/'.$plugin.'/package.json'));
			return $this->loadedPackage[$plugin];
		} else {
			return $this->pluginPackage;
		}
	}
	
	/**
	 * Get plugin's event listsners from package.json file
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return object[] $eventListeners plugin's event listeners
	 */
	function getTarget($plugin=null) {
		$package = $this->getPackage($plugin);
		return $package == null || empty($package->targets) == true ? new stdClass() : $package->targets;
	}
	
	/**
	 * Get plugin's title
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return string $title plugin's title
	 */
	function getTitle($plugin=null) {
		$package = $this->getPackage($plugin);
		if ($package == null) return '';
		
		if (isset($package->title->{$this->IM->language}) == true) return $package->title->{$this->IM->language};
		else return $this->title->{$package->language};
	}
	
	/**
	 * Get plugin's description
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return string $title plugin's title
	 */
	function getDescription($plugin=null) {
		$package = $this->getPackage($plugin);
		if ($package == null) return '';
		
		if (isset($package->description->{$this->IM->language}) == true) return $package->description->{$this->IM->language};
		else return $this->description->{$package->language};
	}
	
	/**
	 * Get loaded plugin path (included DOCUMENT_ROOT)
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return string $path
	 */
	function getPath($plugin=null) {
		if ($plugin !== null) return __IM_PATH__.'/plugins/'.$plugin;
		if ($this->IM !== null) return null;
		return $this->pluginPath;
	}
	
	/**
	 * Get loaded plugin path (not included DOCUMENT_ROOT)
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return string $path
	 */
	function getDir($plugin=null) {
		if ($plugin !== null) return __IM_DIR__.'/plugins/'.$plugin;
		if ($this->IM !== null) return null;
		return $this->pluginDir;
	}
	
	/**
	 * Get plugin installed info from im_plugin_table (install plugin only)
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return object $info
	 */
	function getInstalled($plugin=null) {
		if ($plugin !== null) {
			return $this->db('default')->select($this->table->plugin)->where('plugin',$plugin)->getOne();
		} else {
			return $this->pluginInstalled;
		}
	}
	
	/**
	 * Get plugin configs from im_plugin_table's configs field (install plugin only)
	 *
	 * @return object $info
	 */
	function getConfig($key) {
		if (empty($this->pluginConfigs->$key) == true) return null;
		else return $this->pluginConfigs->$key;
	}
	
	/**
	 * Check plugin install status before load plugin
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return boolean $installed
	 */
	function isInstalled($plugin=null) {
		if ($plugin !== null) return $this->db()->select($this->table->plugin)->where('plugin',$plugin)->has();
		else return $this->pluginInstalled !== null;
	}
	
	/**
	 * Check plugin dependencies in Module (After plugin loaded)
	 *
	 * @param string $plugin plugin's name. If not exists this param, use loaded plugin after plugin loaded.
	 * @return boolean(string) $success
	 */
	function checkDependencies($plugin=null) {
		$package = $this->getPackage($plugin);
		
		foreach ($package->dependencies as $dependency=>$version) {
			if ($dependency == 'core') {
				if (version_compare($version,__IM_VERSION__,'>') == true) return false;
			} else {
				if ($this->IM->Module->isInstalled($dependency) == false) return false;
				if (version_compare($version,$this->IM->Module->getInstalled($dependency)->version,'>') == true) return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Plugin install
	 *
	 * @param string $plugin plugin'name
	 * @param object $configs plugin's configs value
	 * @return boolean $success
	 */
	function install($plugin,$configDatas=null) {
		$package = $this->getPackage($plugin);
		if ($package == null) return 'NOT_FOUND';
		if ($this->checkDependencies($plugin) == false) return 'DEPENDENCY_ERROR';
		
		// create databases
		if (isset($package->databases) == true) {
			if (CreateDatabase($this->db(),$package->databases) == false) return 'DATABASE_ERROR';
		}
		
		if (isset($package->configs) == true) {
			$configs = new stdClass();
			$installed = $this->isInstalled($plugin) == true ? json_decode($this->getInstalled($plugin)->configs) : new stdClass();
			foreach ($package->configs as $config=>$type) {
				if (isset($configDatas->$config) == true) $value = $configDatas->$config;
				elseif (isset($installed->$config) == true) $value = $installed->$config;
				else $value = $type->default;
				
				if ($type->type == 'boolean') $value = $value === true || $value == 'on' ? true : false;
				
				$configs->$config = $value;
			}
		} else {
			$configs = new stdClass();
		}
		$configs = json_encode($configs,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
		
		$targets = isset($package->targets) == true ? json_encode($package->targets,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) : '{}';
		
		// insert plugin table
		if ($this->isInstalled($plugin) == false) {
			$this->db()->insert($this->table->plugin,array(
				'plugin'=>$plugin,
				'hash'=>$this->getHash($plugin),
				'version'=>$package->version,
				'configs'=>$configs,
				'targets'=>$targets,
				'active'=>'TRUE'
			))->execute();
		} else {
			$this->db()->update($this->table->plugin,array(
				'hash'=>$this->getHash($plugin),
				'version'=>$package->version,
				'configs'=>$configs,
				'targets'=>$targets
			))->where('plugin',$plugin)->execute();
		}
		
		return true;
	}
}
?>