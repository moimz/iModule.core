<?php
/**
 * This file is part of iModule - https://www.imodule.kr
 *
 * @file Module.class.php
 * @author Arzz
 * @license MIT License
 */
class Module {
	private $IM;
	
	private $table;
	private $modulePath;
	private $moduleDir;
	private $modulePackage;
	private $moduleConfigs = null;
	private $moduleInstalled = null;
	private $loadedPackage = array();
	
	private $loaded = false;
	
	/**
	 * construct
	 *
	 * @param object $IM iModule core
	 */
	function __construct($IM) {
		$this->IM = $IM;
		$this->table = new stdClass();
		$this->table->module = 'module_table';
		
		// Registe module's event listenters
		if ($this->IM->cache()->check('core','modules','all') > time() - 60) {
			$modules = json_decode($this->IM->cache()->get('core','modules','all'));
		} else {
			$modules = $this->IM->db()->select($this->table->module)->get();
			$this->IM->cache()->store('core','modules','all',json_encode($modules));
		}
		
		for ($i=0, $loop=sizeof($modules);$i<$loop;$i++) {
			$targets = $modules[$i]->targets ? json_decode($modules[$i]->targets) : new stdClass();
			
			foreach ($targets as $target=>$events) {
				foreach ($events as $event=>$callers) {
					if ($callers == '*') {
						$this->IM->Event->addTarget($target,$event,'*','module/'.$modules[$i]->module);
					} else {
						foreach ($callers as $caller) {
							$this->IM->Event->addTarget($target,$event,$caller,'module/'.$modules[$i]->module);
						}
					}
				}
			}
		}
	}
	
	/**
	 * Get module's hash for module modified check
	 *
	 * @param string $module module's name
	 * @return string $hash module's hash
	 */
	function getHash($module) {
		return file_exists(__IM_PATH__.'/modules/'.$module.'/package.json') == true ? md5_file(__IM_PATH__.'/modules/'.$module.'/package.json') : false;
	}
	
	/**
	 * Get module info from package.json file
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return object $info
	 */
	function getPackage($module=null) {
		if ($module !== null) {
			if (isset($this->loadedPackage[$module]) == true) return $this->loadedPackage[$module];
			if (file_exists(__IM_PATH__.'/modules/'.$module.'/package.json') == false) return null;
			$this->loadedPackage[$module] = json_decode(file_get_contents(__IM_PATH__.'/modules/'.$module.'/package.json'));
			return $this->loadedPackage[$module];
		} else {
			return $this->modulePackage;
		}
	}
	
	/**
	 * Get module's event listsners from package.json file
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return object[] $eventListeners module's event listeners
	 */
	function getTarget($module=null) {
		$package = $this->getPackage($module);
		return $package == null || empty($package->targets) == true ? new stdClass() : $package->targets;
	}
	
	/**
	 * Get module's title
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return string $title module's title
	 */
	function getTitle($module=null) {
		$package = $this->getPackage($module);
		if ($package == null) return '';
		
		if (isset($package->title->{$this->IM->language}) == true) return $package->title->{$this->IM->language};
		else return $this->title->{$package->language};
	}
	
	/**
	 * Get module's description
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return string $title module's title
	 */
	function getDescription($module=null) {
		$package = $this->getPackage($module);
		if ($package == null) return '';
		
		if (isset($package->description->{$this->IM->language}) == true) return $package->description->{$this->IM->language};
		else return $this->description->{$package->language};
	}
	
	/**
	 * Get loaded module path (included DOCUMENT_ROOT)
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return string $path
	 */
	function getPath($module=null) {
		if ($module !== null) return __IM_PATH__.'/modules/'.$module;
		else return $this->modulePath;
	}
	
	/**
	 * Get loaded module path (not included DOCUMENT_ROOT)
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return string $path
	 */
	function getDir($module=null) {
		if ($module !== null) return __IM_DIR__.'/modules/'.$module;
		return $this->moduleDir;
	}
	
	/**
	 * Get module installed info from im_module_table (install module only)
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return object $info
	 */
	function getInstalled($module=null) {
		if ($module !== null) {
			return $this->IM->db('default')->select($this->table->module)->where('module',$module)->getOne();
		} else {
			return $this->moduleInstalled;
		}
	}
	
	/**
	 * Get module configs from im_module_table's configs field (install module only)
	 *
	 * @return object $info
	 */
	function getConfig($key=null) {
		if ($key == null) return $this->moduleConfig;
		elseif (empty($this->moduleConfigs->$key) == true) return null;
		else return $this->moduleConfigs->$key;
	}
	
