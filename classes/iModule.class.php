<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodule.kr)
 *
 * iModule core class 로 모든 사이트 레이아웃 및 모듈, 위젯, 애드온은 이 class 를 통해 호출된다.
 * 이 class 는 index.php 파일에 의해 선언되며 iModule과 관련된 모든 파일에서 $IM 변수로 접근할 수 있다.
 * 
 * @file /classes/iModule.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0.160831
 */
class iModule {
	/**
	 * DB 관련 변수정의
	 *
	 * @private DB $DB DB에 접속하고 데이터를 처리하기 위한 DB class (@see /classes/DB.class.php)
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $DB;
	private $table; // store core database tables
	
	/**
	 * 사이트 주소에 의해 정의되는 사이트설정변수
	 * http://$domain/$language/$menu/$view/$idx
	 */
	public $domain;
	public $language;
	public $menu;
	public $page;
	public $view;
	public $idx;
	
	/**
	 * DB접근을 줄이기 위해 DB에서 불러온 데이터를 저장할 변수를 정의한다.
	 *
	 * @public object[] $sites : 사이트 설정값
	 * @public object[] $menus : 사이트별 모든 메뉴설정값
	 * @public object[] $pages : 사이트별 특정 메뉴에 해당하는 모든 페이지설정값
	 * @public object[] $modules : 불러온 모듈 클래스
	 */
	public $sites = array();
	public $menus = array();
	public $pages = array();
	public $modules = array();
	
	/**
	 * 언어셋을 정의한다.
	 * 
	 * @private object $lang 현재 사이트주소에서 설정된 언어셋
	 * @private object $oLang package.json 에 의해 정의된 기본 언어셋
	 */
	private $lang = null;
	private $oLang = null;
	
	/**
	 * 각 기능별 core class 를 정의한다.
	 *
	 * @public Event $Event 이벤트처리를 위한 Event class (@see /classes/Event.class.php)
	 * @public Addon $Addon addon을 정의하고 호출하기 위한 Addon class (@see /classes/Addon.class.php)
	 * @public Module $Module module을 정의하고 호출하기 위한 Module class (@see /classes/Module.class.php)
	 * @public Cache $Cache 캐싱처리를 위한 Cache class (@see /classes/Cache.class.php)
	 */
	public $Event;
	public $Addon;
	public $Module;
	public $Cache;
	
	private $initTime = 0;
	public $timezone; // server timezone
	
	/**
	 * 사이트 설정변수
	 * 현재 접속한 사이트주소에 따라 접근한 사이트관련 정보들을 정의한다.
	 *
	 * @public object $site 현재 사이트에 관련된 모든 RAW 정보
	 * @private string $siteTitle 웹브라우저에 표시되는 사이트제목
	 * @private string $siteDescription SEO를 위한 META 태그에 정의될 사이트소개
	 * @private string $siteCanonical SEO를 위한 현재 페이지에 접근할 수 있는 유니크한 사이트주소 (필수 GET 변수만 남겨둔 페이지 URL)
	 * @private string $siteImage OG META 태그를 위한 사이트 이미지 (각 모듈이나 애드온에서 페이지별로 변경할 수 있다.)
	 */
	public $site;
	private $siteTitle = null; // use for for meta tag in html head
	private $siteDescription = null; // use for meta tag in html head (For SEO)
	private $siteCanonical = null; // use for meta tag in html head (For SEO)
	private $siteImage = null; // use for meta tag in html head (For Social site preview image likes facebook or twitter and etc.)
	
	private $siteHeader = array(); // store all meta tag (likes script, css, meta, ... etc.)
	private $templetPath = null;
	private $templetDir = null;
	private $useTemplet = true;
	private $javascriptLanguages = array();
	private $webFont = array('FontAwesome');
	private $webFontDefault = null;
	
	/**
	 * class 선언
	 */
	function __construct() {
		global $_CONFIGS;
		
		/**
		 * 페이지 로딩시간을 구하기 위한 최초 마이크로타임을 기록한다.
		 */
		$this->initTime = $this->getMicroTime();
		
		/**
		 * cache처리를 위한 클래스를 정의한다.
		 */
		$this->Cache = new Cache($this);
		
		/**
		 * iModule 이 설치되어 있다면 각 기능별 core class 를 호출한다.
		 * 이 클래스는 인스톨러에서도 사용되기 인스톨러에서 호출되면 에러가 발생하는 기능 class 를 비활성화 한다.
		 */
		if ($_CONFIGS->installed === true) {
			$this->Event = new Event($this);
			$this->Addon = new Addon($this);
			$this->Module = new Module($this);
		}
		
		/**
		 * iModule core 에서 사용하는 DB 테이블 별칭 정의
		 * @see package.json 의 databases 참고
		 */
		$this->table = new stdClass();
		$this->table->site = 'site_table';
		$this->table->page = 'page_table';
		$this->table->article = 'article_table';
		
		/**
		 * 타임존 설정
		 * @todo 언젠가 사용할 예정
		 */
		$this->timezone = 'Asia/Seoul';
		date_default_timezone_set($this->timezone);
		
		/**
		 * 접속한 사이트주소 및 사이트변수 정의
		 */
		$this->site = null;
		$this->domain = isset($_SERVER['HTTP_HOST']) == true ? strtolower($_SERVER['HTTP_HOST']) : '';
		$this->language = Request('language');
		$this->menu = Request('menu') == null ? 'index' : Request('menu');
		$this->page = Request('page') == null ? null : Request('page');
		$this->view = Request('view') == null ? null : Request('view');
		$this->idx = Request('idx') == null ? null : Request('idx');
		
		/**
		 * 기본 사이트 자바스크립트 호출
		 *
		 * moment.js : 시간포맷을 위한 자바스크립트 라이브러리
		 * jquery.1.11.2.min.js : jQuery
		 * default.js : 기본 iModule 자바스크립트 라이브러리
		 */
		$this->addSiteHeader('script',__IM_DIR__.'/scripts/moment.js');
		$this->addSiteHeader('script',__IM_DIR__.'/scripts/jquery.1.11.2.min.js');
		$this->addSiteHeader('script',__IM_DIR__.'/scripts/default.js');
		
		/**
		 * iModule 이 설치되어 있고, 웹브라우저로 접근하였을 경우 현재 사이트 정의
		 */
		if ($_CONFIGS->installed === true && isset($_SERVER['HTTP_HOST']) == true) {
			$this->initSites();
		}
	}
	
