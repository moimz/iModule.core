<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 모듈 코어클래스로 iModule 의 모든 모듈을 관리한다.
 * 이 클래스는 iModule 코어나, Admin 모듈에서 호출되는 경우를 제외하고 각 모듈클래스에서 호출되는 경우 대부분의 $module 파라매터를 사용하지 않아도 된다.
 * 이 클래스는 각 모듈클래스에서 $Module 변수로 접근할 수 있다.
 * 
 * @file /classes/Module.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 6. 21.
 */
class Module {
	/**
	 * iModule 코어클래스
	 */
	private $IM;
	
	/**
	 * DB 관련 변수정의
	 *
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $table;
	
	/**
	 * 각 모듈에서 이 클래스를 호출하였을 경우 사용되는 모듈정보
	 *
	 * @private string $modulePath 모듈 절대경로
	 * @private string $moduleDir 모듈 상대경로
	 * @private string $modulePackage 모듈 package.json 정보
	 * @private object $moduleConfigs 모듈 환경설정 정보
	 * @private object $moduleInstalled 모듈 설치정보
	 * @private object[] $loadedPackages 읽어온 모듈의 package.json 정보
	 * @private Templet[] $loadedTemplets 읽어온 템플릿 객체
	 */
	private $modulePath;
	private $moduleDir;
	private $modulePackage;
	private $moduleConfigs = null;
	private $moduleInstalled = null;
	private $loadedPackages = array();
	private $loadedTemplets = array();
	
	/**
	 * 호출된 모듈이 있을 경우 해당 모듈명
	 */
	private $loaded = false;
	private $loadedClass = null;
	
	/**
	 * class 선언
	 *
	 * @param iModule $IM iModule 코어클래스
	 * @see /classes/iModule.class.php
	 */
	function __construct($IM) {
		/**
		 * iModule 코어 선언
		 */
		$this->IM = $IM;
		
		/**
		 * 모듈에서 사용하는 DB 테이블 별칭 정의
		 * @see iModule 코어 package.json 의 databases 참고
		 */
		$this->table = new stdClass();
		$this->table->module = 'module_table';
		
		/**
		 * 설치된 모듈에서 사용하는 이벤트리스너를 모두 Event 클래스에 등록한다.
		 */
		$this->addEventListener();
	}
	
