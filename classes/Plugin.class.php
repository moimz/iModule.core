<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * 모든 플러그인을 관리한다.
 * 
 * @file /classes/Plugin.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2018. 6. 21.
 */
class Plugin {
	/**
	 * iModule 코어클래스
	 */
	private $IM;
	
	/**
	 * DB 관련 변수정의
	 *
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $DB;
	private $table;
	
	/**
	 * 각 플러그인에서 이 클래스를 호출하였을 경우 사용되는 플러그인정보
	 *
	 * @private string $pluginPath 플러그인 절대경로
	 * @private string $pluginDir 플러그인 상대경로
	 * @private string $pluginPackage 플러그인 package.json 정보
	 * @private object $pluginConfigs 플러그인 환경설정 정보
	 * @private object $pluginInstalled 플러그인 설치정보
	 * @private object[] $loadedPackage 읽어온 플러그인의 package.json 정보
	 * @private Templet[] $loadedTemplets 읽어온 템플릿 객체
	 */
	private $pluginPath;
	private $pluginDir;
	private $pluginPackage;
	private $pluginConfigs = null;
	private $pluginInstalled = null;
	private $loadedPackage = array();
	private $loadedTemplets = array();
	
	/**
	 * 호출된 플러그인이 있을 경우 해당 플러그인명
	 */
	private $loaded = false;
	
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
		 * 플러그인에서 사용하는 DB 테이블 별칭 정의
		 * @see iModule 코어 package.json 의 databases 참고
		 */
		$this->table = new stdClass();
		$this->table->plugin = 'plugin_table';
		