	/**
	 * 인스톨과정에서 iModule core 클래스 정의가 필요할 경우 iModule core 를 정의한다.
	 *
	 * @return null
	 */
	function init() {
		global $_CONFIGS;
		
		$_CONFIGS->key = isset($_CONFIGS->key) == true ? $_CONFIGS->key : FileReadLine(__IM_PATH__.'/configs/key.config.php',1);
		$_CONFIGS->db = isset($_CONFIGS->db) == true ? $_CONFIGS->db : json_decode(Decoder(FileReadLine(__IM_PATH__.'/configs/db.config.php',1)));
		
		$this->Event = new Event($this); // ./classes/Event.class.php
		$this->Addon = new Addon($this); // ./classes/Addon.class.php
		$this->Module = new Module($this); // ./classes/Module.class.php
		$this->Cache = new Cache($this); // ./classes/Cache.class.php
	}
	
	/**
	 * DB클래스를 반환한다.
	 *
	 * @param string $code DB코드 (기본값 : default)
	 * @param string $prefix DB 테이블 앞에 고정적으로 사용되는 PREFIX 명 (정의되지 않을 경우 init.config.php 에서 정의된 __IM_DB_PREFIX__ 상수값을 사용한다.
	 * @return DB $DB
	 */
	function db($code='default',$prefix=null) {
		$db = new DB();
		$prefix = $prefix == null ? __IM_DB_PREFIX__ : $prefix;
		return $db->db($code,$prefix);
	}
	
	/**
	 * class 외부에서 DB 테이블 별칭으로 실제 테이블명을 가져온다.
	 *
	 * @param string $table DB 테이블 별칭
	 * @return string $tableName 실제 DB 테이블명
	 */
	function getTable($table) {
		return $this->table->$table;
	}
	
	/**
	 * 언어셋파일에 정의된 코드를 이용하여 사이트에 설정된 언어별로 텍스트를 반환한다.
	 * 코드에 해당하는 문자열이 없을 경우 1차적으로 package.json 에 정의된 기본언어셋의 텍스트를 반환하고, 기본언어셋 텍스트도 없을 경우에는 코드를 그대로 반환한다.
	 *
	 * @param string $code 언어코드
	 * @return string $language 실제 언어셋 텍스트
	 */
	function getLanguage($code) {
		if ($this->lang == null) {
			$package = json_decode(file_get_contents(__IM_PATH__.'/package.json'));
			if (file_exists(__IM_PATH__.'/languages/'.$this->language.'.json') == true) {
				$this->lang = json_decode(file_get_contents(__IM_PATH__.'/languages/'.$this->language.'.json'));
				if ($this->language != $package->language) {
					$this->oLang = json_decode(file_get_contents(__IM_PATH__.'/languages/'.$package->language.'.json'));
				}
			} else {
				$this->lang = json_decode(file_get_contents(__IM_PATH__.'/languages/'.$package->language.'.json'));
				$this->oLang = null;
			}
		}
		
		$temp = explode('/',$code);
		if (count($temp) == 1) {
			return isset($this->lang->$code) == true ? $this->lang->$code : ($this->oLang != null && isset($this->oLang->$code) == true ? $this->oLang->$code : $code);
		} else {
			$string = $this->lang;
			for ($i=0, $loop=count($temp);$i<$loop;$i++) {
				if (isset($string->{$temp[$i]}) == true) {
					$string = $string->{$temp[$i]};
				} else {
					$string = null;
					break;
				}
			}
			
			if ($string != null) return $string;
			if ($this->oLang == null) return $code;
			
			if ($string == null && $this->oLang != null) {
				$string = $this->oLang;
				for ($i=0, $loop=count($temp);$i<$loop;$i++) {
					if (isset($string->{$temp[$i]}) == true) $string = $string->{$temp[$i]};
					return $code;
				}
			}
			
			return $string;
		}
	}
	
	/**
	 * Get Cache class
	 *
	 * @return Cache $cache
	 */
	function cache() {
		return $this->Cache;
	}
	
	/**
	 * Fire Event, some module fired event (afterInitContext or afterDoProcess ... etc.)
	 * iModule core listen all events and transfer Event class.
	 *
	 * @param string $event event type (afterInitContext or afterDoProcess ... etc.)
	 * @param string $target event target class name (likes core, board, dataroom ... etc.)
	 * @param string $caller event target class's method name
	 * @param object $values some values for event listsners, event listeners(theo others module or addons) can be change this values.
	 * @param object $results some events fired doProcess method. $results params has processing result and datas. event listeners(theo others module or addons) can be change this values.
	 * @param string &$context if some events fired context method(likes doLayout, getContext), there's html code stored this parameter. event listeners(theo others module or addons) can be change this values.
	 */
	function fireEvent($event,$target,$caller,$values=null,$results=null,&$context=null) {
		$this->Event->fireEvent($event,$target,$caller,$values,$results,$context);
	}
	
	function getMicroTime() {
		$microtimestmp = explode(" ",microtime());
		return $microtimestmp[0]+$microtimestmp[1];
	}
	
	function getLoadTime() {
		return sprintf('%0.5f',$this->getMicroTime() - $this->initTime);
	}
	
	function getHost($isDir=false,$domain=null) {
		$url = isset($_SERVER['HTTPS']) == true ? 'https://' : 'http://';
		$url.= $domain == null ? $this->domain : $domain;
		if ($isDir == true) $url.= __IM_DIR__;
		
		return $url;
	}
	
	function getAttachmentPath() {
		return __IM_PATH__.'/attachments';
	}
	
	function getAttachmentDir() {
		return __IM_DIR__.'/attachments';
	}
	
	function getProcessUrl($module,$action,$params=array(),$isFullUrl=false) {
		$queryStrings = array();
		foreach ($params as $key=>$value) $queryStrings[] = $key.'='.urlencode($value);
		
		if ($isFullUrl == true) {
			$url = isset($_SERVER['HTTPS']) == true ? 'https://' : 'http://';
			$url.= $_SERVER['HTTP_HOST'].__IM_DIR__;
		} else {
			$url = '';
		}
		
		return $url.__IM_DIR__.'/'.$this->language.'/process/'.$module.'/'.$action.(count($queryStrings) > 0 ? '?'.implode('&',$queryStrings) : '');
	}
	
