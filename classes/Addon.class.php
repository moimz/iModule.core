<?php
class Addon {
	private $IM;
	private $addons = array();
	private $table;
	private $addonPath;
	private $addonDir;
	private $addonPackage;
	private $addonConfigs = null;
	private $addonInstalled = null;
	private $loadedPackage = array();
	
	function __construct($IM) {
		$this->table = new stdClass();
		$this->table->addon = 'addon_table';
		
		if (is_object($IM) == true) {
			$this->IM = $IM;
			
			// Registe addon's event listenters
			if ($this->IM->cache()->check('core','addons','all') > time() - 60) {
				$addons = json_decode($this->IM->cache()->get('core','addons','all'));
			} else {
				$addons = $this->db()->select($this->table->addon)->where('active','TRUE')->get();
				$this->IM->cache()->store('core','addons','all',json_encode($addons));
			}
			
			for ($i=0, $loop=sizeof($addons);$i<$loop;$i++) {
				$targets = $addons[$i]->targets ? json_decode($addons[$i]->targets) : new stdClass();
				
				foreach ($targets as $target=>$events) {
					foreach ($events as $event=>$callers) {
						foreach ($callers as $caller) {
							$this->IM->Event->addTarget($target,$event,$caller,'addon/'.$addons[$i]->addon);
						}
					}
				}
			}
		} else {
			$this->IM = null;
			$this->addonPath = __IM_PATH__.'/addons/'.$IM;
			$this->addonDir = __IM_DIR__.'/addons/'.$IM;
			$this->addonPackage = $this->getPackage($IM);
		
			// check installed
			$this->addonInstalled = $this->db()->select($this->table->addon)->where('addon',$IM)->getOne();
			if ($this->addonInstalled == null) return false;
			else $this->addonConfigs = json_decode($this->addonInstalled->configs);
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
		
		if (is_dir($this->addonPath.'/languages') == false) return null;
		
		if ($this->lang == null) {
			if (file_exists($this->addonPath.'/languages/'.$this->IM->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents($this->addonPath.'/languages/'.$this->IM->language.'.json'));
			} else {
				$this->lang = json_decode(file_get_contents($this->addonPath.'/languages/'.$this->getPackage()->language.'.json'));
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
	 * Get addon hash for addon modified check
	 *
	 * @param string $addon addon's name
	 * @return string $hash addon's hash
	 */
	function getHash($addon) {
		return file_exists(__IM_PATH__.'/addons/'.$addon.'/package.json') == true ? md5_file(__IM_PATH__.'/addons/'.$addon.'/package.json') : false;
	}
	
	/**
	 * Get addon info from package.json file
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return object $info
	 */
	function getPackage($addon=null) {
		if ($addon !== null) {
			if (isset($this->loadedPackage[$addon]) == true) return $this->loadedPackage[$addon];
			if (file_exists(__IM_PATH__.'/addons/'.$addon.'/package.json') == false) return null;
			$this->loadedPackage[$addon] = json_decode(file_get_contents(__IM_PATH__.'/addons/'.$addon.'/package.json'));
			return $this->loadedPackage[$addon];
		} else {
			return $this->addonPackage;
		}
	}
	
	/**
	 * Get addon's event listsners from package.json file
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return object[] $eventListeners addon's event listeners
	 */
	function getTarget($addon=null) {
		$package = $this->getPackage($addon);
		return $package == null || empty($package->targets) == true ? new stdClass() : $package->targets;
	}
	
	/**
	 * Get addon's title
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return string $title addon's title
	 */
	function getTitle($addon=null) {
		$package = $this->getPackage($addon);
		if ($package == null) return '';
		
		if (isset($package->title->{$this->IM->language}) == true) return $package->title->{$this->IM->language};
		else return $this->title->{$package->language};
	}
	
	/**
	 * Get addon's description
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return string $title addon's title
	 */
	function getDescription($addon=null) {
		$package = $this->getPackage($addon);
		if ($package == null) return '';
		
		if (isset($package->description->{$this->IM->language}) == true) return $package->description->{$this->IM->language};
		else return $this->description->{$package->language};
	}
	
	/**
	 * Get loaded addon path (included DOCUMENT_ROOT)
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return string $path
	 */
	function getPath($addon=null) {
		if ($addon !== null) return __IM_PATH__.'/addons/'.$addon;
		if ($this->IM !== null) return null;
		return $this->addonPath;
	}
	
	/**
	 * Get loaded addon path (not included DOCUMENT_ROOT)
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return string $path
	 */
	function getDir($addon=null) {
		if ($addon !== null) return __IM_DIR__.'/addons/'.$addon;
		if ($this->IM !== null) return null;
		return $this->addonDir;
	}
	
	/**
	 * Get addon installed info from im_addon_table (install addon only)
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return object $info
	 */
	function getInstalled($addon=null) {
		if ($addon !== null) {
			return $this->db('default')->select($this->table->addon)->where('addon',$addon)->getOne();
		} else {
			return $this->addonInstalled;
		}
	}
	
	/**
	 * Get addon configs from im_addon_table's configs field (install addon only)
	 *
	 * @return object $info
	 */
	function getConfig($key) {
		if (empty($this->addonConfigs->$key) == true) return null;
		else return $this->addonConfigs->$key;
	}
	
	/**
	 * Check addon install status before load addon
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return boolean $installed
	 */
	function isInstalled($addon=null) {
		if ($addon !== null) return $this->db()->select($this->table->addon)->where('addon',$addon)->has();
		else return $this->addonInstalled !== null;
	}
	
	/**
	 * Check addon dependencies in Module (After addon loaded)
	 *
	 * @param string $addon addon's name. If not exists this param, use loaded addon after addon loaded.
	 * @return boolean(string) $success
	 */
	function checkDependencies($addon=null) {
		$package = $this->getPackage($addon);
		
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
	 * Addon install
	 *
	 * @param string $addon addon'name
	 * @param object $configs addon's configs value
	 * @return boolean $success
	 */
	function install($addon,$configDatas=null) {
		$package = $this->getPackage($addon);
		if ($package == null) return 'NOT_FOUND';
		if ($this->checkDependencies($addon) == false) return 'DEPENDENCY_ERROR';
		
		// create databases
		if (isset($package->databases) == true) {
			if (CreateDatabase($this->db(),$package->databases) == false) return 'DATABASE_ERROR';
		}
		
		if (isset($package->configs) == true) {
			$configs = new stdClass();
			$installed = $this->isInstalled($addon) == true ? json_decode($this->getInstalled($addon)->configs) : new stdClass();
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
		
		// insert addon table
		if ($this->isInstalled($addon) == false) {
			$this->db()->insert($this->table->addon,array(
				'addon'=>$addon,
				'hash'=>$this->getHash($addon),
				'version'=>$package->version,
				'configs'=>$configs,
				'targets'=>$targets,
				'active'=>'TRUE'
			))->execute();
		} else {
			$this->db()->update($this->table->addon,array(
				'hash'=>$this->getHash($addon),
				'version'=>$package->version,
				'configs'=>$configs,
				'targets'=>$targets
			))->where('addon',$addon)->execute();
		}
		
		return true;
	}
}
?>