	/**
	 * 설치된 모듈에서 사용하는 이벤트리스너를 모두 Event 클래스에 등록한다.
	 * DB접근을 줄이기 위하여 60초동안 모든 모듈에 대한 이벤트 리스너를 캐싱한다.
	 *
	 * @param boolean $is_force_update 캐싱된 사항을 무시하고 강제로 업데이트할지 여부(기본값 : false)
	 */
	function addEventListener($is_force_update=false) {
		if ($is_force_update == false && $this->IM->cache()->check('core','modules','all') > time() - 60) {
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
						$this->IM->Event->addEventListener($target,$event,'*','module/'.$modules[$i]->module);
					} else {
						foreach ($callers as $caller) {
							$this->IM->Event->addEventListener($target,$event,$caller,'module/'.$modules[$i]->module);
						}
					}
				}
			}
		}
	}
	
	/**
	 * [코어] 모듈을 불러온다.
	 *
	 * @param string $module 모듈명
	 * @param boolean $forceLoad(옵션) 설치가 되지 않은 모듈이라도 강제로 모듈클래스를 호출할지 여부
	 * @return Module $Module 모듈클래스
	 */
	function load($module,$forceLoad=false) {
		/**
		 * 모듈을 불러올때 모듈코어의 모듈전용 변수를 선언한다.
		 */
		$this->moduleName = $module;
		$this->modulePath = __IM_PATH__.'/modules/'.$module;
		$this->moduleDir = __IM_DIR__.'/modules/'.$module;
		
		if (is_dir($this->modulePath) == false) return false;
		$this->modulePackage = $this->getPackage($module);
		
		/**
		 * 설치여부를 확인하여, 설치되지 않았다면 false 를 반환한다.
		 * 단 $forceLogin 값이 true 일 경우 클래스를 반환한다.
		 */
		$this->moduleInstalled = $this->IM->db('default')->select($this->table->module)->where('module',$module)->getOne();
		if ($this->moduleInstalled != null) {
			$this->moduleConfigs = json_decode($this->moduleInstalled->configs);
		} else {
			$this->moduleConfigs = null;
			if ($forceLoad == false) return false;
		}
		
		$class = 'Module'.ucfirst($module);
		if (is_file($this->modulePath.'/'.$class.'.class.php') == false) return false;
		
		$this->loaded = $module;
		$this->loadedClass = new $class($this->IM,$this);
		
		return $this->loadedClass;
	}
	
	/**
	 * [코어] iModule 코어에서 사이트를 구성할때 전역모듈을 불러온다.
	 *
	 * @see /classes/iModule.class.php -> doLayout()
	 */
	function loadGlobals() {
		/**
		 * 관리자일 경우 처리하지 않는다.
		 */
		if ($_SERVER['SCRIPT_NAME'] == '/admin/index.php') return;
		
		$globals = $this->IM->db()->select($this->table->module)->where('is_global','TRUE')->get();
		for ($i=0, $loop=sizeof($globals);$i<$loop;$i++) {
			$this->IM->getModule($globals[$i]->module);
		}
	}
	
	/**
	 * [모듈내부] 호출한 모듈 클래스를 반환한다.
	 *
	 * @return object $moduleClass 모듈클래스
	 */
	function getClass() {
		return $this->loadedClass;
	}
	
	/**
	 * [모듈내부] 현재 모듈명을 반환한다.
	 *
	 * @return string $name 모듈명
	 */
	function getName() {
		if ($this->loaded === false) return null;
		return $this->loaded;
	}
	
	/**
	 * 모듈코어에서 사용중인 DB테이블 별칭을 이용하여 실제 DB테이블 명을 반환한다.
	 *
	 * @param string $table DB테이블 별칭
	 * @return string $table 실제 DB테이블 명
	 */
	function getTable($table) {
		return empty($this->table->$table) == true ? null : $this->table->$table;
	}
	
	/**
	 * [코어/모듈내부] 모듈 타이틀을 반환한다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return string $title 모듈 타이틀
	 */
	function getTitle($module=null) {
		$package = $this->getPackage($module);
		if ($package == null) return '';
		
		if (isset($package->title->{$this->IM->language}) == true) return $package->title->{$this->IM->language};
		else return $package->title->{$package->language};
	}
	
	/**
	 * [코어/모듈내부] 모듈설명을 반환한다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return string $title 모듈설명
	 */
	function getDescription($module=null) {
		$package = $this->getPackage($module);
		if ($package == null) return '';
		
		if (isset($package->description->{$this->IM->language}) == true) return $package->description->{$this->IM->language};
		else return $this->description->{$package->language};
	}
	
	/**
	 * [코어/모듈내부] 모듈의 package.json 파일의 해시를 구한다.
	 * 모듈이 업데이트되었는지 여부를 확인하는 용도로 쓰인다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return string $hash package.json 해시
	 */
	function getHash($module=null) {
		$module = $module == null ? $this->loaded : $module;
		return file_exists(__IM_PATH__.'/modules/'.$module.'/package.json') == true ? md5_file(__IM_PATH__.'/modules/'.$module.'/package.json') : false;
	}
	
	/**
	 * [코어/모듈내부] 모듈의 package.json 정보를 반환한다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return object $package package.json 정보
	 */
	function getPackage($module=null) {
		if ($module !== null) {
			if (isset($this->loadedPackages[$module]) == true) return $this->loadedPackages[$module];
			if (file_exists(__IM_PATH__.'/modules/'.$module.'/package.json') == false) return null;
			$this->loadedPackages[$module] = json_decode(file_get_contents(__IM_PATH__.'/modules/'.$module.'/package.json'));
			return $this->loadedPackages[$module];
		} else {
			return $this->modulePackage;
		}
	}
	
	/**
	 * [코어/모듈내부] 모듈에서 정의된 이벤트리스너를 반환한다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return object[] $eventListeners module's event listeners
	 */
	function getTarget($module=null) {
		$package = $this->getPackage($module);
		return $package == null || empty($package->targets) == true ? new stdClass() : $package->targets;
	}
	
	/**
	 * [코어/모듈내부] 모듈의 절대경로를 반환한다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return string $path 모듈 절대경로
	 */
	function getPath($module=null) {
		if ($module !== null) return __IM_PATH__.'/modules/'.$module;
		else return $this->modulePath;
	}
	
	/**
	 * [코어/모듈내부] 모듈의 상대경로를 반환한다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return string $path 모듈 상대경로
	 */
	function getDir($module=null) {
		if ($module !== null) return __IM_DIR__.'/modules/'.$module;
		return $this->moduleDir;
	}
	
	/**
	 * [사이트관리자] 모듈의 설정패널을 구성한다.
	 *
	 * @param string $module 모듈명
	 * @return string $panel 설정패널 HTML
	 */
	function getConfigPanel($module) {
		/**
		 * 모듈을 불러온다.
		 */
		$mModule = new Module($this->IM);
		$mModule = $mModule->load($module,true);
		
		/**
		 * 모듈클래스에 설정패널 함수가 있으면 해당 함수의 결과값을 리턴한다.
		 */
		if (method_exists($mModule,'getConfigPanel') == true) return $mModule->getConfigPanel();
		
		/**
		 * 모듈클래스에 설정패널 함수가 없다면, 설정패널을 생성한다.
		 */
		$package = $this->getPackage($module);
		
		/**
		 * 모듈환경설정이 없다면, NULL 을 반환한다.
		 */
		if (isset($package->configs) == false || $package->configs == null) return null;
		
		$panel = array();
		array_push($panel,
			'<script>',
			'new Ext.form.Panel({',
				'id:"ModuleConfigForm",',
				'border:false,',
				'bodyPadding:10,',
				'fieldDefaults:{labelAlign:"right",labelWidth:160,anchor:"100%",allowBlank:true},',
				'items:['
		);
		
		$fields = array();
		foreach ($package->configs as $key=>$config) {
			$field = array();
			array_push($field,
				'new Ext.form.TextField({',
					'name:"'.$key.'",',
					'fieldLabel:"'.(isset($config->title->{$this->IM->language}) == true ? $config->title->{$this->IM->language} : $config->title->{$package->language}).'",'
			);
			
			if (isset($config->help) == true) {
				array_push($field,
					'afterBodyEl:"<div class=\"x-form-help\">'.(isset($config->help->{$this->IM->language}) == true ? $config->help->{$this->IM->language} : $config->help->{$package->language}).'</div>",'
				);
			}
			
			array_push($field,
					'allowBlank:false',
				'})'
			);
			
			$field = implode(PHP_EOL,$field);
			
			$fields[] = $field;
		}
		
		$fields = implode(','.PHP_EOL,$fields);
		array_push($panel,$fields);
		
		array_push($panel,
				']',
			'});',
			'</script>'
		);
		
		return implode(PHP_EOL,$panel);
	}
	
	/**
	 * [사이트관리자] 모듈의 관리자패널 구성한다.
	 *
	 * @param string $module 모듈명
	 * @return string $panel 관리자패널 HTML
	 */
	function getAdminPanel($module) {
		/**
		 * 모듈을 불러온다.
		 */
		$mModule = $this->IM->getModule($module);
		
		/**
		 * 모듈의 언어팩을 불러온다.
		 */
		$this->loadLanguage($module);
		
		/**
		 * 모듈의 관리자용 자바스크립트나 스타일시트가 있을 경우 불러온다.
		 */
		if (is_file(__IM_PATH__.'/modules/'.$module.'/admin/styles/style.css') == true) {
			$this->IM->addHeadResource('style',__IM_DIR__.'/modules/'.$module.'/admin/styles/style.css');
		}
		
		if (is_file(__IM_PATH__.'/modules/'.$module.'/admin/scripts/script.js') == true) {
			$this->IM->addHeadResource('script',__IM_DIR__.'/modules/'.$module.'/admin/scripts/script.js');
		}
		
		/**
		 * 모듈에 설정패널 메소드가 없으면 NULL 을 반환한다.
		 */
		if (method_exists($mModule,'getAdminPanel') == false) return null;
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		$this->IM->fireEvent('afterGetAdminPanel',$module,'admin');
		
		return $mModule->getAdminPanel();
	}
	
	/**
	 * [모듈내부] 모듈의 환경설정을 가져온다.
	 *
	 * @param string $key(옵션) 환경설정코드값, 없을경우 전체 환경설정값
	 * @return string $value 환경설정값
	 */
	function getConfig($key=null) {
		if ($key == null) return $this->moduleConfigs;
		elseif ($this->moduleConfigs == null || isset($this->moduleConfigs->$key) == false) return null;
		else return $this->moduleConfigs->$key;
	}
	
	/**
	 * [모듈내부] 모듈의 환경설정을 저장한다.
	 *
	 * @param string $key 환경설정코드값, 없을경우 전체 환경설정값
	 * @param object $value 변경할 환경설정값
	 * @return Module $this
	 */
	function setConfig($key,$value) {
		if ($this->moduleConfigs == null) return $this;
		$this->moduleConfigs->{$key} = $value;
		$this->IM->db()->update($this->table->module,array('configs'=>json_encode($this->moduleConfigs,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK)))->where('module',$this->loaded)->execute();
		
		return $this;
	}
	
	/**
	 * [코어/모듈내부] 모듈의 설치정보를 가져온다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return object $data 설치정보
	 */
	function getInstalled($module=null) {
		if ($module !== null) {
			return $this->IM->db('default')->select($this->table->module)->where('module',$module)->getOne();
		} else {
			return $this->moduleInstalled;
		}
	}
	
	/**
	 * [사이트관리자] 모듈의 컨텍스트 목록을 가져온다.
	 *
	 * @param string $module 모듈명
	 * @return striong[] $contexts 컨텍스트명
	 */
	function getContexts($module) {
		$mModule = $this->IM->getModule($module);
		if (method_exists($mModule,'getContexts') == true) return $mModule->getContexts();
		return array();
	}
	
	/**
	 * [사이트관리자] 모듈의 컨텍스트 환경설정을 가져온다.
	 *
	 * @param string $domain 설정대상 사이트도메인, 없을경우 현재사이트
	 * @param string $langauge 설정대상 사이트언어셋, 없을경우 현재사이트
	 * @param string $menu 설정대상 사이트메뉴
	 * @param string $page 설정대상 사이트페이지
	 * @param string $module 설정대상 모듈명
	 * @param string $context 설정대상 컨텍스트명
	 * @return object[] $configs 컨텍스트 환경설정
	 */
	function getContextConfigs($domain,$language,$menu,$page,$module,$context) {
		$site = $this->IM->getSites($domain,$language);
		$page = $menu && $page ? $this->IM->getPages($menu,$page,$domain,$language) : null;
		
		if ($page != null && $page->type == 'MODULE' && $page->context->module == $module && $page->context->context == $context) {
			$values = $page->context->configs;
		} else {
			$values = null;
		}
		
		$mModule = $this->IM->getModule($module);
		if (method_exists($mModule,'getContextConfigs') == true) return $mModule->getContextConfigs($site,$values,$context);
		return array();
	}
	
	/**
	 * 모듈의 컨텍스트 제목을 가져온다.
	 *
	 * @param string $context 컨텍스트명
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return string $title 컨텍스트 제목
	 */
	function getContextTitle($context,$module=null) {
		$module = $module == null ? $this->loaded : $module;
		if ($module == null) return '';
		
		$mModule = $this->IM->getModule($module);
		if (method_exists($mModule,'getContextTitle') == true) return $mModule->getContextTitle($context);
		return '';
	}
	
	/**
	 * [모듈내부] 모듈 템플릿의 package.json 정보를 가져온다.
	 *
	 * @param string $templet 템플릿명
	 * @param object $configs 템플릿설정
	 * @return Templet $templet 템플릿 객체
	 */
	function getTemplet($templet,$configs=null) {
		if (isset($this->loadedTemplets[$templet]) == true) return $this->loadedTemplets[$templet];
		$this->loadedTemplets[$templet] = $this->IM->getTemplet($this,$templet);
		if ($configs != null) $this->loadedTemplets[$templet]->setConfigs($configs);
		
		return $this->loadedTemplets[$templet];
	}
	
	/**
	 * [모듈내부] 모듈의 모든 템플릿을 가져온다.
	 *
	 * @return object[] $templets 템플릿정보
	 */
	function getTemplets() {
		return $this->IM->getTemplets($this);
	}
	
	/**
	 * [사이트관리자] 모듈 설정패널이 있는지 확인한다.
	 *
	 * @param string $module 모듈명
	 * @param boolean $hasConfig
	 */
	function isConfigPanel($module) {
		/**
		 * 모듈에 설정패널 설정패널 함수가 있으면 true 를 반환한다.
		 */
		$package = $this->getPackage($module);
		return isset($package->configs) == true && $package->configs != null;
	}
	
	/**
	 * [코어/모듈내부] 모듈이 설치가 되어 있는지 확인한다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return boolean $installed 모듈설치여부
	 */
	function isInstalled($module=null) {
		if ($module !== null) return $this->IM->db()->select($this->table->module)->where('module',$module)->has();
		else return $this->moduleInstalled !== null;
	}
	
	/**
	 * [코어/모듈내부] 모듈이 사이트맵 구성을 처리하는지 여부를 확인한다.
	 *
	 * @param string $module(옵션) 모듈명 (코어에서 호출시 사용, 모듈내부에서 호출시 호출한 모듈명)
	 * @return boolean $sitemap
	 */
	function isSitemap($module=null) {
		$installed = $this->getInstalled($module);
		return $installed != null && $installed->is_sitemap == 'TRUE';
	}
	
	/**
	 * [사이트관리자] 모듈의 설치조건이 만족하는지 확인한다.
	 *
	 * @param string $module 모듈명
	 * @return boolean(string) $success
	 */
	function checkDependencies($module) {
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
	
	/**
	 * 사이트관리자 기능을 사용하는 모듈목록을 가져온다.
	 *
	 * @return object[] $modules
	 */
	function getAdminModules() {
		$modules = $this->IM->db()->select($this->table->module)->where('is_admin','TRUE')->get();
		return $modules;
	}
	
	/**
	 * 컨텍스트를 사용한다고 설정된 모듈목록을 가져온다.
	 *
	 * @return object[] $modules
	 */
	function getContextModules() {
		$modules = $this->IM->db()->select($this->table->module)->where('is_context','TRUE')->get();
		for ($i=0, $loop=sizeof($modules);$i<$loop;$i++) {
			$modules[$i]->title = $this->getTitle($modules[$i]->module);
		}
		
		return $modules;
	}
	
	/**
	 * 크론작업을 사용한다고 설정된 모듈목록을 가져온다.
	 *
	 * @return object[] $modules
	 */
	function getCronModules() {
		$modules = $this->IM->db()->select($this->table->module)->where('is_cron','TRUE')->get();
		
		return $modules;
	}
	
	/**
	 * [코어/사이트관리자] 모듈을 설치한다.
	 *
	 * @param string $module 설치할 모듈명
	 * @param object $configs 사이트관리자로부터 넘어온 모듈환경설정값
	 * @param string $database 설치할 데이터베이스 코드
	 * @param boolean $isUpdateSize(옵션, 기본값 true) 모듈설치후 모듈이 사용하는 용량을 업데이트할지 여부
	 * @return boolean $success
	 */
	function install($module,$configDatas=null,$database='default',$isUpdateSize=true) {
		/**
		 * 모듈의 package.json 파일을 확인하고, 설치조건을 확인한다.
		 */
		$package = $this->getPackage($module);
		if ($package == null) return 'NOT_FOUND';
		if ($this->checkDependencies($module) == false) return 'DEPENDENCY_ERROR';
		
		/**
		 * 모듈에서 사용하는 attachment 폴더를 생성한다.
		 */
		if (isset($package->attachments) == true && is_array($package->attachments) == true) {
			for ($i=0, $loop=count($package->attachments);$i<$loop;$i++) {
				if (is_dir($this->IM->getAttachmentPath().'/'.$package->attachments[$i]) == false) {
					mkdir($this->IM->getAttachmentPath().'/'.$package->attachments[$i],0707);
				}
			}
		}
		
		/**
		 * 모듈에서 사용하는 DB테이블을 생성한다.
		 */
		if (isset($package->databases) == true) {
			$schema = CreateDatabase($this->IM->db($database),$package->databases);
			if ($schema !== true) return $this->IM->getErrorText('DB_CREATE_TABLE_ERROR',$schema);
		}
		
		if (isset($package->configs) == true) {
			$configs = new stdClass();
			$templetFields = array();
			$installed = $this->isInstalled($module) == true ? json_decode($this->getInstalled($module)->configs) : new stdClass();
			foreach ($package->configs as $config=>$type) {
				if ($configDatas != null && isset($configDatas->$config) == true) $value = $configDatas->$config;
				elseif (isset($installed->$config) == true) $value = $installed->$config;
				else $value = isset($type->default) == true ? $type->default : '';
				
				if ($type->type == 'boolean') $value = $value === true || $value === 'on' ? true : false;
				elseif ($type->type == 'array' && is_array($value) == false) $value = json_decode($value);
				elseif ($type->type == 'number' && is_numeric($value) == false) $value = floatVal($value);
				
				if ($type->type == 'templet') $templetFields[] = $config;
				
				$configs->$config = $value;
			}
			
			for ($i=0, $loop=count($templetFields);$i<$loop;$i++) {
				if (isset($configs->{$templetFields[$i].'_configs'}) == false) $configs->{$templetFields[$i].'_configs'} = new stdClass();
				
				if ($configDatas != null) {
					foreach ($configDatas as $key=>$value) {
						if (preg_match('/^'.$templetFields[$i].'_configs_(.*?)$/',$key,$match) == true) {
							$configs->{$templetFields[$i].'_configs'}->{$match[1]} = $value;
						}
					}
				}
			}
		} else {
			$configs = new stdClass();
		}
		$configs = json_encode($configs,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
		
		$targets = isset($package->targets) == true ? json_encode($package->targets,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK) : '{}';
		
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
				'is_widget'=>isset($package->widget) == true && $package->widget === true ? 'TRUE' : 'FALSE',
				'is_templet'=>isset($package->templet) == true && $package->templet === true ? 'TRUE' : 'FALSE',
				'is_external'=>isset($package->external) == true && $package->external === true ? 'TRUE' : 'FALSE',
				'is_sitemap'=>isset($package->sitemap) == true && $package->sitemap === true ? 'TRUE' : 'FALSE',
				'is_cron'=>isset($package->cron) == true && $package->cron === true ? 'TRUE' : 'FALSE',
				'configs'=>$configs,
				'targets'=>$targets
			))->execute();
			
			$mode = 'install';
		} else {
			$this->IM->db()->update($this->table->module,array(
				'hash'=>$this->getHash($module),
				'version'=>$package->version,
				'database'=>$database,
				'is_global'=>isset($package->global) == true && $package->global === true ? 'TRUE' : 'FALSE',
				'is_admin'=>isset($package->admin) == true && $package->admin === true ? 'TRUE' : 'FALSE',
				'is_context'=>isset($package->context) == true && $package->context === true ? 'TRUE' : 'FALSE',
				'is_article'=>isset($package->article) == true && $package->article === true ? 'TRUE' : 'FALSE',
				'is_widget'=>isset($package->widget) == true && $package->widget === true ? 'TRUE' : 'FALSE',
				'is_templet'=>isset($package->templet) == true && $package->templet === true ? 'TRUE' : 'FALSE',
				'is_external'=>isset($package->external) == true && $package->external === true ? 'TRUE' : 'FALSE',
				'is_sitemap'=>isset($package->sitemap) == true && $package->sitemap === true ? 'TRUE' : 'FALSE',
				'is_cron'=>isset($package->cron) == true && $package->cron === true ? 'TRUE' : 'FALSE',
				'configs'=>$configs,
				'targets'=>$targets
			))->where('module',$module)->execute();
			
			$mode = 'update';
		}
		
		if ($isUpdateSize === true) $this->updateSize($module);
		
		/**
		 * 이벤트리스너를 업데이트하고 이벤트를 발생시킨다.
		 */
		$this->addEventListener(true);
		$this->IM->fireEvent('afterInstall','core',$mode);
		
		return true;
	}
	
	/**
	 * [코어] 모듈이 사용하고 있는 용량을 구한다.
	 *
	 * @param string $module 모듈명
	 * @return object $size 용량
	 */
	function updateSize($module) {
		$package = $this->getPackage($module);
	
		$db_size = 0;
		if (isset($package->databases) == true) {
			$mModule = $this->IM->getModule($module);
			foreach ($package->databases as $tablename=>$schema) {
				$db_size+= $mModule->db()->size($tablename);
			}
		}
		
		if ($module == 'attachment') {
			$attachment_size = 0;
		} else {
			$mAttachment = $this->IM->getModule('attachment');
			$attachment = $mAttachment->db()->select($mAttachment->getTable('attachment'),'SUM(size) as size')->where('module',$module)->getOne();
			$attachment_size = isset($attachment->size) == true && $attachment->size ? $attachment->size : 0;
			
			if (isset($package->attachments) == true) {
				foreach ($package->attachments as $path) {
					$attachment_size+= GetFolderSize($this->IM->getAttachmentPath().'/'.$path);
				}
			}
		}
		
		$this->IM->db()->update($this->table->module,array('db_size'=>$db_size,'attachment_size'=>$attachment_size))->where('module',$module)->execute();
		
		$size = new stdClass();
		$size->db_size = $db_size;
		$size->attachment_size = $attachment_size;
		
		return $size;
	}
	
	/**
	 * 자바스크립트용 언어셋 파일을 호출한다.
	 * 언어셋은 기본적으로 PHP파일을 통해 사용되나 모듈의 자바스크립트에서 언어셋이 필요할 경우 해당 함수를 호출하여 자바스크립트상에서 모듈명.getLanguage() 함수로 언어셋을 불러올 수 있다.
	 *
	 * @param string $module 모듈명
	 */
	function loadLanguage($module) {
		$package = $this->getPackage($module);
		$this->IM->loadLanguage('module',$module,$package->language);
	}
	
	function resetArticle() {
		$modules = $this->IM->db()->select($this->table->module)->where('is_article','TRUE')->get();
		for ($i=0, $loop=count($modules);$i<$loop;$i++) {
			$this->IM->getModule($modules[$i]->module)->resetArticle();
		}
	}
}
?>