	/**
	 * Get page url
	 *
	 * @param string $menu menu code, If not string, follow this rule(null : used current menu code, false : return site index url)
	 * @param string $page page code, If not string, follow this rule(null : used current page code, false : return menu index url)
	 * @param string $view view code, used module(list, view, write, modify ... etc), If not string, follow this rule(null : used current view code, false : return page index url)
	 * @param int $number page number in list page or post idx in view page
	 * @param boolean $isFullUrl If this value is true, return full url include protocol and domain(host)
	 * @param string $domain If not exists, used current site domain
	 * @param string $language language code (likes ko, en ... etc), If not exists, used current site language
	 * @return string $url;
	 */
	function getUrl($menu=null,$page=null,$view=null,$number=null,$isFullUrl=false,$domain=null,$language=null) {
		$menu = $menu === null ? $this->menu : $menu;
		$page = $page === null && $menu == $this->menu ? $this->page : $page;
		$view = $view === null && $menu == $this->menu && $page == $this->page ? $this->view : $view;
		
		$domain = $domain == '*' ? $this->site->domain : $domain;
		if ($isFullUrl == true || $domain !== $this->site->domain) {
			$check = $this->db()->select($this->table->site)->where('domain',$domain)->getOne();
			if ($check == null) {
				$url = isset($_SERVER['HTTPS']) == true ? 'https://' : 'http://';
				$url.= ($domain === null ? $_SERVER['HTTP_HOST'] : $domain).__IM_DIR__;
			} else {
				$url = $check->is_ssl == 'TRUE' ? 'https://' : 'http://';
				$url.= ($domain === null ? $_SERVER['HTTP_HOST'] : $domain).__IM_DIR__;
			}
		} else {
			$url = __IM_DIR__;
		}
		$url.= '/'.($language == null ? $this->language : $language);
		if ($menu === null || $menu === false) return $url;
		$url.= '/'.$menu;
		if ($page === null || $page === false) return $url;
		$url.= '/'.$page;
		if ($view === null || $view === false) return $url;
		$url.= '/'.$view;
		if ($number === null || $number === false) return $url;
		$url.= '/'.$number;
		
		return $url;
	}
	
	
	/**
	 * Get module's direct url
	 *
	 * @param string $module module name
	 * @param string $container container name
	 * @param int $idx idx
	 */
	function getModuleUrl($module,$container=null,$idx=null,$isFullUrl=false,$domain=null,$language=null) {
		$domain = $domain == '*' ? $this->site->domain : $domain;
		if ($isFullUrl == true || $domain !== $this->site->domain) {
			$check = $this->db()->select($this->table->site)->where('domain',$domain)->getOne();
			if ($check == null) {
				$url = isset($_SERVER['HTTPS']) == true ? 'https://' : 'http://';
				$url.= ($domain === null ? $_SERVER['HTTP_HOST'] : $domain).__IM_DIR__;
			} else {
				$url = $check->is_ssl == 'TRUE' ? 'https://' : 'http://';
				$url.= ($domain === null ? $_SERVER['HTTP_HOST'] : $domain).__IM_DIR__;
			}
		} else {
			$url = __IM_DIR__;
		}
		
		$url.= '/'.($language == null ? $this->language : $language);
		$url.= '/module/'.$module;
		if ($container != null) $url.= '/'.$container;
		if ($idx != null) $url.= '/'.$idx;
		
		return $url;
	}
	
	function getQueryString($query=array(),$queryString=null) {
		$queryString = $queryString == null ? $_SERVER['QUERY_STRING'] : $queryString;
		$query = array_merge(array('menu'=>'','page'=>'','view'=>'','idx'=>'','p'=>'','language'=>''),$query);
		
		if (isset($_SERVER['REDIRECT_URL']) == true && preg_match('/\/module\/([^\/]+)/',$_SERVER['REDIRECT_URL']) == true) $query = array_merge(array('container'=>'','idx'=>'','language'=>''),$query);
		$querys = explode('&',$queryString);
		
		for ($i=0, $total=count($querys);$i<$total;$i++) {
			$temp = explode('=',$querys[$i]);
			if (isset($temp[1]) == true) {
				$arg[$temp[0]] = $temp[1];
			}
		}
	
		//replace
		foreach ($query as $key=>$value) {
			$arg[$key] = $value;
		}
	
		//sum
		$queryString = '';
	
		foreach ($arg as $key=>$value) {
			if (strlen($value) > 0) {
				$queryString.= $queryString == '' ? '?' : '&';
				$queryString .= $key."=".$value;
			}
		}
		
		return $queryString;
	}
	
	/**
	 * Init all sites
	 */
	function initSites() {
		$this->sites = $this->db()->select($this->table->site)->orderBy('sort','asc')->get();
		
		if ($this->db()->select($this->table->site)->where('domain',$this->domain)->has() == false) {
			$isAlias = false;
			for ($i=0, $loop=count($this->sites);$i<$loop;$i++) {
				if ($this->sites[$i]->alias == '') continue;
				
				$domains = explode(',',$this->sites[$i]->alias);
				for ($j=0, $loopj=count($domains);$j<$loopj;$j++) {
					if ($domains[$j] == $this->domain) {
						$this->domain = $this->sites[$i]->domain;
						$isAlias = true;
						break;
					}
					
					if (preg_match('/\*\./',$domains[$j]) == true) {
						$aliasToken = explode('.',$domains[$j]);
						$domainToken = explode('.',$this->domain);
						$isMatch = true;
						while (count($aliasToken) > 0) {
							$token = array_pop($aliasToken);
							if ($token != '*' && $token != array_pop($domainToken)) {
								$isMatch = false;
							}
						}
						
						if ($isMatch == true) {
							$this->domain = $this->sites[$i]->domain;
							$isAlias = true;
							break;
						}
					}
				}
			}
			
			if ($isAlias == false) {
				$this->printError('SITE_NOT_FOUND');
			}
		}
		
		if ($this->language === null) {
			$site = $this->db()->select($this->table->site)->where('domain',$this->domain)->where('is_default','TRUE')->getOne();
			$this->language = $site != null ? $site->language : null;
		} else {
			$site = $this->db()->select($this->table->site)->where('domain',$this->domain)->where('language',$this->language)->getOne();
			if ($site == null) {
				$site = $this->db()->select($this->table->site)->where('domain',$this->domain)->where('is_default','TRUE')->getOne();
				$this->language = $site != null ? $site->language : null;
			}
		}
		
		if ($site == null) $this->printError('LANGUAGE_NOT_FOUND');
		
		// URL redirect
		if (preg_match('/\/(addons|admin|api|modules|process|templets)(\/[a-z]+)?\/index\.php/',$_SERVER['PHP_SELF']) == false) {
			if (($site->is_ssl == 'TRUE' && empty($_SERVER['HTTPS']) == true) || $_SERVER['HTTP_HOST'] != $site->domain || Request('language') != $site->language) {
				header("location:".($site->is_ssl == 'TRUE' ? 'https://' : 'http://').$site->domain.__IM_DIR__.'/'.$this->language.'/');
				exit;
			}
		}

		for ($i=0, $loop=count($this->sites);$i<$loop;$i++) {
			$this->menus[$this->sites[$i]->domain.'@'.$this->sites[$i]->language] = array();
			$this->pages[$this->sites[$i]->domain.'@'.$this->sites[$i]->language] = array();
		}
		
		// Init all menus
		$pages = $this->db()->select($this->table->page)->orderBy('sort','asc')->get();
		for ($i=0, $loop=count($pages);$i<$loop;$i++) {
			if ($pages[$i]->page == '') {
				$pages[$i]->context = $pages[$i]->context == '' ? null : json_decode($pages[$i]->context);
				if ($pages[$i]->type == 'MODULE') $pages[$i]->context->config = isset($pages[$i]->context->config) == true ? $pages[$i]->context->config : null;
				$pages[$i]->description = isset($pages[$i]->description) == true && $pages[$i]->description ? $pages[$i]->description : null;
				$pages[$i]->image = isset($pages[$i]->image) == true && $pages[$i]->image ? __IM_DIR__.'/attachment/view/'.$pages[$i]->image.'/preview.png' : null;
				$this->menus[$pages[$i]->domain.'@'.$pages[$i]->language][] = $pages[$i];
				$this->pages[$pages[$i]->domain.'@'.$pages[$i]->language][$pages[$i]->menu] = array();
			}
		}
		
		for ($i=0, $loop=count($pages);$i<$loop;$i++) {
			if ($pages[$i]->page != '') {
				$pages[$i]->context = $pages[$i]->context == '' ? null : json_decode($pages[$i]->context);
				$pages[$i]->description = isset($pages[$i]->description) == true && $pages[$i]->description ? $pages[$i]->description : null;
				$pages[$i]->image = isset($pages[$i]->image) == true && $pages[$i]->image ? __IM_DIR__.'/attachment/view/'.$pages[$i]->image.'/preview.png' : null;
				if ($pages[$i]->type == 'MODULE') $pages[$i]->context->config = isset($pages[$i]->context->config) == true ? $pages[$i]->context->config : null;
				$this->pages[$pages[$i]->domain.'@'.$pages[$i]->language][$pages[$i]->menu][] = $pages[$i];
			}
		}
	}
	