	/**
	 * Get module configs from im_module_table's configs field (install module only)
	 *
	 * @return object $info
	 */
	function getTemplets($is_include_site=true) {
		if ($this->loaded == false) return array();
		
		$lists = array();
		$templetsPath = @opendir($this->modulePath.'/templets');
		while ($templetName = @readdir($templetsPath)) {
			if ($templetName != '.' && $templetName != '..' && is_dir($this->modulePath.'/templets/'.$templetName) == true) {
				$package = $this->getTempletPackage($templetName);
				if ($package !== null) $lists[] = $package;
			}
		}
		@closedir($templetsPath);
		
		if ($is_include_site == true) {
			$siteTemplets = @opendir(__IM_PATH__.'/templets');
			while ($siteTemplet = @readdir($siteTemplets)) {
				if ($siteTemplet != '.' && $siteTemplet != '..' && is_dir(__IM_PATH__.'/templets/'.$siteTemplet.'/templets/modules/'.$this->loaded) == true) {
					$templetsPath = @opendir(__IM_PATH__.'/templets/'.$siteTemplet.'/templets/modules/'.$this->loaded.'/templets');
					while ($templetName = @readdir($templetsPath)) {
						if ($templetName != '.' && $templetName != '..' && is_dir(__IM_PATH__.'/templets/'.$siteTemplet.'/templets/modules/'.$this->loaded.'/templets/'.$templetName) == true) {
							$package = $this->getTempletPackage('@'.$siteTemplet.'/'.$templetName);
							if ($package !== null) $lists[] = $package;
						}
					}
					@closedir($templetsPath);
				}
			}
			@closedir($siteTemplets);
		}
		
		return $lists;
	}
	
	function getTempletPackage($templet) {
		if ($this->loaded == false) return null;
		
		if (preg_match('/^@/',$templet) == true) {
			$temp = explode('/',preg_replace('/^@/','',$templet));
			$templetPath = __IM_PATH__.'/templets/'.$temp[0].'/templets/modules/'.$this->loaded.'/templets/'.$temp[1];
			$templetDir = __IM_DIR__.'/templets/'.$temp[0].'/templets/modules/'.$this->loaded.'/templets/'.$temp[1];
		} else {
			$templetPath = $this->modulePath.'/templets/'.$templet;
			$templetDir = $this->moduleDir.'/templets/'.$templet;
		}
		
		if (is_dir($templetPath) === false || is_file($templetPath.'/package.json') == false) return null;
		$package = json_decode(file_get_contents($templetPath.'/package.json'));
		if ($package == null) return null;
		$package->name = $templet;
		$package->title = isset($package->title->{$this->IM->language}) == true ? $package->title->{$this->IM->language} : $package->title->{$package->language};
		$package->path = $templetPath;
		$package->dir = $templetDir;
		
		return $package;
	}
	
	/**
	 * Check module install status before load module
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return boolean $installed
	 */
	function isInstalled($module=null) {
		if ($module !== null) return $this->IM->db()->select($this->table->module)->where('module',$module)->has();
		else return $this->moduleInstalled !== null;
	}
	
	/**
	 * Check module dependencies in Module (After module loaded)
	 *
	 * @param string $module module's name. If not exists this param, use loaded module after module loaded.
	 * @return boolean(string) $success
	 */
	function checkDependencies($module=null) {
		$package = $this->getPackage($module);
		
		foreach ($package->dependencies as $dependency=>$version) {
			if ($dependency == 'core') {
				if (version_compare($version,__IM_VERSION__,'>') == true) return false;
			} else {
				if ($this->isInstalled($dependency) == false) return false;
				if (version_compare($version,$this->getInstalled($dependency)->version,'>') == true) return false;
			}
		}
		
		return true;
	}
	
	function resetArticle() {
		$modules = $this->IM->db()->select($this->table->module)->where('is_article','TRUE')->get();
		for ($i=0, $loop=count($modules);$i<$loop;$i++) {
			$this->IM->getModule($modules[$i]->module)->resetArticle();
		}
	}
	
	/**
	 * Load module
	 *
	 * @param string $module module's name
	 * @return Module $module module's class object
	 */
	function load($module) {
		$this->modulePath = __IM_PATH__.'/modules/'.$module;
		$this->moduleDir = __IM_DIR__.'/modules/'.$module;
		
		if (is_dir($this->modulePath) == false) return false;
		
		$this->modulePackage = $this->getPackage($module);
		
		// check installed
		$this->moduleInstalled = $this->IM->db('default')->select($this->table->module)->where('module',$module)->getOne();
		if ($this->moduleInstalled == null) return false;
		else $this->moduleConfigs = json_decode($this->moduleInstalled->configs);
		
		$class = 'Module'.ucfirst($module);
		if (file_exists($this->modulePath.'/'.$class.'.class.php') == false) return false;
		
		$this->loaded = $module;
		
		return new $class($this->IM,$this);
	}
	