		/**
		 * 설치된 플러그인에서 사용하는 이벤트리스너를 모두 Event 클래스에 등록한다.
		 */
		$this->addEventListener();
	}
	
	/**
	 * 플러그인 설치시 정의된 DB코드를 사용하여 플러그인에서 사용할 전용 DB클래스를 반환한다.
	 *
	 * @return DB $DB
	 */
	function db() {
		if ($this->loaded === false) return $this->IM->db();
		if ($this->DB == null || $this->DB->ping() === false) $this->DB = $this->IM->db($this->getInstalled()->database);
		return $this->DB;
	}
	
	/**
	 * 설치된 플러그인에서 사용하는 이벤트리스너를 모두 Event 클래스에 등록한다.
	 * DB접근을 줄이기 위하여 60초동안 모든 플러그인에 대한 이벤트 리스너를 캐싱한다.
	 *
	 * @param boolean $is_force_update 캐싱된 사항을 무시하고 강제로 업데이트할지 여부(기본값 : false)
	 */
	function addEventListener($is_force_update=false) {
		if ($is_force_update == false && $this->IM->cache()->check('core','plugins','all') > time() - 60) {
			$plugins = json_decode($this->IM->cache()->get('core','plugins','all'));
		} else {
			$plugins = $this->IM->db()->select($this->table->plugin)->get();
			$this->IM->cache()->store('core','plugins','all',json_encode($plugins));
		}
		
		for ($i=0, $loop=sizeof($plugins);$i<$loop;$i++) {
			$targets = $plugins[$i]->targets ? json_decode($plugins[$i]->targets) : new stdClass();
			
			foreach ($targets as $target=>$events) {
				foreach ($events as $event=>$callers) {
					if ($callers == '*') {
						$this->IM->Event->addEventListener($target,$event,'*','plugin/'.$plugins[$i]->plugin);
					} else {
						foreach ($callers as $caller) {
							$this->IM->Event->addEventListener($target,$event,$caller,'plugin/'.$plugins[$i]->plugin);
						}
					}
				}
			}
		}
	}
	
	/**
	 * [코어] 플러그인을 불러온다.
	 *
	 * @param string $plugin 플러그인명
	 * @param boolean $forceLoad(옵션) 설치되지 않은 플러그인이라도 강제로 플러그인클래스를 호출할지 여부
	 * @return Plugin $Plugin 플러그인클래스
	 */
	function load($plugin,$forceLoad=false) {
		/**
		 * 플러그인을 불러올때 플러그인코어의 플러그인전용 변수를 선언한다.
		 */
		$this->pluginName = $plugin;
		$this->pluginPath = __IM_PATH__.'/plugins/'.$plugin;
		$this->pluginDir = __IM_DIR__.'/plugins/'.$plugin;
		
		if (is_dir($this->pluginPath) == false) return false;
		$this->pluginPackage = $this->getPackage($plugin);
		
		/**
		 * 설치여부를 확인하여, 설치되지 않았다면 false 를 반환한다.
		 * 단 $forceLogin 값이 true 일 경우 클래스를 반환한다.
		 */
		$this->pluginInstalled = $this->IM->db('default')->select($this->table->plugin)->where('plugin',$plugin)->getOne();
		if ($this->pluginInstalled != null) {
			$this->pluginConfigs = json_decode($this->pluginInstalled->configs);
		} else {
			$this->pluginConfigs = null;
			if ($forceLoad == false) return false;
		}
		
		$this->loaded = $plugin;
		
		return $this;
	}
	
	/**
	 * [플러그인내부] 현재 플러그인명을 반환한다.
	 *
	 * @return string $name 플러그인명
	 */
	function getName() {
		if ($this->loaded === false) return null;
		return $this->loaded;
	}
	
	/**
	 * 플러그인코어에서 사용중인 DB테이블 별칭을 이용하여 실제 DB테이블 명을 반환한다.
	 *
	 * @param string $table DB테이블 별칭
	 * @return string $table 실제 DB테이블 명
	 */
	function getTable($table) {
		return empty($this->table->$table) == true ? null : $this->table->$table;
	}
	
	/**
	 * [코어/플러그인내부] 플러그인 타이틀을 반환한다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return string $title 플러그인 타이틀
	 */
	function getTitle($plugin=null) {
		$package = $this->getPackage($plugin);
		if ($package == null) return '';
		
		if (isset($package->title->{$this->IM->language}) == true) return $package->title->{$this->IM->language};
		else return $package->title->{$package->language};
	}
	
	/**
	 * [코어/플러그인내부] 플러그인설명을 반환한다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return string $title 플러그인설명
	 */
	function getDescription($plugin=null) {
		$package = $this->getPackage($plugin);
		if ($package == null) return '';
		
		if (isset($package->description->{$this->IM->language}) == true) return $package->description->{$this->IM->language};
		else return $this->description->{$package->language};
	}
	
	/**
	 * [코어/플러그인내부] 플러그인의 package.json 파일의 해시를 구한다.
	 * 플러그인이 업데이트되었는지 여부를 확인하는 용도로 쓰인다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return string $hash package.json 해시
	 */
	function getHash($plugin=null) {
		$plugin = $plugin == null ? $this->loaded : $plugin;
		return file_exists(__IM_PATH__.'/plugins/'.$plugin.'/package.json') == true ? md5_file(__IM_PATH__.'/plugins/'.$plugin.'/package.json') : false;
	}
	
	/**
	 * [코어/플러그인내부] 플러그인의 package.json 정보를 반환한다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return object $package package.json 정보
	 */
	function getPackage($plugin=null) {
		if ($plugin !== null) {
			if (isset($this->loadedPackages[$plugin]) == true) return $this->loadedPackages[$plugin];
			if (file_exists(__IM_PATH__.'/plugins/'.$plugin.'/package.json') == false) return null;
			$this->loadedPackages[$plugin] = json_decode(file_get_contents(__IM_PATH__.'/plugins/'.$plugin.'/package.json'));
			return $this->loadedPackages[$plugin];
		} else {
			return $this->pluginPackage;
		}
	}
	
	/**
	 * [코어/플러그인내부] 플러그인에서 정의된 이벤트리스너를 반환한다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return object[] $eventListeners plugin's event listeners
	 */
	function getTarget($plugin=null) {
		$package = $this->getPackage($plugin);
		return $package == null || empty($package->targets) == true ? new stdClass() : $package->targets;
	}
	
	/**
	 * [코어/플러그인내부] 플러그인의 절대경로를 반환한다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return string $path 플러그인 절대경로
	 */
	function getPath($plugin=null) {
		if ($plugin !== null) return __IM_PATH__.'/plugins/'.$plugin;
		else return $this->pluginPath;
	}
	
	/**
	 * [코어/플러그인내부] 플러그인의 상대경로를 반환한다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return string $path 플러그인 상대경로
	 */
	function getDir($plugin=null) {
		if ($plugin !== null) return __IM_DIR__.'/plugins/'.$plugin;
		return $this->pluginDir;
	}
	
	/**
	 * [사이트관리자] 플러그인의 설정패널을 구성한다.
	 *
	 * @param string $plugin 플러그인명
	 * @return string $panel 설정패널 HTML
	 */
	function getConfigPanel($plugin) {
		/**
		 * 플러그인을 불러온다.
		 */
		$mPlugin = new Plugin($this->IM);
		$mPlugin = $mPlugin->load($plugin,true);
		
		/**
		 * 플러그인 폴더에 설정패널 파일이 있다면 해당 파일을 사용한다.
		 */
		if (is_file($mPlugin->getPath().'/admin/configs.php') == true) {
			$IM = $this->IM;
		
			ob_start();
			INCLUDE $mPlugin->getPath().'/admin/configs.php';
			$panel = ob_get_contents();
			ob_end_clean();
			
			return $panel;
		}
		
		/**
		 * 플러그인 폴더에 설정패널 파일이 없다면, 설정패널을 생성한다.
		 */
		$package = $this->getPackage($plugin);
		
		/**
		 * 플러그인환경설정이 없다면, NULL 을 반환한다.
		 */
		if (isset($package->configs) == false || $package->configs == null) return null;
		
		$panel = array();
		array_push($panel,
			'<script>',
			'new Ext.form.Panel({',
				'id:"PluginConfigForm",',
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
	 * [사이트관리자] 플러그인의 관리자패널 구성한다.
	 *
	 * @param string $plugin 플러그인명
	 * @return string $panel 관리자패널 HTML
	 */
	function getAdminPanel($plugin) {
		/**
		 * 플러그인을 불러온다.
		 */
		$mPlugin = $this->IM->getPlugin($plugin);
		
		/**
		 * 플러그인의 언어팩을 불러온다.
		 */
		$this->loadLanguage($plugin);
		
		/**
		 * 플러그인의 관리자용 자바스크립트나 스타일시트가 있을 경우 불러온다.
		 */
		if (is_file(__IM_PATH__.'/plugins/'.$plugin.'/admin/styles/style.css') == true) {
			$this->IM->addHeadResource('style',__IM_DIR__.'/plugins/'.$plugin.'/admin/styles/style.css');
		}
		
		if (is_file(__IM_PATH__.'/plugins/'.$plugin.'/admin/scripts/script.js') == true) {
			$this->IM->addHeadResource('script',__IM_DIR__.'/plugins/'.$plugin.'/admin/scripts/script.js');
		}
		
		/**
		 * 플러그인에 설정패널 메소드가 없으면 NULL 을 반환한다.
		 */
		if (method_exists($mPlugin,'getAdminPanel') == false) return null;
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		$this->IM->fireEvent('afterGetAdminPanel',$plugin,'admin');
		
		return $mPlugin->getAdminPanel();
	}
	
	/**
	 * [플러그인내부] 플러그인의 환경설정을 가져온다.
	 *
	 * @param string $key(옵션) 환경설정코드값, 없을경우 전체 환경설정값
	 * @return string $value 환경설정값
	 */
	function getConfig($key=null) {
		if ($key == null) return $this->pluginConfigs;
		elseif ($this->pluginConfigs == null || isset($this->pluginConfigs->$key) == false) return null;
		else return $this->pluginConfigs->$key;
	}
	
	/**
	 * [플러그인내부] 플러그인의 환경설정을 저장한다.
	 *
	 * @param string $key 환경설정코드값, 없을경우 전체 환경설정값
	 * @param object $value 변경할 환경설정값
	 * @return Plugin $this
	 */
	function setConfig($key,$value) {
		if ($this->pluginConfigs == null) return $this;
		$this->pluginConfigs->{$key} = $value;
		$this->IM->db()->update($this->table->plugin,array('configs'=>json_encode($this->pluginConfigs,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK)))->where('plugin',$this->loaded)->execute();
		
		return $this;
	}
	
	/**
	 * [코어/플러그인내부] 플러그인의 설치정보를 가져온다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return object $data 설치정보
	 */
	function getInstalled($plugin=null) {
		if ($plugin !== null) {
			return $this->IM->db('default')->select($this->table->plugin)->where('plugin',$plugin)->getOne();
		} else {
			return $this->pluginInstalled;
		}
	}
	
	/**
	 * [플러그인내부] 플러그인 템플릿의 package.json 정보를 가져온다.
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
	 * [플러그인내부] 플러그인의 모든 템플릿을 가져온다.
	 *
	 * @return object[] $templets 템플릿정보
	 */
	function getTemplets() {
		return $this->IM->getTemplets($this);
	}
	
	/**
	 * [사이트관리자] 플러그인 설정패널이 있는지 확인한다.
	 *
	 * @param string $plugin 플러그인명
	 * @param boolean $hasConfig
	 */
	function isConfigPanel($plugin) {
		/**
		 * 플러그인에 설정패널 설정패널 함수가 있으면 true 를 반환한다.
		 */
		$package = $this->getPackage($plugin);
		return isset($package->configs) == true && $package->configs != null;
	}
	
	/**
	 * [코어/플러그인내부] 플러그인이 설치가 되어 있는지 확인한다.
	 *
	 * @param string $plugin(옵션) 플러그인명 (코어에서 호출시 사용, 플러그인내부에서 호출시 호출한 플러그인명)
	 * @return boolean $installed 플러그인설치여부
	 */
	function isInstalled($plugin=null) {
		if ($plugin !== null) return $this->IM->db()->select($this->table->plugin)->where('plugin',$plugin)->has();
		else return $this->pluginInstalled !== null;
	}
	
	/**
	 * [사이트관리자] 플러그인의 설치조건이 만족하는지 확인한다.
	 *
	 * @param string $plugin 플러그인명
	 * @return boolean(string) $success
	 */
	function checkDependencies($plugin) {
		$package = $this->getPackage($plugin);
		
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
	 * 사이트관리자 기능을 사용하는 플러그인목록을 가져온다.
	 *
	 * @return object[] $plugins
	 */
	function getAdminPlugins() {
		$plugins = $this->IM->db()->select($this->table->plugin)->where('is_admin','TRUE')->get();
		return $plugins;
	}
	
	/**
	 * 크론작업을 사용한다고 설정된 플러그인목록을 가져온다.
	 *
	 * @return object[] $plugins
	 */
	function getCronPlugins() {
		$plugins = $this->IM->db()->select($this->table->plugin)->where('is_cron','TRUE')->get();
		
		return $plugins;
	}
	
	/**
	 * [코어/사이트관리자] 플러그인을 설치한다.
	 *
	 * @param string $plugin 설치할 플러그인명
	 * @param object $configs 사이트관리자로부터 넘어온 플러그인환경설정값
	 * @param string $database 설치할 데이터베이스 코드
	 * @param boolean $isUpdateSize(옵션, 기본값 true) 플러그인설치후 플러그인이 사용하는 용량을 업데이트할지 여부
	 * @return boolean $success
	 */
	function install($plugin,$configDatas=null,$database='default',$isUpdateSize=true) {
		/**
		 * 플러그인의 package.json 파일을 확인하고, 설치조건을 확인한다.
		 */
		$package = $this->getPackage($plugin);
		if ($package == null) return 'NOT_FOUND';
		if ($this->checkDependencies($plugin) == false) return 'DEPENDENCY_ERROR';
		
		/**
		 * 플러그인에서 사용하는 DB테이블을 생성한다.
		 */
		if (isset($package->databases) == true) {
			$schema = CreateDatabase($this->IM->db($database),$package->databases);
			if ($schema !== true) return $this->IM->getErrorText('DB_CREATE_TABLE_ERROR',$schema);
		}
		
		if (isset($package->configs) == true) {
			$configs = new stdClass();
			$templetFields = array();
			$installed = $this->isInstalled($plugin) == true ? json_decode($this->getInstalled($plugin)->configs) : new stdClass();
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
		
		if ($this->isInstalled($plugin) == false) {
			$this->IM->db()->insert($this->table->plugin,array(
				'plugin'=>$plugin,
				'hash'=>$this->getHash($plugin),
				'version'=>$package->version,
				'database'=>$database,
				'is_admin'=>isset($package->admin) == true && $package->admin === true ? 'TRUE' : 'FALSE',
				'configs'=>$configs,
				'targets'=>$targets
			))->execute();
			
			$mode = 'install';
		} else {
			$this->IM->db()->update($this->table->plugin,array(
				'hash'=>$this->getHash($plugin),
				'version'=>$package->version,
				'database'=>$database,
				'is_admin'=>isset($package->admin) == true && $package->admin === true ? 'TRUE' : 'FALSE',
				'configs'=>$configs,
				'targets'=>$targets
			))->where('plugin',$plugin)->execute();
			
			$mode = 'update';
		}
		
		if ($isUpdateSize === true) $this->updateSize($plugin);
		
		/**
		 * 이벤트리스너를 업데이트하고 이벤트를 발생시킨다.
		 */
		$this->addEventListener(true);
		$this->IM->fireEvent('afterInstall','plugin',$mode);
		
		return true;
	}
	
	/**
	 * [코어] 플러그인이 사용하고 있는 용량을 구한다.
	 *
	 * @param string $plugin 플러그인명
	 * @return object $size 용량
	 */
	function updateSize($plugin) {
		$package = $this->getPackage($plugin);
	
		$db_size = 0;
		if (isset($package->databases) == true) {
			$mPlugin = $this->IM->getPlugin($plugin);
			foreach ($package->databases as $tablename=>$schema) {
				$db_size+= $mPlugin->db()->size($tablename);
			}
		}
		
		$this->IM->db()->update($this->table->plugin,array('db_size'=>$db_size))->where('plugin',$plugin)->execute();
		
		$size = new stdClass();
		$size->db_size = $db_size;
		
		return $size;
	}
	
	/**
	 * 자바스크립트용 언어셋 파일을 호출한다.
	 * 언어셋은 기본적으로 PHP파일을 통해 사용되나 플러그인의 자바스크립트에서 언어셋이 필요할 경우 해당 함수를 호출하여 자바스크립트상에서 플러그인명.getLanguage() 함수로 언어셋을 불러올 수 있다.
	 *
	 * @param string $plugin 플러그인명
	 */
	function loadLanguage($plugin) {
		$package = $this->getPackage($plugin);
		$this->IM->loadLanguage('plugin',$plugin,$package->language);
	}
}
?>