	/**
	 * Get site configures
	 *
	 * @param string $domain(optional) Site HTTP_HOST
	 * @param string $language(optional) Site language
	 * @param object $lesson Lesson post data from the form
	 * @return object $domain param not exists return array
	 */
	function getSites($domain=null,$language=null) {
		if ($domain == null && $language == null) return $this->sites;
		
		$sites = array();
		for ($i=0, $loop=count($this->sites);$i<$loop;$i++) {
			if ($this->sites[$i]->domain == $domain && $this->sites[$i]->language == $language) return $this->sites[$i];
			if ($domain == null && $this->sites[$i]->language == $language) $sites[] = $this->sites[$i];
			if ($language == null && $this->sites[$i]->domain == $domain) $sites[] = $this->sites[$i];
		}
		
		return count($sites) > 0 ? $sites : null;
	}
	
	/**
	 * Get site menus (1st depth)
	 *
	 * @param string $menu(optional) if this values exists and find this menu code, return that menu object not menus array.
	 * @param string $domain(optional) if search for the others(the others domain) site menu, using this param.
	 * @return object[] $menus all or one menu object
	 */
	function getMenus($menu=null,$domain=null) {
		$domain = $domain === null ? $this->domain : $domain;
		if (count(explode('@',$domain)) == 1) $domain = $domain.'@'.$this->language;
		if (empty($this->menus[$domain]) == true) return $menu == null ? array() : null;
		if ($menu == null) return $this->menus[$domain];
		
		for ($i=0, $loop=count($this->menus[$domain]);$i<$loop;$i++) {
			if ($this->menus[$domain][$i]->menu == $menu) return $this->menus[$domain][$i];
		}
		return null;
	}
	
	function getPages($menu=null,$page=null,$domain=null) {
		$domain = $domain === null ? $this->domain : $domain;
		if (count(explode('@',$domain)) == 1) $domain = $domain.'@'.$this->language;
		if (empty($this->menus[$domain]) == true) return $page == null ? array() : null;
		if ($menu == null) return $this->pages[$domain];
		if ($page == null) return $this->pages[$domain][$menu];	
		$size=(isset($this->pages[$domain][$menu]))?count($this->pages[$domain][$menu]):0;
		
		for ($i=0, $loop=$size;$i<$loop;$i++) {
			if ($this->pages[$domain][$menu][$i]->page == $page) return $this->pages[$domain][$menu][$i];
		}
		return null;
	}
	
	function getFooterPages() {
		$sorts = array();
		$pages = $this->db()->select($this->table->page)->where('domain',$this->domain)->where('language',$this->language)->where('is_footer','TRUE')->get();
		for ($i=0, $loop=count($pages);$i<$loop;$i++) {
			if ($pages[$i]->page) {
				$menu = $this->db()->select($this->table->page)->where('domain',$this->domain)->where('language',$this->language)->where('menu',$pages[$i]->menu)->where('page','')->getOne();
				$sorts[$menu->sort * 100 + $pages[$i]->sort + 1] = $pages[$i];
			} else {
				$sorts[$pages[$i]->sort * 100] = $pages[$i];
			}
		}
		
		ksort($sorts);
		$footers = array();
		foreach ($sorts as $page) {
			$page->url = $this->getUrl($page->menu,$page->page == '' ? false : $page->page);
			$footers[] = $page;
		}
		
		return $footers;
	}
	
	function getPageCountInfo($page) {
		if ($page->type == 'MODULE') {
			$module = $this->getModule($page->context->module);
			if (method_exists($module,'getCountInfo') == true) {
				return $module->getCountInfo($page->context->context,$page->context->config);
			} else {
				return null;
			}
		}
		
		return null;
	}
	
	function setView($view) {
		$this->view = $view;
	}
	
	function setIdx($idx) {
		$this->view = $view;
	}
	
	/**
	 * Removed site default templet
	 * Anywhere call this method, you can remove all site templet's design (include site header and footer).
	 */
	function removeTemplet() {
		$this->useTemplet = false;
	}
	
	/**
	 * Setting for ExtJS Library, Basically, Admin page is using ExtJs library
	 * ExtJs library doesn't use header cache
	 */
	function loadExtJs() {
		$this->addSiteHeader('style',__IM_DIR__.'/styles/extjs.css');
		$this->addSiteHeader('script',__IM_DIR__.'/scripts/extjs.6.0.1.js');
		$this->addSiteHeader('script',__IM_DIR__.'/scripts/extjs/moimz.js');
		// load ExtJs locale
		if (file_exists(__IM_PATH__.'/scripts/extjs/'.$this->language.'/ext-locale-'.$this->language.'.js') == true) {
			$this->addSiteHeader('script',__IM_DIR__.'/scripts/extjs/'.$this->language.'/ext-locale-'.$this->language.'.js');
		}
	}
	
	/**
	 * Load language for Javascript (module only)
	 *
	 * @param string $module (loaded module name)
	 * @param string $langaugeCode If not exists, use site language setting
	 */
	function loadLangaugeJs($module,$language=null) {
		$language = $language != null ? $language : $this->language;
		$package = $this->Module->getPackage($module);
		$this->javascriptLanguages[] = $module.'@'.$language.'@'.$package->language;
	}
	