	/**
	 * Load global module when iModule is rendered on web browser
	 */
	function loadGlobals() {
		$globals = $this->IM->db()->select($this->table->module)->where('is_global','TRUE')->get();
		for ($i=0, $loop=sizeof($globals);$i<$loop;$i++) {
			$this->IM->getModule($globals[$i]->module);
		}
	}
	
	/**
	 * Get admin panel of modules
	 *
	 * @return object[] $modules
	 */
	function getAdmins() {
		$modules = $this->IM->db()->select($this->table->module)->where('is_admin','TRUE')->get();
		for ($i=0, $loop=sizeof($modules);$i<$loop;$i++) {
//			$modules[$i] = $this->IM->getModule($modules[$i]->module);
		}
		
		return $modules;
	}
	
	/**
	 * Get modules that used context
	 *
	 * @return object[] $modules
	 */
	function getContexts() {
		$modules = $this->IM->db()->select($this->table->module)->where('is_context','TRUE')->get();
		for ($i=0, $loop=sizeof($modules);$i<$loop;$i++) {
			$modules[$i]->title = $this->getTitle($modules[$i]->module);
		}
		
		return $modules;
	}
	
	/**
	 * Module install
	 *
	 * @param string $module module'name
	 * @param object $configs module's configs value
	 * @return boolean $success
	 */
	function install($module,$configDatas=null,$database='default') {
		$package = $this->getPackage($module);
		if ($package == null) return 'NOT_FOUND';
		if ($this->checkDependencies($module) == false) return 'DEPENDENCY_ERROR';
		
		// make attachments folder
		if (isset($package->attachments) == true && is_array($package->attachments) == true) {
			for ($i=0, $loop=count($package->attachments);$i<$loop;$i++) {
				if (is_dir($this->IM->getAttachmentPath().'/'.$package->attachments[$i]) == false) {
					mkdir($this->IM->getAttachmentPath().'/'.$package->attachments[$i],0707);
				}
			}
		}
		
		// create databases
		if (isset($package->databases) == true) {
			if (CreateDatabase($this->IM->db($database),$package->databases) == false) return 'DATABASE_ERROR';
		}
		
		if (isset($package->configs) == true) {
			$configs = new stdClass();
			$installed = $this->isInstalled($module) == true ? json_decode($this->getInstalled($module)->configs) : new stdClass();
			foreach ($package->configs as $config=>$type) {
				if (isset($configDatas->$config) == true) $value = $configDatas->$config;
				elseif (isset($installed->$config) == true) $value = $installed->$config;
				else $value = $type->value;
				
				if ($type->type == 'boolean') $value = $value === true || $value === 'on' ? true : false;
				elseif ($type->type == 'array' && is_array($value) == false) $value = explode(',',$value);
				elseif ($type->type == 'number' && is_numeric($value) == false) $value = floatVal($value);
				
				$configs->$config = $value;
			}
		} else {
			$configs = new stdClass();
		}
		$configs = json_encode($configs,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
		
		$targets = isset($package->targets) == true ? json_encode($package->targets,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) : '{}';
		
		// insert module table
		if ($this->isInstalled($module) == false) {
			$this->IM->db()->insert($this->table->module,array(
				'module'=>$module,
				'hash'=>$this->getHash($module),
				'version'=>$package->version,
				'database'=>$database,
				'is_global'=>isset($package->global) == true && $package->global === true ? 'TRUE' : 'FALSE',
				'is_admin'=>isset($package->admin) == true && $package->admin === true ? 'TRUE' : 'FALSE',
				'is_context'=>isset($package->context) == true && $package->context === true ? 'TRUE' : 'FALSE',
				'is_article'=>isset($package->article) == true && $package->article === true ? 'TRUE' : 'FALSE',
				'configs'=>$configs,
				'targets'=>$targets
			))->execute();
		} else {
			$this->IM->db()->update($this->table->module,array(
				'hash'=>$this->getHash($module),
				'version'=>$package->version,
				'database'=>$database,
				'is_global'=>isset($package->global) == true && $package->global === true ? 'TRUE' : 'FALSE',
				'is_admin'=>isset($package->admin) == true && $package->admin === true ? 'TRUE' : 'FALSE',
				'is_context'=>isset($package->context) == true && $package->context === true ? 'TRUE' : 'FALSE',
				'is_article'=>isset($package->article) == true && $package->article === true ? 'TRUE' : 'FALSE',
				'configs'=>$configs,
				'targets'=>$targets
			))->where('module',$module)->execute();
		}
		
		return true;
	}
}
?>