	/**
	 * Get module class
	 * If core already loaded request module, using cache.
	 *
	 * @param string $modulename module name (the folder name in ./modules folder is module name.)
	 * @return object $module
	 */
	function getModule($module) {
		// Not exists cache, means iModule core never loaded this module before.
		if (isset($this->modules[$module]) == false) {
			$class = new Module($this); // make new module object
			$this->modules[$module] = $class->load($module); // call module by Module class, and store cache.
		}
		
		if ($this->modules[$module] === false) $this->printError('LOAD_MODULE_FAIL : '.$module); // Not exists requested module, print error.
		return $this->modules[$module];
	}
	
	function getModulePath($module) {
		return __IM_PATH__.'/modules/'.$module;
	}
	
	/**
	 * Get widget
	 *
	 * @param string $widgetname widget name (widgetname : find ./widgets folder, modulename/widgetname(using slash) : find ./modules/modulename/widgets folder)
	 * @return object $widget
	 */
	function getWidget($widget) {
		$class = new Widget($this);
		return $class->load($widget);
	}
	
	function getSite() {
		if ($this->site != null) return $this->site;
		
		$this->site = clone $this->getSites($this->domain,$this->language);
		
		$this->site->logo = json_decode($this->site->logo);
		$this->site->emblem = $this->site->emblem == -1 ? __IM_DIR__.'/images/logo/emblem.png' : ($this->site->emblem == 0 ? null : __IM_DIR__.'/attachment/view/'.$this->site->emblem.'/emblem.png');
		$this->site->favicon = $this->site->favicon == -1 ? __IM_DIR__.'/images/logo/favicon.ico' : ($this->site->favicon == 0 ? null : __IM_DIR__.'/attachment/view/'.$this->site->favicon.'/favicon.ico');
		$this->site->image = $this->site->image == 0 ? null : __IM_DIR__.'/attachment/view/'.$this->site->image.'/preview.jpg';
		$this->site->maskicon = json_decode($this->site->maskicon);
		$this->site->maskicon->icon = $this->site->maskicon->icon == -1 ? __IM_DIR__.'/images/logo/maskicon.svg' : ($this->site->maskicon->icon == 0 ? null : __IM_DIR__.'/attachment/view/'.$this->site->maskicon->icon.'/maskicon.svg');
		$this->site->maskicon->color = $this->site->maskicon->icon == null ? null : $this->site->maskicon->color;
		$this->site->description = $this->site->description ? $this->site->description : null;
		$this->site->templetConfigs = json_decode($this->site->templetConfigs);
		$this->language = $this->language == null ? $this->site->language : $this->language;
		
		
		return $this->site;
	}
	
	/**
	 * 현재 접속한 페이지의 URL 을 가져온다.
	function getCurrentURL($menu=false,$page=false,$view=false,$isParameter=false) {
		$menu = $menu == false ? $this->menu : $menu;
		$page = $page == false ? $this->page : $page;
		$view = $view == false ? $this->view : $view;
		
		$baseURL = '';
		if ($menu == '') return '/';
		else $baseURL.= '/'.$menu;
		
		if ($page == '') return $baseURL;
		else $baseURL.= '/'.$page;
		
		if ($view == '') return $baseURL;
		else $baseURL.= '/'.$view;
		
		if ($isParameter == true) {
			$baseURL.= '';
		}
		
		return $baseURL;
	}
	*/
	
	/**
	 * 사이트 템플릿별 설정된 값을 가져온다.
	 * 해당 설정들은 템플릿 폴더안의 package.json 에 의해 정의되고, 사이트관리자를 통해 설정값이 입력된다.
	 *
	 * @param string $key 설정코드
	 * @return string $value 설정된 값 (설정된 값이 없을 경우 공백이 반환된다.)
	 */
	function getSiteTempletConfig($key) {
		if ($this->site == null) $this->getSite();
		return $this->site->templetConfigs != null && isset($this->site->templetConfigs->{$key}) == true ? $this->site->templetConfigs->{$key} : '';
	}
	
	function getSiteLogo($type='default') {
		if (in_array($type,array('default','footer')) == true && $this->site->logo->$type == -1) return __IM_DIR__.'/images/logo/'.$type.'.png';
		elseif (empty($this->site->logo->$type) == true) return $type == 'default' ? null : $this->getSiteLogo('default');
		elseif ($this->site->logo->$type == 0) return null;
		return __IM_DIR__.'/attachment/view/'.$this->site->logo->$type.'/logo.png';
	}
	
	function getSiteEmblem() {
		return $this->site->emblem;
	}
	
	/**
	 * Get site title for html title tag
	 *
	 * @return string $siteTitle
	 */
	function getSiteTitle() {
		if ($this->siteTitle == null) {
			$site = $this->getSite();
			$this->siteTitle = $site->title;
		}
		
		return $this->siteTitle;
	}
	
	/**
	 * Set site title for html title tag
	 *
	 * @param string $title
	 * @param boolean $isSiteTitle If this valus is true, using subtitle (ex : siteTitle - subtitle)
	 */
	function setSiteTitle($title,$isSiteTitle=true) {
		$this->siteTitle = $isSiteTitle == true ? $this->site->title.' - '.$title : $title;
	}
	
	function getSiteDescription() {
		if ($this->siteDescription !== null) return $this->siteDescription;
		
		if ($this->menu != 'index' && $this->menu != 'account') {
			$menu = $this->getMenus($this->menu);
			$page = $this->page !== null ? $this->getPages($this->menu,$this->page) : null;
			$description = $page !== null && $page->description !== null ? $page->description : $page !== null ? $menu->description : null;
			return $description !== null ? $description : $this->site->description;
		}
		return $this->site->description;
	}
	
	function setSiteDescription($description) {
		$this->siteDescription = $description;
	}
	
	function getSiteCanonical() {
		return $this->siteCanonical !== null ? $this->siteCanonical : $this->getHost(false).$_SERVER['REQUEST_URI'];
	}
	
	function setSiteCanonical($canonical) {
		$this->siteCanonical = $canonical;
	}
	
	function getSiteImage($isFullUrl=false) {
		$url = $isFullUrl == true ? isset($_SERVER['HTTPS']) == true ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'] : '';
		
		if ($this->siteImage !== null) return $url.$this->siteImage;
		
		if ($this->menu != 'index' && $this->menu != 'account') {
			$page = $this->page ? $this->getPages($this->menu,$this->page) : $this->getMenus($this->menu);
			return $page->image !== null ? $url.$page->image : $url.$this->site->image;
		}
		return $url.$this->site->image;
	}
	
	function setSiteImage($image) {
		$this->siteImage = $image;
	}
	
	/**
	 * Get site header
	 *
	 * @return string $header html tag for <head> tag
	 * @todo use header cache
	 */
	function getSiteHeader() {
		if (count($this->javascriptLanguages) > 0) {
			$this->addSiteHeader('script',__IM_DIR__.'/scripts/language.js.php?languages='.implode(',',$this->javascriptLanguages));
		}
		$this->addSiteHeader('script',__IM_DIR__.'/scripts/php2js.js.php?language='.$this->language.'&menu='.($this->menu != null ? $this->menu : '').'&page='.($this->page != null ? $this->page : '').'&view='.($this->view != null ? $this->view : ''));
		$this->addSiteHeader('style',__IM_DIR__.'/styles/font.css.php?language='.$this->language.'&font='.implode(',',$this->webFont).($this->webFontDefault != null ? '&default='.$this->webFontDefault : ''));
		
		return implode(PHP_EOL,$this->siteHeader).PHP_EOL;
	}
	
	function getTempletPath() {
		if ($this->templetPath == null) {
			$site = $this->getSite();
			$this->templetPath = __IM_PATH__.'/templets/'.$site->templet;
		}
		
		return $this->templetPath;
	}
	
	function getTempletDir() {
		if ($this->templetDir == null) {
			$site = $this->getSite();
			$this->templetDir = __IM_DIR__.'/templets/'.$site->templet;
		}
		
		return $this->templetDir;
	}
	
	function setArticle($module,$context,$type,$idx,$reg_date) {
		$check = $this->db()->select($this->table->article)->where('module',$module)->where('type',$type)->where('idx',$idx)->get();
		if ($check == null) {
			$this->db()->insert($this->table->article,array('module'=>$module,'context'=>$context,'type'=>$type,'idx'=>$idx,'reg_date'=>$reg_date,'update_date'=>$reg_date))->execute();
		} else {
			$this->db()->update($this->table->article,array('context'=>$context,'update_date'=>$reg_date))->where('module',$module)->where('type',$type)->where('idx',$idx)->execute();
		}
	}
	
	function deleteArticle($module,$type,$idx) {
		$this->db()->delete($this->table->article)->where('module',$module)->where('type',$type)->where('idx',$idx)->execute();
	}
	
	function resetArticle() {
		$this->db()->delete($this->table->article)->execute();
		$this->Module->resetArticle();
	}
	
	/**
	 * Add webfont lists
	 * @param string $font font name
	 * @param boolean $isDefault using default
	 */
	function addWebFont($font,$isDefault=false) {
		if (in_array($font,$this->webFont) == false) $this->webFont[] = $font;
		if ($isDefault == true) $this->webFontDefault = $font;
	}
	
	/**
	 * Add site's head tag
	 *
	 * @param string $type type of meta tag (ex : style, meta, script, link...)
	 * @param string[] $value values of tag's content (tagname => tagcontent)
	 */
	function addSiteHeader($type,$value) {
		$tag = null;
		
		switch ($type) {
			case 'style' :
				$path = parse_url($value);
				if (isset($path['host']) == true) {
					$tag = '<link rel="stylesheet" href="'.$value.'" type="text/css">';
				} else {
					if (is_file(__IM_PATH__.$path['path']) == true) {
						$value = $path['path'].(isset($path['query']) == true ? '?'.$path['query'].'&' : '?').'v='.filemtime(__IM_PATH__.$path['path']);
						$tag = '<link rel="stylesheet" href="'.$value.'" type="text/css">';
					}
				}
				break;
				
			case 'script' :
				$path = parse_url($value);
				if (isset($path['host']) == true) {
					$tag = '<script src="'.$value.'"></script>';
				} else {
					if (is_file(__IM_PATH__.$path['path']) == true) {
						$value = $path['path'].(isset($path['query']) == true ? '?'.$path['query'].'&' : '?').'v='.filemtime(__IM_PATH__.$path['path']);
						$tag = '<script src="'.$value.'"></script>';
					}
				}
				break;
				
			default :
				$tag = '<';
				$tag.= $type;
				foreach ($value as $tagName=>$tagValue) {
					$tag.= ' '.$tagName.'="'.$tagValue.'"';
				}
				$tag.= '>';
		}
		
		if ($tag != null && in_array($tag,$this->siteHeader) == false) $this->siteHeader[] = $tag;
	}
	
	function printError($code,$message='') {
		if ($this->site == null) {
			echo 'NOT_FOUND_SITE';
			if ($code) echo ' : '.$code;
		} else {
			echo $this->getHeader();
			echo '<b>ERROR : </b>'.$code;
			echo $this->getFooter();
		}
		exit;
	}
	
	/**
	 * Print html header
	 *
	 * @return string $headerHTML
	 */
	function getHeader() {
		if (defined('__IM_HEADER_INCLUDED__') == true) return;
		$site = $this->getSite();
		
		$this->addSiteHeader('style',__IM_DIR__.'/styles/default.css');
		
		$IM = $this;
		$values = new stdClass();
		$values->header = '';
		
		if ($this->getSiteDescription()) $this->addSiteHeader('meta',array('name'=>'description','content'=>$this->getSiteDescription()));
		$this->addSiteHeader('link',array('rel'=>'canonical','href'=>$this->getSiteCanonical()));
		
		if ($this->site->emblem !== null) {
			$this->addSiteHeader('link',array('rel'=>'apple-touch-icon','sizes'=>'57x57','href'=>$this->site->emblem));
			$this->addSiteHeader('link',array('rel'=>'apple-touch-icon','sizes'=>'114x114','href'=>$this->site->emblem));
			$this->addSiteHeader('link',array('rel'=>'apple-touch-icon','sizes'=>'72x72','href'=>$this->site->emblem));
			$this->addSiteHeader('link',array('rel'=>'apple-touch-icon','sizes'=>'144x144','href'=>$this->site->emblem));
		}
		
		if ($this->site->favicon !== null) {
			$this->addSiteHeader('link',array('rel'=>'shortcut icon','type'=>'image/x-icon','href'=>$this->site->favicon));
		}
		
		if ($this->site->maskicon->icon !== null) {
			$this->addSiteHeader('link',array('rel'=>'mask-icon','href'=>$this->site->maskicon->icon,'color'=>$this->site->maskicon->color));
		}
		
		ob_start();
		if ($this->useTemplet == false || file_exists($this->getTempletPath().'/header.php') == false) {
			INCLUDE __IM_PATH__.'/includes/header.php';
		} else {
			INCLUDE $this->getTempletPath().'/header.php';
		}
		
		$values->header = ob_get_contents();
		ob_end_clean();
		
		return $values->header;
	}
	
	/**
	 * Print html footer
	 *
	 * @return string $footerHTML
	 */
	function getFooter() {
		if (defined('__IM_FOOTER_INCLUDED__') == true) return;
		$site = $this->getSite();
		
		$IM = $this;
		$values = new stdClass();
		$values->footer = '';
		
		ob_start();
		if ($this->useTemplet == false || file_exists($this->getTempletPath().'/footer.php') == false) {
			INCLUDE __IM_PATH__.'/includes/footer.php';
		} else {
			INCLUDE $this->getTempletPath().'/footer.php';
		}
		
		$values->footer = ob_get_contents();
		ob_end_clean();
		
		return $values->footer;
	}
	
	/**
	 * 특정 페이지의 컨텍스트를 가져온다.
	 *
	 * @param string $menu 메뉴명 (1차 메뉴)
	 * @param string $page 페이지명 (2차 메뉴)
	 * @return string $context 컨텍스트 HTML
	 */
	function getPageContext($menu,$page) {
		/**
		 * 페이지명이 NULL 일 경우 1차 메뉴의 설정을 가져오고 페이지명이 있을 경우 2차 메뉴의 설정을 가져온다.
		 */
		$config = $page == null ? $this->getMenus($menu) : $this->getPages($menu,$page);
		if ($config == null) return null;
		
		/**
		 * 가져올 컨텍스트에 따라 웹브라우저에서 표시될 사이트제목을 설정한다.
		 */
		$this->setSiteTitle($config->title);
		
		/**
		 * 컨텍스트 종류가 PAGE 일 경우로 이 값은 1차 메뉴에서만 설정가능하다.
		 * 1차 메뉴(menu)에 접근시 2차 메뉴 중 설정된 2차 메뉴(page)의 컨텍스트를 가져온다.
		 * $config->context->page : 불러올 2차 메뉴(page)명
		 */
		if ($config->type == 'PAGE') {
			return $this->getPageContext($menu,$config->context->page);
		}
		
		/**
		 * 컨텍스트 종류가 EXTERNAL 일 경우
		 * 서버내 특정 디렉토리에 존재하는 PHP 파일 내용을 가지고 온다.
		 * $config->context->external : 불러올 외부 PHP 파일명
		 */
		if ($config->type == 'EXTERNAL') {
			return $this->getExternalContext($config->context->external);
		}
		
		/**
		 * 컨텍스트 종류가 WIDGET 일 경우
		 * 위젯마법사를 이용하여 위젯만으로 이루어진 페이지에 대한 컨텍스트를 가지고 온다.
		 * $page->context->widget : 위젯마법사를 이용해 만들어진 위젯레이아웃 코드
		 */
		if ($config->type == 'WIDGET') {
			return $this->getWidgetContext($page->context->widget);
		}
		
		/**
		 * 컨텍스트 종류가 MODULE 일 경우
		 * 설정된 모듈 클래스를 선언하고 모듈클래스내의 getContext 함수를 호출하여 컨텍스트를 가져온다.
		 * $page->context->module : 불러올 모듈명
		 * $page->context->context : 해당 모듈에서 불러올 컨텍스트 종류
		 * $page->context->widget : 해당 모듈에 전달할 환경설정값 (예 : 템플릿명 등)
		 */
		if ($config->type == 'MODULE') {
			return $this->getModule($config->context->module)->getContext($config->context->context,$config->context->config);
		}
		
		return null;
	}
	
	/**
	 * 컨텍스트 HTML 코드를 사이트 레이아웃에 담는다.
	 * 레이아웃은 사이트템플릿 폴더의 layouts 폴더에 있는 레이아웃 파일을 사용한다.
	 *
	 * @param string $menu 메뉴명 (1차 메뉴)
	 * @param string $page 페이지명 (2차 메뉴)
	 * @param string $context 컨텍스트 HTML
	 * @return string $layout 레이아웃 HTML
	 */
	function getContextLayout($menu,$page,$context) {
		/**
		 * 페이지명이 NULL 일 경우 1차 메뉴의 설정을 가져오고 페이지명이 있을 경우 2차 메뉴의 설정을 가져온다.
		 */
		$config = $page == null ? $this->getMenus($menu) : $this->getPages($menu,$page);
		if ($config == null) return null;
		
		/**
		 * 사이트 레이아웃을 사용하지 않는다고 선언된 경우 ($this->useTemplet 값이 false) 컨텍스트 HTML 코드를 그대로 반환한다.
		 */
		if ($this->useTemplet == false) return $context;
		
		/**
		 * 사이트 템플릿의 layouts 폴더에 정의된 레이아웃 파일이 없을 경우 에러메세지를 출력한다.
		 */
		if (is_file($this->getTempletPath().'/layouts/'.$config->layout.'.php') == false) return $this->printError('NOT_FOUND_LAYOUT : '.$config->layout);
		
		/**
		 * 레이아웃 파일에서 iModule core 에 접근할 수 있도록 $IM 변수를 선언한다.
		 */
		$IM = $this;
		
		/**
		 * 레이아웃 파일을 불러온다.
		 * 레이아웃 파일안에서 $context 변수를 이용하여 페이지의 컨텍스트 HTML 코드가 나타날 곳을 정의할 수 있다.
		 */
		ob_start();
		INCLUDE $this->getTempletPath().'/layouts/'.$config->layout.'.php';
		$layout = ob_get_contents();
		ob_end_clean();
		
		return $layout;
	}
	
	/**
	 * 외부 PHP 파일내용을 가져온다.
	 *
	 * @param string $external 외부 PHP 파일명
	 * @return string $context 컨텍스트 HTML
	 */
	function getExternalContext($external) {
		/**
		 * 외부 PHP파일에서 iModule core 에 접근할 수 있도록 $IM 변수를 선언한다.
		 */
		$IM = $this;
		$context = '';
		
		/**
		 * 파일명이 @ 로 시작할 경우 사이트 템플릿의 externals 폴더에서 파일을 찾는다.
		 * 예를 들어 현재 사이트의 템플릿명이 default 이고 $external 값이 @foo.php 라면,
		 * /templets/default/externals/foo.php 파일을 불러온다.
		 */
		if (preg_match('/^@/',$external) == true) {
			$templetPath = $this->getTempletPath().'/externals';
			$templetDir = $this->getTempletDir().'/externals';
			
			if (file_exists($this->getTempletPath().'/externals/'.preg_replace('/^@/','',$external)) == true) {
				ob_start();
				INCLUDE $this->getTempletPath().'/externals/'.preg_replace('/^@/','',$external);
				$context = ob_get_contents();
				ob_end_clean();
			}
		}
		/**
		 * 파일명이 @ 로 시작하지 않을 경우 /externals 폴더에서 파일을 찾는다.
		 */
		else {
			$templetPath = __IM_PATH__.'/externals';
			$templetDir = __IM_DIR__.'/externals';
			
			if (file_exists(__IM_PATH__.'/externals/'.$external) == true) {
				ob_start();
				INCLUDE __IM_PATH__.'/externals/'.$external;
				$context = ob_get_contents();
				ob_end_clean();
			}
		}
		
		return $context;
	}
	
	/**
	 * 위젯마법사 설정값으로 위젯 HTML 을 가지고 온다.
	 *
	 * @param object $widgets 위젯마법사의 설정값
	 * @return string $context 컨텍스트 HTML
	 */
	function getWidgetContext($widgets) {
		ob_start();
		
		foreach ($widgets as $row) {
			echo '<div class="row">'.PHP_EOL;
			foreach ($row as $col) {
				echo '<div class="col-sm-'.$col->col.'">'.PHP_EOL;
				
				$widget = $this->getWidget($col->widget)->setTemplet($col->templet);
				foreach ($col->values as $key=>$value) {
					$widget->setValue($key,$value);
				}
				$widget->doLayout();
				
				echo '</div>'.PHP_EOL;
			}
			echo '</div>'.PHP_EOL;
		}
		
		$widget = ob_get_contents();
		ob_end_clean();
		
		return $widget;
	}
	
	/**
	 * Parse permission string
	 *
	 * @param string $permssionString
	 * @return boolean $hasPermission
	 */
	function parsePermissionString($permissionString) {
		$member = $this->getModule('member')->getMember();
		if ($member->type == 'ADMINISTRATOR') return true;
		
		// replace code
		if ($member->idx == 0) {
			$permissionString = str_replace('{$member.level}','0',$permissionString);
			$permissionString = str_replace('{$member.type}',"'GURST'",$permissionString);
			$permissionString = str_replace('{$member.email}',"''",$permissionString);
			$permissionString = str_replace('{$member.label}',"''",$permissionString);
		} else {
			$permissionString = str_replace('{$member.level}',$member->level->level,$permissionString);
			$permissionString = str_replace('{$member.type}',$member->type,$permissionString);
			$permissionString = str_replace('{$member.email}',$member->email,$permissionString);
			
			if (preg_match_all('/\{\$member\.label\}(.*?)(==|!=)(.*?)\'(.*?)\'/',$permissionString,$match,PREG_SET_ORDER) == true) {
				for ($i=0, $loop=count($match);$i<$loop;$i++) {
					$string = 'in_array(\''.$match[$i][4].'\',$member->label) '.$match[$i][2].' true';
					$permissionString = str_replace($match[$i][0],$string,$permissionString);
				}
			}
		}
		
		if (@eval('return '.$permissionString.';') == true) return true;
		else return false;
	}
	
	/**
	 * Check permission string
	 *
	 * @param string $permssionString
	 * @return boolean/string $success or $errorString
	 */
	function checkPermissionString($permissionString) {
		// replace code
		$permissionString = str_replace('{$member.level}',"0",$permissionString);
		$permissionString = str_replace('{$member.type}',"'MEMBER'",$permissionString);
		$permissionString = str_replace('{$member.label}',"'default'",$permissionString);
		$permissionString = str_replace('{$member.email}',"'email@email.com'",$permissionString);
		
		// check unknown code
		if (preg_match('/\{(.*?)\}/',$permissionString,$match) == true) {
			return str_replace('{code}',$match[0],$this->getLanguage('error/permissionString/unknownCode'));
		}
		
		// check doubleQuotation
		if (preg_match('/"/',$permissionString) == true) {
			return $this->getLanguage('error/permissionString/doubleQuotation');
		}
		
		// eval check
		ob_start();
		$check = eval("return {$permissionString};");
		$content = ob_get_contents();
		ob_end_clean();
		
		if ($content) return $this->getLanguage('error/permissionString/parse');
		if (is_bool($check) == false) return $this->getLanguage('error/permissionString/boolean');
		
		return true;
	}
	
	/**
	 * 접속한 URL 주소에 따라 $menu, $page, $idx 변수들을 활용하여 현재 접속한 URL 메뉴에 해당하는 컨텍스트를 불러오고,
	 * 사이트 템플릿에 적용하여 사이트 레이아웃을 화면상에 출력한다.
	 */
	function doLayout() {
		global $_CONFIGS;
		
		/**
		 * iModule 이 설치가 되지 않은 경우 레이아웃 출력을 중단하고 설치 페이지로 이동한다.
		 */
		if ($_CONFIGS->installed === false) {
			header('location:'.__IM_DIR__.'/install');
			exit;
		}
		
		/**
		 * 필수모듈이 설치가 되어 있지 않은 경우, 레이아웃 출력을 중단하고 모듈 설치 페이지(관리자)로 이동한다.
		 */
		if ($this->Module->isInstalled('member') == false || $this->Module->isInstalled('push') == false) {
			header('location:'.__IM_DIR__.'/admin/module');
			exit;
		}
		
		/**
		 * 사이트내 글로벌하게 동작하도록 설정된 모듈(예 : member, push 등)을 불러온다.
		 */
		$this->Module->loadGlobals();
		
		$site = $this->getSite();
		
		/**
		 * 컨텍스트를 가지고 오기전 beforeGetContext 이벤트를 발생시킨다.
		 */
		$this->fireEvent('beforeGetContext','core','doLayout',null,null);
		
		/**
		 * 현재 접근한 페이지에 해당하는 사이트명을 설정하고, 컨텍스트 HTML 코드를 가져온다.
		 * 현재 접근한 페이지에 해당하는 컨텍스트가 없을 경우404 에러를 출력한다.
		 */
		$context = $this->getPageContext($this->menu,$this->page);
		
		/**
		 * 컨텍스트를 가지고 온 뒤 afterGetContext 이벤트를 발생시킨다.
		 * 컨텍스트 HTML 코드인 $context 변수는 pass by object 로 전달되기 때문에 이벤트리스너에서 조작할 경우 최종출력되는 HTML 코드가 변경된다.
		 */
		$this->fireEvent('afterGetContext','core','doLayout',null,null,$context);
		
		/**
		 * 가져온 컨텍스트 HTML 코드를 페이지 레이아웃에 담아 웹사이트 body 를 만든다.
		 */
		$body = $this->getContextLayout($this->menu,$this->page,$context);
		
		/**
		 * 사이트 푸터에서 스타일시트나, 자바스크립트 파일을 추가할 수 있으므로, 사이트푸터부터 생성하여 가져온다.
		 */
		$footer = $this->getFooter();
		
		/**
		 * 사이트 헤더를 가져온다.
		 */
		$header = $this->getHeader();
		
		/**
		 * 사이트 레이아웃 HTML 을 만든다.
		 */
		$html = $header.PHP_EOL.$body.PHP_EOL.$footer;
		
		/**
		 * 사이트 로딩타임을 출력한다.
		 */
		$html.= PHP_EOL.'<!-- Load Time : '.$this->getLoadTime().' -->';
		
		/**
		 * 전체 사이트 HTML 을 생성한 뒤 afterDoLayout 이벤트를 발생시킨다.
		 * 전체 사이트 HTML 코드인 $html 변수는 pass by object 로 전달되기 때문에 이벤트리스너에서 조작할 경우 최종출력되는 HTML 코드가 변경된다.
		 */
		$this->fireEvent('afterDoLayout','core','*',null,null,$html);
		
		/**
		 * 사이트 HTML 코드를 출력한다.
		 */
		echo $html;
	}
}
?>