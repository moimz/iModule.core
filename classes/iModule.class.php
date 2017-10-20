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
 * @version 3.0.0.160905
 */
class iModule {
	/**
	 * DB 관련 변수정의
	 *
	 * @private DB $DB DB에 접속하고 데이터를 처리하기 위한 DB class (@see /classes/DB.class.php)
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $DB;
	private $table;
	
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
	public $container = null;
	public $indexUrl = null;
	
	/**
	 * DB접근을 줄이기 위해 DB에서 불러온 데이터를 저장할 변수를 정의한다.
	 *
	 * @public object[] $sites : 사이트 설정값
	 * @public object[] $siteLinks : 사이트 링크값
	 * @public object[] $menus : 사이트별 모든 메뉴설정값
	 * @public object[] $pages : 사이트별 특정 메뉴에 해당하는 모든 페이지설정값
	 * @public object[] $modules : 불러온 모듈 클래스
	 */
	public $sites = array();
	public $siteLinks = array();
	public $menus = array();
	public $pages = array();
	public $sitemap = array();
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
	private $timezone; // server timezone
	
	/**
	 * 사이트 설정변수
	 * 현재 접속한 사이트주소에 따라 접근한 사이트관련 정보들을 정의한다.
	 *
	 * @public object $site 현재 사이트에 관련된 모든 RAW 정보
	 * @public boolean $useTemplet 사이트템플릿 사용여부
	 * @private string $siteTitle 웹브라우저에 표시되는 사이트제목
	 * @private string $siteDescription SEO를 위한 META 태그에 정의될 사이트소개
	 * @private string $siteCanonical SEO를 위한 현재 페이지에 접근할 수 있는 유니크한 사이트주소 (필수 GET 변수만 남겨둔 페이지 URL)
	 * @private string $siteImage OG META 태그를 위한 사이트 이미지 (각 모듈이나 애드온에서 페이지별로 변경할 수 있다.)
	 */
	public $site;
	public $useTemplet = true;
	
	private $siteTitle = null;
	private $siteDescription = null;
	private $siteCanonical = null;
	private $siteImage = null;
	
	private $siteHeader = array();
	private $siteTemplet = null;
	private $javascriptLanguages = array();
	private $webFont = array('moimz'); // Moimz 폰트아이콘은 기본적으로 포함된다.
	private $webFontDefault = null;
	
	/**
	 * class 선언
	 */
	function __construct($mode=null) {
		global $_CONFIGS;
		
		/**
		 * 페이지 로딩시간을 구하기 위한 최초 마이크로타임을 기록한다.
		 */
		$this->initTime = $this->getMicroTime();
		
		/**
		 * 접속한 사이트주소 및 사이트변수 정의
		 */
		$this->site = null;
		$this->domain = isset($_SERVER['HTTP_HOST']) == true ? strtolower($_SERVER['HTTP_HOST']) : '';
		$this->language = Request('language');
		$this->menu = Request('menu') == null ? 'index' : preg_replace('/[^a-zA-Z_0-9]/','',Request('menu'));
		$this->page = Request('page') == null ? null : preg_replace('/[^a-zA-Z_0-9]/','',Request('page'));
		$this->view = Request('view') == null ? null : Request('view');
		$this->idx = Request('idx') == null ? null : Request('idx');
		
		if ($mode !== 'SAFETY') {
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
			$this->table->sitemap = 'sitemap_table';
			$this->table->article = 'article_table';
		}
		
		/**
		 * 타임존 설정
		 * @todo 언젠가 사용할 예정
		 */
		$this->timezone = 'Asia/Seoul';
		date_default_timezone_set($this->timezone);
		
		/**
		 * 기본 사이트 자바스크립트 호출
		 *
		 * moment.js : 시간포맷을 위한 자바스크립트 라이브러리
		 * jquery.1.11.2.min.js : jQuery
		 * default.js : 기본 iModule 자바스크립트 라이브러리
		 */
		$this->addHeadResource('script',__IM_DIR__.'/scripts/moment.js');
		$this->addHeadResource('script',__IM_DIR__.'/scripts/jquery.js');
		$this->addHeadResource('script',__IM_DIR__.'/scripts/jquery.extend.js');
		$this->addHeadResource('script',__IM_DIR__.'/scripts/common.js');
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
	 * 정상적으로 사이트에 접속시, 현재 접속한 사이트의 기본 URL을 구하고 사이트에 설정된 메뉴들을 저장한다.
	 */
	function initSites() {
		/**
		 * 모든 사이트의 RAW 데이터를 저장한다.
		 */
		$this->sites = $this->db()->select($this->table->site)->orderBy('sort','asc')->get();
		
		/**
		 * 현재 접속한 도메인에 해당하는 사이트가 없을 경우, 유사한 사이트를 찾는다.
		 */
		if ($this->db()->select($this->table->site)->where('domain',$this->domain)->has() == false) {
			$isAlias = false;
			for ($i=0, $loop=count($this->sites);$i<$loop;$i++) {
				if ($this->sites[$i]->alias == '') continue;
				
				/**
				 * 현재 접속한 도메인을 alias 로 가지고 있는 사이트를 탐색한다.
				 */
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
			
			/**
			 * 전체 사이트 정보를 참고해도 현재 접속한 도메인의 사이트를 찾을 수 없을 경우 에러메세지를 출력한다.
			 */
			if ($isAlias == false) {
				$this->printError('SITE_NOT_FOUND');
			}
		}
		
		/**
		 * 언어설정값이 유효한지 확인한다.
		 */
		if ($this->language === null) {
			/**
			 * 언어셋이 지정되지 않았을 경우 기본언어셋을 검색한다. 만약 찾을 수 없다면 에러메세지를 출력한다.
			 */
			$site = $this->db()->select($this->table->site)->where('domain',$this->domain)->where('is_default','TRUE')->getOne();
			if ($site == null) $this->printError('LANGUAGE_NOT_FOUND');
			
			$this->language = $site->language;
		} else {
			/**
			 * 언어셋이 지정되었고, 해당 언어셋이 현재 사이트에서 사용중인지 확인한다. 만약 사용중인 언어셋이 아니라면 기본언어셋을 사용한다.
			 */
			$site = $this->db()->select($this->table->site)->where('domain',$this->domain)->where('language',$this->language)->getOne();
			if ($site == null) {
				$site = $this->db()->select($this->table->site)->where('domain',$this->domain)->where('is_default','TRUE')->getOne();
				
				/**
				 * 기본 언어셋이 없을 경우 에러메세지를 출력한다.
				 */
				if ($site == null) $this->printError('LANGUAGE_NOT_FOUND');
				
				$this->language = $site->language;
			}
		}
		
		/**
		 * 특수한 경우가 아닌 경우 사이트유효성 검사에 따라 확인된 URL로 이동한다.
		 */
		if (preg_match('/\/(addons|admin|api|modules|process|templets)(\/[a-z]+)?\/index\.php/',$_SERVER['PHP_SELF']) == false) {
			if (($site->is_ssl == 'TRUE' && empty($_SERVER['HTTPS']) == true) || $_SERVER['HTTP_HOST'] != $site->domain || Request('language') != $site->language) {
				header("location:".($site->is_ssl == 'TRUE' ? 'https://' : 'http://').$site->domain.__IM_DIR__.'/'.$this->language.'/');
				exit;
			}
		}
		
		/**
		 * 사이트에서 사용중인 1차메뉴 및 2차메뉴를 저장한다.
		 */
		for ($i=0, $loop=count($this->sites);$i<$loop;$i++) {
			$this->menus[$this->sites[$i]->domain.'@'.$this->sites[$i]->language] = array();
			$this->pages[$this->sites[$i]->domain.'@'.$this->sites[$i]->language] = array();
			
			/**
			 * 사이트 구성모듈이 있는 경우 해당 모듈을 통해 사이트맵을 가져온다.
			 */
			if (strpos($this->sites[$i]->templet,'#') === 0 && $this->getModule()->isSitemap(substr($this->sites[$i]->templet,1)) == true) {
				$mModule = $this->getModule(substr($this->sites[$i]->templet,1));
				$this->menus[$this->sites[$i]->domain.'@'.$this->sites[$i]->language] = $mModule->getMenus();
				$this->pages[$this->sites[$i]->domain.'@'.$this->sites[$i]->language] = $mModule->getPages();
			} else {
				$sitemap = $this->db()->select($this->table->sitemap)->where('domain',$this->sites[$i]->domain)->where('language',$this->sites[$i]->language)->orderBy('sort','asc')->get();
				
				for ($j=0, $loopj=count($sitemap);$j<$loopj;$j++) {
					$sitemap[$j]->is_hide = isset($sitemap[$j]->is_hide) == true && $sitemap[$j]->is_hide == 'TRUE';
					$sitemap[$j]->is_footer = isset($sitemap[$j]->is_footer) == true && $sitemap[$j]->is_footer == 'TRUE';
					
					$sitemap[$j]->context = isset($sitemap[$j]->context) == true && $sitemap[$j]->context ? json_decode($sitemap[$j]->context) : null;
					$sitemap[$j]->description = isset($sitemap[$j]->description) == true && $sitemap[$j]->description ? $sitemap[$j]->description : null;
					$sitemap[$j]->image = isset($sitemap[$j]->image) == true && $sitemap[$j]->image ? __IM_DIR__.'/attachment/view/'.$sitemap[$j]->image.'/preview.png' : null;
					if ($sitemap[$j]->type == 'MODULE') $sitemap[$j]->context->config = isset($sitemap[$j]->context->config) == true ? $sitemap[$j]->context->config : null;
					
					if (isset($this->pages[$sitemap[$j]->domain.'@'.$sitemap[$j]->language][$sitemap[$j]->menu]) == false) $this->pages[$sitemap[$j]->domain.'@'.$sitemap[$j]->language][$sitemap[$j]->menu] = array();
					
					if ($sitemap[$j]->page == '') {
						$this->menus[$sitemap[$j]->domain.'@'.$sitemap[$j]->language][] = $sitemap[$j];
					} else {
						$this->pages[$sitemap[$j]->domain.'@'.$sitemap[$j]->language][$sitemap[$j]->menu][] = $sitemap[$j];
					}
				}
			}
		}
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
	 * Cache 클래스를 반환한다.
	 *
	 * @return Cache $cache
	 */
	function cache() {
		return $this->Cache;
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
	 * 코어이름을 반환한다.
	 */
	function getName() {
		return 'core';
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
		
		$returnString = null;
		$temp = explode('/',$code);
		
		$string = $this->lang;
		$oString = $this->oLang;
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
		
		if ($returnString == null) return $replacement === null ? $code : $replacement;
		else return $returnString;
	}
	
	/**
	 * 상황에 맞게 에러코드를 반환한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param string $message(옵션) 변환된 에러메세지
	 */
	function getErrorText($code,$value=null,$message=null,$isRawData=false) {
		if (is_object($code) == true) {
			$message = $code->message;
			$description = $code->description;
			$type = $code->type;
		} else {
			$message = '';
			if ($message == null) {
				$message = $this->getText('error/'.$code,$code);
			}
			
			if ($message == $code) {
				$message = $this->getText('error/UNKNOWN');
				$description = $code;
				$type = 'MAIN';
			} else {
				$description = null;
				switch ($code) {
					case 'PHP_ERROR' :
						$description = 'File : '.$value['file'].'<br>Line : '.$value['line'].'<br><br>';
						$description.= nl2br(str_replace(array('<','>'),array('&lt;','&gt;'),$value['message']));
						$type = 'MAIN';
						break;
						
					case 'NOT_FOUND_PAGE' :
						$description = $value ? $value : $this->getUrl();
						$type = 'BACK';
						break;
						
					case 'REQUIRED_LOGIN' :
						$type = 'LOGIN';
						break;
						
					default :
						if ($value != null && is_string($value) == true) $description = $value;
						$type = 'BACK';
				}
				$description = strlen($description) == 0 ? null : $description;
			}
		}
		
		if ($isRawData === true) {
			$data = new stdClass();
			$data->message = $message;
			$data->description = $description;
			$data->type = $type;
			
			return $data;
		}
		
		return $message.($description !== null ? ' ('.$description.')' : '');
	}
	
	/**
	 * 모듈 클래스를 불러온다.
	 * 이미 모듈 클래스가 선언되어 있다면 선언되어 있는 모듈클래스를 반환한다. (중복선언하지 않음)
	 *
	 * @param string $module(옵션) 모듈이름 (/modules 내부의 해당모듈의 폴더명)
	 * @param boolean $isForceLoad(기본값 false) 모듈이 설치되어 있지 않더라도 모듈 클래스를 호출할지 여부
	 * @return object $module 모듈클래스
	 */
	function getModule($module=null,$isForceLoad=false) {
		if ($module == null) return $this->Module;
		
		/**
		 * 선언되어 있는 해당 모듈 클래스가 없을 경우, 새로 선언한다.
		 */
		if (isset($this->modules[$module]) == false) {
			/**
			 * 모듈코어 클래스를 새로 선언하고, 모듈코어 클래스에서 모듈 클래스를 불러온다.
			 */
			$class = new Module($this);
			$this->modules[$module] = $class->load($module,$isForceLoad);
		}
		
		/**
		 * 모듈클래스를 호출하지 못했을 경우, 에러메세지를 출력한다.
		 */
		if ($this->modules[$module] === false) $this->printError('LOAD_MODULE_FAIL : '.$module);
		
		return $this->modules[$module];
	}
	
	/**
	 * 위젯 클래스를 불러온다.
	 * 위젯은 하나의 페이지에 중복으로 사용할 수 있으므로, 무조건 새로운 클래스를 정의하여 반환한다.
	 *
	 * @param string $widget 위젯명 (/widgets 내부의 해당위젯의 폴더명)
	 * @return object $widget
	 */
	function getWidget($widget) {
		$class = new Widget($this);
		return $class->load($widget);
	}
	
	/**
	 * 템플릿 객체를 가져온다.
	 *
	 * @param object $caller 템플릿을 요청하는 클래스 (iModule, Module, Widget)
	 * @param string $templet 템플릿명
	 * @return Templet $templet 템플릿 객체
	 */
	function getTemplet($caller,$templet) {
		$class = new Templet($this);
		return $class->load($caller,$templet);
	}
	
	/**
	 * 전체 템플릿목록을 가져온다.
	 *
	 * @param object $caller 템플릿목록을 요청하는 클래스 (iModule, Module, Widget)
	 * @return Templet[] $templets 템플릿목록
	 */
	function getTemplets($caller) {
		$class = new Templet($this);
		return $class->getTemplets($caller);
	}
	
	/**
	 * 함수가 호출될 시점의 microtime 을 구한다.
	 *
	 * @return double $microtime
	 */
	function getMicroTime() {
		$microtimestmp = explode(" ",microtime());
		return $microtimestmp[0]+$microtimestmp[1];
	}
	
	/**
	 * iModule 이 선언되고 나서 함수가 호출되는 시점까지의 수행시간을 구한다.
	 *
	 * @return double $loadtime
	 */
	function getLoadTime() {
		return sprintf('%0.5f',$this->getMicroTime() - $this->initTime);
	}
	
	/**
	 * 모든 첨부파일이 저장되는 절대경로를 반환한다.
	 *
	 * @return string $attachment_path
	 * @see /modules/ModuleAttachment.class.php
	 * @tode 첨부파일 저장되는 경로를 변경할 수 있는 설정값 추가
	 */
	function getAttachmentPath() {
		global $_CONFIGS;
		if (isset($_CONFIGS->attachment) == true && isset($_CONFIGS->attachment->path) == true) return $_CONFIGS->attachment->path;
		return __IM_PATH__.'/attachments';
	}
	
	/**
	 * 모든 첨부파일이 저장되는 상대경로를 반환한다.
	 *
	 * @return string $attachment_dir
	 * @see /modules/ModuleAttachment.class.php
	 * @tode 첨부파일 저장되는 경로를 변경할 수 있는 설정값 추가
	 */
	function getAttachmentDir() {
		global $_CONFIGS;
		if (isset($_CONFIGS->attachment) == true && isset($_CONFIGS->attachment->dir) == true) return $_CONFIGS->attachment->dir;
		return __IM_DIR__.'/attachments';
	}
	
	/**
	 * 현재 접속한 프로토콜(HTTP or HTTPS)를 포함한 Host정보를 구한다.
	 * 
	 * @param boolean $isDir true : iModule 이 설치된 디렉토리 경로를 포함한다.
	 */
	function getHost($isDir=false) {
		$url = isset($_SERVER['HTTPS']) == true ? 'https://' : 'http://';
		$url.= $this->domain;
		if ($isDir == true) $url.= __IM_DIR__;
		
		return $url;
	}
	
	/**
	 * 메뉴 URL 을 구한다.
	 * 모든 파라매터값은 옵션이며 입력되지 않거나, NULL 일 경우 현재 접속한 페이지의 정보를 사용한다.
	 * 즉, 모든 파라매터값이 없는 상태로 호출하면 현재 페이지의 URL 을 구할 수 있다.
	 * 파라매터값을 false 로 설정하면 하위주소를 무시한다. $page 값이 false 일 경우 1차 메뉴주소까지만 반환한다.
	 *
	 * @param string $menu 1차 메뉴
	 * @param string $page 2차 메뉴
	 * @param string $view 모듈별 페이지종류 (목록페이지 또는 글쓰기페이지 등 : 모듈별로 사용되는 값이 다르다.)
	 * @param string $idx 모듈별 고유값 (게시물번호 또는 회원아이디 등 : 모듈별로 사용되는 값이 다르다.)
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 제외한 URL(기본)
	 * @param string $domain 현재 접속한 도메인이 아닌 다른 사이트로 연결하고자 할 경우 해당 사이트의 도메인
	 * @param string $language 현재 접속한 언어설정이 아닌 다른 언어의 사이트로 연결하고자 할 경우 해당 언어셋 코드
	 * @return string $url;
	 */
	function getUrl($menu=null,$page=null,$view=null,$idx=null,$isFullUrl=false,$domain=null,$language=null) {
		if ($this->container != null) {
			$container = explode('/',$this->container);
			return $this->getModuleUrl($container[0],$container[1],$view,$idx,$isFullUrl,$domain,$language);
		}
		
		/**
		 * 전달된 값이 없거나, NULL 일 경우 현재 페이지의 값으로 설정한다.
		 */
		$menu = $menu === null ? $this->menu : $menu;
		$page = $page === null && $menu == $this->menu ? $this->page : $page;
		$view = $view === null && $menu == $this->menu && $page == $this->page ? $this->view : $view;
		$idx = $idx === null && $menu == $this->menu && $page == $this->page && $view == $this->view ? $this->idx : $idx;
		
		/**
		 * $domain 의 값이 * 일 경우 현재 사이트의 도메인으로 설정한다.
		 */
		$domain = $domain == '*' ? $this->site->domain : $domain;
		
		$context = $menu === null || $menu === false ? null : ($page === null || $page === false ? $this->getMenus($menu,$domain,$language) : $this->getPages($menu,$page,$domain,$language));
		if ($context != null && isset($context->type) == true && $context->type == 'LINK') return $context->context->link.'#IM'.$context->context->target;
		
		/**
		 * $isFullUrl 값이 true 이거나, 설정된 도메인이 현재 사이트의 도메인과 다를 경우 전체 URL 을 생성한다.
		 */
		if ($isFullUrl == true || ($domain != null && $domain !== $this->site->domain)) {
			$domain = $domain == null ? $_SERVER['HTTP_HOST'] : $domain;
			$check = $this->db()->select($this->table->site)->where('domain',$domain)->getOne();
			if ($check == null) {
				$url = isset($_SERVER['HTTPS']) == true ? 'https://' : 'http://';
				$url.= $domain.__IM_DIR__;
			} else {
				$url = $check->is_ssl == 'TRUE' ? 'https://' : 'http://';
				$url.= $domain.__IM_DIR__;
			}
		} else {
			$url = __IM_DIR__;
		}
		
		/**
		 * 각각의 파라매터값이 false 가 아닐때까지 하위메뉴 주소를 만들고 반환한다.
		 */
		$url.= '/'.($language == null ? $this->language : $language);
		if ($menu === null || $menu === false) return $url;
		$url.= '/'.$menu;
		if ($page === null || $page === false) return $url;
		$url.= '/'.$page;
		if ($view === null || $view === false) return $url;
		$url.= '/'.$view;
		if ($idx === null || $idx === false) return $url;
		$url.= '/'.$idx;
		
		return $url;
	}
	
	/**
	 * 특정 모듈의 특정 컨텍스트를 사용하도록 설정된 페이지 URL을 반환한다.
	 *
	 * @param string $module 모듈명
	 * @param string $context 컨텍스트명
	 * @param string[] $extacts 반드시 일치해야하는 컨텍스트 옵션
	 * @param string[] $options 반드시 일치할 필요는 없는 컨텍스트 옵션
	 * @param boolean $isSameDomain 현재 도메인 우선모드 (기본값 : false, true 일 경우 같은 도메인일 경우 우선, false 일 경우 $options 설정값에 우선)
	 */
	function getContextUrl($module,$context,$exacts=array(),$options=array(),$isSameDomain=false,$matches=array()) {
		if (count($matches) == 0) {
			$pages = $this->db()->select($this->table->sitemap)->where('type','MODULE')->get();
			foreach ($pages as $page) {
				$page->context = json_decode($page->context);
				
				/**
				 * 반드시 일치해야하는 설정값에 해당하는 페이지를 찾는다.
				 */
				if ($page->context->module == $module && $page->context->context == $context) {
					$isMatched = true;
					foreach ($exacts as $key=>$value) {
						if ($page->context->configs->$key != $value) $isMatched = false;
					}
					
					if ($isMatched == true) $matches[] = $page;
				}
			}
		}
		
		/**
		 * 설정과 일치하는 페이지가 없을 경우, NULL 을 반환한다.
		 */
		if (count($matches) == 0) return null;
		
		/**
		 * 설정과 일치하는 페이지가 유일할 경우 해당 페이지를 반환한다.
		 */
		if (count($matches) == 1) return $matches[0];
		
		/**
		 * 설정과 일치하는 페이지가 2개 이상일 경우, $options 설정이나, $isSameDomain 설정에 따라 최대한 일치하는 페이지를 재탐색한다.
		 */
		$filters = array();
		
		/**
		 * 같은 도메인 우선일 경우
		 */
		if ($isSameDomain == true) {
			foreach ($matches as $match) {
				if ($match->domain == $this->site->domain) $filters[] = $match;
			}
			
			/**
			 * 같은 도메인에 설정과 일치하는 페이지가 없을 경우, $options 우선모드로 재탐색한다.
			 */
			if (count($filters) == 0) return $this->getContextUrl($module,$context,$exacts,$options,false,$matches);
			
			/**
			 * 같은 도메인에 설정과 일치하는 페이지가 유일할 경우, 해당 페이지를 반환한다.
			 */
			if (count($filters) == 1) return $filters[0];
			
			/**
			 * 같은 도메인에 설정과 일치하는 페이지가 2개 이상일 경우, $options 설정에 해당하는 것을 재탐색하기 위해 $matches 를 재정의한다.
			 */
			$matches = $filters;
		}
		
		/**
		 * $options 설정과 최대한 많이 일치하는 페이지를 재탐색한다.
		 */
		$page = null;
		foreach ($matches as $match) {
			/**
			 * 일치하는 $options 설정값 갯수
			 */
			$match->matchCount = 0;
			foreach ($options as $key=>$value) {
				if ($match->context->configs->$key == $value) $match->matchCount++;
			}
			
			if ($page == null || $page->matchCount < $match->matchCount) $page = $match;
		}
		
		return $page;
	}
	
	/**
	 * 명령을 처리할 주소를 반환한다.
	 *
	 * @param string $module 모듈이름
	 * @param string $action 명령코드
	 * @param string[] $params 전달할 변수
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 제외한 URL(기본)
	 */
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
	 * 모듈 인덱스 페이지의 URL을 반환한다.
	 * 사이트에 포함되어 동작하는 모듈이 아니라 단독적으로 동작해야하는 모듈의 경우 자신의 모듈폴더에 index.php 파일을 가지며 해당 PHP파일에 접속하기 위한 URL을 반환한다.
	 *
	 * @param string $module 모듈이름
	 * @param string $container 모듈의 index 파일이 처리할 컨테이너코드
	 * @param string $view 모듈 컨텍스트의 View 값
	 * @param string $idx 모듈별로 요구하는 고유값
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 제외한 URL(기본)
	 * @param string $domain 현재 접속한 도메인이 아닌 다른 사이트로 연결하고자 할 경우 해당 사이트의 도메인
	 * @param string $language 현재 접속한 언어설정이 아닌 다른 언어의 사이트로 연결하고자 할 경우 해당 언어셋 코드
	 */
	function getModuleUrl($module,$container,$view=null,$idx=null,$isFullUrl=false,$domain=null,$language=null) {
		$domain = $domain == '*' || $domain == null ? $_SERVER['HTTP_HOST'] : $domain;
		$view = $view === null ? $this->view : $view;
		$idx = $idx === null ? $this->idx : $idx;
		
		if ($isFullUrl == true || $domain !== $_SERVER['HTTP_HOST']) {
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
		$url.= '/module/'.$module.'/'.$container;
		
		if ($view == null || $view == false) return $url;
		$url.= '/'.$view;
		
		if ($idx == null || $idx == false) return $url;
		$url.= '/'.$idx;
		
		return $url;
	}
	
	/**
	 * GET 으로 전달되는 QUERY_STRING(URL의 ? 이하부분)중 일부 파라매터값을 변경하고, 비어있거나 불필요한 QUERY_STRING 삭제한다.
	 *
	 * @param string[] $query array('GET 파라매터 KEY'=>'변경할 값, 해당값이 없으면 GET 파라매터를 지운다.')
	 * @param string $queryString 정리할 query string 값이 없을 경우 $_SERVER['QUERY_STRING'] 을 사용한다.
	 * @return string $queryString 정리된 GET 파라매터
	 */
	function getQueryString($query=array(),$queryString=null) {
		$queryString = $queryString == null ? $_SERVER['QUERY_STRING'] : $queryString;
		$query = array_merge(array('menu'=>'','page'=>'','view'=>'','idx'=>'','module'=>'','container'=>'','language'=>''),$query);
		
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
	 * 사이트관리자에 의해 설정된 사이트 설정값을 가져온다.
	 *
	 * @param string $domain(옵션) 사이트 도메인 주소 (해당 값이 있을 경우 해당 도메인에 대한 설정값이 반환되며 없을 경우 모든 사이트에 대한 설정값이 반환된다.)
	 * @param string $language(옵션) 사이트 언어셋코드 (해당 값이 있을 경우 해당 언어셋을 사용하고 있는 사이트정보만 반환된다.)
	 * @return object[] 파라매터 조건에 맞는 사이트정보 (조건에 맞는 사이트가 1뿐이라면 배열이 아닌 사이트정보 Object 가 반환된다.)
	 */
	function getSites($domain=null,$language=null) {
		if ($this->sites == null) $this->initSites();
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
	 * 사이트관리자에 의해 설정된 멀티사이트링크를 가져온다.
	 * 가급적 현재 사이트와 동일한 언어의 사이트링크를 가져오고 없을경우 기본언어 사이트를 가져온다.
	 */
	function getSiteLinks() {
		if ($this->siteLinks != null) return $this->siteLinks;
		if ($this->sites == null) $this->initSites();
		
		$check = array();
		$links = array();
		for ($i=0, $loop=count($this->sites);$i<$loop;$i++) {
			if ($this->sites[$i]->language == $this->language) {
				$check[$this->sites[$i]->domain] = true;
			}
		}
		
		for ($i=0, $loop=count($this->sites);$i<$loop;$i++) {
			if (isset($check[$this->sites[$i]->domain]) == false && $this->sites[$i]->is_default == 'TRUE') {
				$links[] = $this->sites[$i];
			} elseif ($this->sites[$i]->language == $this->language) {
				$links[] = $this->sites[$i];
			}
		}
		
		for ($i=0, $loop=count($links);$i<$loop;$i++) {
			$links[$i]->url = ($links[$i]->is_ssl == 'TRUE' ? 'https://' : 'http://').$links[$i]->domain.__IM_DIR__.'/'.$links[$i]->language;
		}
		
		$this->siteLinks = $links;
		
		return $this->siteLinks;
	}
	
	/**
	 * 현재 사이트의 1차 메뉴 정보를 가져온다.
	 *
	 * @param string $menu(옵션) $menu 값이 있을 경우 해당 값에 해당되는 메뉴설정이 반환된다. 없을 경우 전체 메뉴설정이 반환된다.
	 * @param string $domain(옵션) 현재 접속한 사이트가 아닌 다른 도메인의 1차 메뉴를 가져온다.
	 * @param string $language(옵션) 현재 접속한 사이트언어가 아닌 아닌 다른 언어의 1차 메뉴를 가져온다.
	 * @return object[] $menus
	 */
	function getMenus($menu=null,$domain=null,$language=null) {
		$domain = $domain === null ? $this->domain : $domain;
		$language = $language == null ? $this->language : $language;
		
		$site = $domain.'@'.$language;
		if (empty($this->menus[$site]) == true) return $menu == null ? array() : null;
		if ($menu == null) return $this->menus[$site];
		
		for ($i=0, $loop=count($this->menus[$site]);$i<$loop;$i++) {
			if ($this->menus[$site][$i]->menu == $menu) return $this->menus[$site][$i];
		}
		return null;
	}
	
	/**
	 * 현재 사이트의 2차 메뉴 정보를 가져온다.
	 *
	 * @param string $menu(옵션) $menu 값이 있을 경우 해당 값에 해당되는 2차 메뉴목록이 반환된다. 없을 경우 전체 1차메뉴의 2차메뉴를 가져온다.
	 * @param string $page(옵션) $page 값이 있을 경우 $menu 값은 반드시 지정되어 있어야하며, $page 값에 설정된 특정 페이지설정이 반환된다.
	 * @param string $domain(옵션) 현재 접속한 사이트가 아닌 다른 도메인의 2차 메뉴를 가져온다.
	 * @param string $language(옵션) 현재 접속한 사이트언어가 아닌 아닌 다른 언어의 2차 메뉴를 가져온다.
	 * @return object[] $pages
	 */
	function getPages($menu=null,$page=null,$domain=null,$language=null) {
		$domain = $domain === null ? $this->domain : $domain;
		$language = $language == null ? $this->language : $language;
		
		$site = $domain.'@'.$language;
		if (isset($this->menus[$site]) == false) return $page == null ? array() : null;
		if ($menu == null) return $this->pages[$site];
		if (isset($this->pages[$site][$menu]) == false) return $page == null ? array() : null;
		if ($page == null) return $this->pages[$site][$menu];
		
		for ($i=0, $loop=count($this->pages[$site][$menu]);$i<$loop;$i++) {
			if ($this->pages[$site][$menu][$i]->page == $page) return $this->pages[$site][$menu][$i];
		}
		return null;
	}
	
	/**
	 * 현재 사이트 RAW 데이터를 가공하여 가져온다.
	 * 
	 * @return object $site
	 */
	function getSite() {
		if ($this->site != null) return $this->site;
		
		$this->site = clone $this->getSites($this->domain,$this->language);
		
		$this->site->logo = json_decode($this->site->logo);
		$this->site->maskicon = json_decode($this->site->maskicon);
		$this->site->description = $this->site->description ? $this->site->description : null;
		
		return $this->site;
	}
	
	/**
	 * 현재 페이지의 정보를 가공하여 가져온다.
	 */
	function getPage() {
		if ($this->site == null) return null;
		
		if ($this->page == null) {
			$menu = $this->getMenus($this->menu);
			if (isset($menu->type) == true && $menu->type == 'PAGE') return $this->getPages($this->menu,$menu->context->page);
			else return $menu;
		} else {
			return $this->getPages($this->menu,$this->page);
		}
	}
	
	/**
	 * 현재 사이트 또는 다른 도메인의 사이트맵을 가져온다.
	 *
	 * @param string $domain(옵션) 사이트도메인, 미입력시 현재 사이트
	 * @param string $language(옵션) 사이트언어셋, 미입력시 현재 사이트언어
	 */
	function getSitemap($domain=null,$language=null) {
		$domain = $domain === null ? $this->domain : $domain;
		$language = $language == null ? $this->language : $language;
		
		$site = $domain.'@'.$language;
		if (isset($this->sitemap[$site]) == true) return $this->sitemap[$site];
		
		/**
		 * 사이트 전체메뉴를 가져온다.
		 */
		$sitemap = $this->getMenus(null,$domain,$language);
		for ($i=0, $loop=count($sitemap);$i<$loop;$i++) {
			/**
			 * 메뉴의 하위 메뉴를 가져온다.
			 */
			$sitemap[$i]->pages = $this->getPages($sitemap[$i]->menu,null,$domain,$language);
		}
		
		$this->sitemap[$site] = $sitemap;
		return $this->sitemap[$site];
	}
	
	/**
	 * @todo 공사중
	 */
	function getPageCountInfo($page) {
		return null;
		/*
		if ($page->type == 'MODULE') {
			$module = $this->getModule($page->context->module);
			if (method_exists($module,'getCountInfo') == true) {
				return $module->getCountInfo($page->context->context,$page->context->config);
			} else {
				return null;
			}
		}
		
		return null;
		*/
	}
	
	/**
	 * 현재사이트에서 페이지 하단(푸터)부분에 출력하도록 설정한 1차 또는 2차메뉴를 가져온다.
	 *
	 * @return object[] $footerMenus
	 */
	function getFooterPages() {
		$sorts = array();
		$pages = $this->db()->select($this->table->sitemap)->where('domain',$this->domain)->where('language',$this->language)->where('is_footer','TRUE')->get();
		for ($i=0, $loop=count($pages);$i<$loop;$i++) {
			if ($pages[$i]->page) {
				$menu = $this->db()->select($this->table->sitemap)->where('domain',$this->domain)->where('language',$this->language)->where('menu',$pages[$i]->menu)->where('page','')->getOne();
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
	
	/**
	 * 사이트 로고 이미지를 가져온다.
	 * 사이트 로고는 사이트템플릿의 package.json 에 의해 정해진 종류별로 가져올 수 있다.
	 * 해당 종류의 사이트로고 파일이 없을 경우 기본 로고 이미지를 반환한다.
	 *
	 * @param string $type 로고종류
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 포함하지 않은 URL(기본)
	 * @return string $logoUrl 로고 이미지 URL
	 */
	function getSiteLogo($type='default',$isFullUrl=false) {
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		/**
		 * 로고종류가 default 또는 footer 이고 해당 종류에 설정된 로고가 없을 경우, iModule 의 기본 로고파일을 반환한다.
		 */
		if (in_array($type,array('default','footer')) == true && (empty($this->site->logo->{$type}) == true || $this->site->logo->{$type} == -1)) return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/images/logo/'.$type.'.png';
		
		/**
		 * 가져올 로고타입이 사이트관리자에 의해 설정되어 있지 않을 경우, 기본 로고파일을 가져온다.
		 */
		if (empty($this->site->logo->$type) == true) return $this->getSiteLogo('default');
		
		/**
		 * 가져올 로고타입을 사이트관리자에 의해 사용하지 않음 으로 설정되어 있는 경우 NULL 을 반환한다.
		 */
		if ($this->site->logo->$type == 0) return null;
		
		/**
		 * 사이트 관리자에 설정된 로고파일을 가져온다.
		 */
		return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/attachment/view/'.$this->site->logo->$type.'/logo.png';
	}
	
	/**
	 * 사이트 엠블럼 이미지를 가져온다.
	 * 사이트 엠블럼이 지정되지 않은 경우 iModule 의 기본 엠블럼이미지를 반환하고, 사용하지 않는다고 설정한 경우 NULL 을 반환한다.
	 *
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 포함하지 않은 URL(기본)
	 * @return string $emblemUrl 엠블럼 이미지 URL
	 */
	function getSiteEmblem($isFullUrl=false) {
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		/**
		 * 엠블럼설정이 없는 경우 iModule 의 기본 엠블럼 이미지를 반환한다.
		 */
		if ($this->site->emblem == -1) return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/images/logo/emblem.png';
		
		/**
		 * 엠블럼을 사용하지 않는다고 설정한 경우 NULL 을 반환한다.
		 */
		if ($this->site->emblem == 0) return null;
		
		return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/attachment/view/'.$this->site->emblem.'/emblem.png';
	}
	
	/**
	 * 사이트 favicon 아이콘을 가져온다.
	 * 사이트 favicon이 지정되지 않은 경우 iModule 의 기본 favicon을 반환하고, 사용하지 않는다고 설정한 경우 NULL 을 반환한다.
	 *
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 포함하지 않은 URL(기본)
	 * @return string $faviconUrl favicon 아이콘 URL
	 */
	function getSiteFavicon($isFullUrl=false) {
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		/**
		 * favicon설정이 없는 경우 iModule 의 기본 favicon 아이콘을 반환한다.
		 */
		if ($this->site->favicon == -1) return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/images/logo/favicon.ico';
		
		/**
		 * favicon을 사용하지 않는다고 설정한 경우 NULL 을 반환한다.
		 */
		if ($this->site->emblem == 0) return null;
		
		return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/attachment/view/'.$this->site->favicon.'/favicon.ico';
	}
	
	/**
	 * 사이트의 mask 아이콘을 가져온다.
	 * mask 아이콘은 safari 웹브라우져의 고정탭 아이콘으로 사용되는 svg 이미지 파일이며, svg 이미지파일 경로 및 이미지파일 색상을 반환한다.
	 * 사이트 mask 아이콘이 지정되지 않은 경우 iModule 의 기본 mask 아이콘을 반환하고, 사용하지 않는다고 설정한 경우 NULL 을 반환한다.
	 *
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 포함하지 않은 URL(기본)
	 * @return object $maskIcon mask 아이콘 설정 {url:mask 아이콘 url, color : mask 아이콘 색상}
	 */
	function getMaskIcon($isFullUrl=false) {
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		$maskIcon = new stdClass();
		
		/**
		 * mask 아이콘설정이 없는 경우 iModule 의 기본 mask 아이콘을 사용한다.
		 */
		if ($this->site->maskicon->icon == -1) $maskIcon->url = ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/images/logo/maskicon.svg';
		
		/**
		 * mask 아이콘을 사용하지 않는다고 설정한 경우 NULL 을 반환한다.
		 */
		elseif ($this->site->maskicon->icon == 0) return null;
		else $maskIcon->url = ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/attachment/view/'.$this->site->maskicon->icon.'/maskicon.svg';
		
		$maskIcon->color = $this->site->maskicon->color;
		
		return $maskIcon;
	}
	
	/**
	 * 사이트 이미지를 가져온다.
	 * 사이트 이미지는 사이트 템플릿에 사용되거나, OG 메타태그를 구성하기 위해서 사용된다.
	 * 사용하고자 하는 경우에 따라 $type 값을 통해 이미지 최대 크기를 정할 수 있다.
	 * $type 이 original 일 경우 원본이미지를, view 일 경우 최대 가로사이즈 1000픽셀 이미지를, thumbnail 일 경우 최대 가로 사이즈 500픽셀 이미지를 반환한다.
	 * 설정된 사이트이미지가 없을 경우 NULL 을 반환한다.
	 *
	 * @param string $type 이미지 크기 종류(기본 original)
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 포함하지 않은 URL(기본)
	 * @return string $imageUrl 이미지 URL
	 */
	function getSiteImage($type='original',$isFullUrl=false) {
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		/**
		 * 사이트 이미지를 사용하지 않는다고 설정된 경우나, 알수없는 $type 값일 경우 NULL을 반환한다.
		 */
		if ($this->site->image == 0 || in_array($type,array('original','view','thumbnail')) == false) return null;
		
		/**
		 * 사이트 관리자에 설정된 로고파일을 가져온다.
		 */
		return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/attachment/'.$type.'/'.$this->site->logo->{$type}.'/preview.png';
	}
	
	/**
	 * 페이지 이미지를 가져온다.
	 * 페이지 이미지는 사이트 템플릿에 사용되거나, OG 메타태그를 구성하기 위해서 사용된다.
	 * 사용하고자 하는 경우에 따라 $type 값을 통해 이미지 최대 크기를 정할 수 있다.
	 * $type 이 original 일 경우 원본이미지를, view 일 경우 최대 가로사이즈 1000픽셀 이미지를, thumbnail 일 경우 최대 가로 사이즈 500픽셀 이미지를 반환한다.
	 * 설정된 페이지 이미지가 없을 경우 사이트 이미지를 가져온다.
	 *
	 * @param string $type 이미지 크기 종류(기본 original)
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 포함하지 않은 URL(기본)
	 * @return string $imageUrl 이미지 URL
	 */
	function getPageImage($type='original',$isFullUrl=false) {
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		/**
		 * 페이지명이 NULL 일 경우 1차 메뉴의 설정을 가져오고 페이지명이 있을 경우 2차 메뉴의 설정을 가져온다.
		 */
		$config = $this->page == null ? $this->getMenus($this->menu) : $this->getPages($this->menu,$this->page);
		
		/**
		 * 페이지 이미지가 설정되지 않은 경우, 사이트 이미지를 반환한다.
		 */
		if ($config->image == -1) return $this->getSiteImage($type,$isFullUrl);
		
		/**
		 * 페이지 이미지가 사용되지 않는다고 설정된 경우나, 알수없는 $type 값일 경우 NULL을 반환한다.
		 */
		if ($config->image == 0 || in_array($type,array('original','view','thumbnail')) == false) return null;
		
		return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/attachment/'.$type.'/'.$config->image.'/preview.png';
	}
	
	/**
	 * 뷰페이지 이미지를 가져온다.
	 * 뷰페이지 이미지는 사이트 템플릿에 사용되거나, OG 메타태그를 구성하기 위해서 사용된다.
	 * 사용하고자 하는 경우에 따라 $type 값을 통해 이미지 최대 크기를 정할 수 있다.
	 * $type 이 original 일 경우 원본이미지를, view 일 경우 최대 가로사이즈 1000픽셀 이미지를, thumbnail 일 경우 최대 가로 사이즈 500픽셀 이미지를 반환한다.
	 * 설정된 뷰페이지 이미지가 없을 경우 페이지 이미지를 가져온다.
	 *
	 * @param string $type 이미지 크기 종류(기본 original)
	 * @param boolean $isFullUrl true : 도메인을 포함한 전체 URL / false : 도메인을 포함하지 않은 URL(기본)
	 * @return string $imageUrl 이미지 URL
	 */
	function getViewImage($type='original',$isFullUrl=false) {
		/**
		 * 모듈등에서 설정되어 있는 뷰페이지 이미지가 없는 경우 페이지 이미지를 반환한다.
		 */
		if ($this->siteImage == null || $this->siteImage <= 0) return $this->getPageImage($type,$isFullUrl);
		
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		return ($isFullUrl == true ? $this->getHost(true) : __IM_DIR__).'/attachment/'.$type.'/'.$this->siteImage.'/preview.png';
	}
	
	/**
	 * 뷰페이지 이미지를 설정한다.
	 * 모듈 등에 의하여 특정 뷰페이지의 이미지를 변경할 수 있으며, 해당 이미지는 OG 메타태그를 구성하기 위해서 사용된다.
	 *
	 * @param int $image attachment 모듈에 의해 지정된 이미지파일의 고유 idx 값
	 * @return null
	 */
	function setViewImage($image) {
		$this->siteImage = $image;
	}
	
	/**
	 * 사이트 타이틀을 가져온다.
	 *
	 * @return string $siteTitle
	 */
	function getSiteTitle() {
		/**
		 * 모듈 등에서 지정된 사이트타이틀이 있을 경우 해당 타이틀을 반환한다.
		 */
		if ($this->siteTitle != null) return $this->siteTitle;
		
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		return $this->site->title;
	}
	
	/**
	 * 사이트 타이틀을 설정한다.
	 *
	 * @param string $title
	 * @param boolean $isIncludeSiteTitle 사이트 기본타이틀에 추가적으로 타이틀을 지정할 경우 true 로 설정한다.
	 * @return null
	 */
	function setSiteTitle($title,$isSiteTitle=true) {
		$title = strip_tags($title);
		$this->siteTitle = $isSiteTitle == true && $this->site != null ? $this->site->title.' - '.$title : $title;
	}
	
	/**
	 * 사이트 설명을 가져온다.
	 * META 태그 구성을 위해 사용된다.
	 *
	 * @return string $description
	 */
	function getSiteDescription() {
		/**
		 * 현재 접속한 사이트의 정보를 찾을 수 없는 경우 NULL 을 반환한다.
		 */
		if ($this->site == null) return null;
		
		/**
		 * 모듈 등에서 지정된 사이트설명 있을 경우 해당 설명을 반환한다.
		 */
		if ($this->siteDescription != null) return $this->siteDescription;
		
		/**
		 * 현재 접속한 메뉴에 설정된 설명이 있을 경우 해당 설명을 반환한다.
		 */
		$config = $this->page == null ? $this->getMenus($this->menu) : $this->getPages($this->menu,$this->page);
		if ($config != null && $config->description) return $config->description;
		
		return $this->site->description;
	}
	
	/**
	 * 사이트 설명을 설정한다.
	 *
	 * @param string $description
	 * @return null
	 */
	function setSiteDescription($description) {
		$this->siteDescription = $description;
	}
	
	/**
	 * 현재 페이지의 고유 URL 을 가져온다.
	 * 고유 URL은 반드시 사용중인 모듈에서 지정되어 있어야 하며, 그렇지 않을 경우 현재 URL이 반환된다.
	 * SEO를 위해 사용된다.
	 *
	 * @return string $canonical 고유 URL
	 */
	function getSiteCanonical() {
		return $this->siteCanonical !== null ? $this->siteCanonical : $this->getHost(false).$_SERVER['REQUEST_URI'];
	}
	
	/**
	 * 현재 페이지의 고유 URL 을 설정한다.
	 * SEO를 위해 사용된다.
	 *
	 * @param string $canonical 고유 URL 은 반드시 도메인을 포함한 전체 URL이어야 한다.
	 * @return null
	 */
	function setSiteCanonical($canonical) {
		$this->siteCanonical = $canonical;
	}
	
	/**
	 * 사이트 템플릿 객체를 반환한다.
	 *
	 * @return Templet $siteTemplet
	 */
	function getSiteTemplet() {
		if ($this->siteTemplet !== null) return $this->siteTemplet;
		$this->siteTemplet = $this->getTemplet($this,$this->getSite()->templet)->setConfigs(json_decode($this->getSite()->templet_configs));
		return $this->siteTemplet;
	}
	
	/**
	 * 사이트 템플릿을 제거한다.
	 * 특정 모듈에서 사이트템플릿 없이 모듈템플릿만으로 사이트화면을 구성할 경우 사용한다.
	 */
	function removeTemplet() {
		$this->useTemplet = false;
	}
	
	/**
	 * view 값을 가져온다.
	 * $baseUrl 값이 있을 경우, $baseUrl 에 설정된 view 를 무시하고 가져온다.
	 *
	 * @param string $baseUrl (옵션)
	 * @return string $view
	 */
	function getView($baseUrl=null) {
		if ($baseUrl != null) {
			$baseUrl = explode('/',str_replace($this->getUrl(false),'',$baseUrl));
			$baseView = count($baseUrl) > 3 ? $baseUrl[3] : null;
			$baseIdx = count($baseUrl) > 4 ? implode('/',array_slice($baseUrl,4)) : null;
		} else {
			$baseView = $baseIdx = null;
		}
		
		if ($baseView == null) return $this->view;
		
		$idx = $baseIdx == null ? $this->idx : str_replace($baseIdx,'',$this->idx);
		$idx = $idx ? explode('/',$idx) : array();
		
		return count($idx) > 0 ? $idx[0] : null;
	}
	
	/**
	 * idx 값을 가져온다.
	 * $baseUrl 값이 있을 경우, $baseUrl 에 설정된 idx 를 무시하고 가져온다.
	 *
	 * @param string $baseUrl (옵션)
	 * @return string $idx
	 */
	function getIdx($baseUrl=null) {
		if ($baseUrl != null) {
			$baseUrl = explode('/',str_replace($this->getUrl(false),'',$baseUrl));
			$baseView = count($baseUrl) > 3 ? $baseUrl[3] : null;
			$baseIdx = count($baseUrl) > 4 ? implode('/',array_slice($baseUrl,4)) : null;
		} else {
			$baseView = $baseIdx = null;
		}
		
		if ($baseUrl == null || $baseView == null) return $this->idx;
		$idx = $baseIdx == null ? explode('/',$this->idx) : explode('/',str_replace($baseIdx,'',$this->idx));
		
		return count($idx) > 1 ? implode('/',array_splice($idx,1)) : null;
	}
	
	/**
	 * 인덱스 URL을 반환한다.
	 *
	 * @return string $url
	 */
	function getIndexUrl() {
		return $this->indexUrl == null ? $this->getUrl(false) : $this->indexUrl;
	}
	
	/**
	 * 인덱스 페이지 URL 을 변경할 이유가 있을 경우 index url 을 변경한다.
	 * 에러메세지 템플릿 등에서 이용된다.
	 *
	 * @param string $url 변경할 index url
	 */
	function setIndexUrl($url) {
		$this->indexUrl = $url;
	}
	
	/**
	 * 현재 설정된 언어셋을 반환한다.
	 *
	 * @return $langcode
	 */
	function getLanguage() {
		return $this->language;
	}
	
	/**
	 * 코어의 언어셋을 지정한다.
	 *
	 * @param $langcode
	 */
	function setLanguage($language) {
		$this->language = $language;
	}
	
	/**
	 * 이 함수가 호출된 이후부터 강제로 $view 를 변경하여 출력한다.
	 * 한 페이지 내에서 2가지 view 를 사용할 경우 호출한다.
	 *
	 * @param string $view 변경할 view 코드
	 * @return null
	 */
	function setView($view) {
		$this->view = $view;
	}
	
	/**
	 * 이 함수가 호출된 이후부터 강제로 $idx 를 변경하여 출력한다.
	 * 한 페이지 내에서 2가지 idx 값을 사용할 경우 호출한다.
	 *
	 * @param string $idx 변경할 idx 코드
	 * @return null
	 */
	function setIdx($idx) {
		$this->idx = $idx;
	}
	
	/**
	 * 모듈의 컨테이너모드를 활성화한다.
	 */
	function setContainerMode($module,$container) {
		$this->container = $module.'/'.$container;
	}
	
	/**
	 * 사이트관리자에서는 기본적으로 ExtJS 라이브러리를 사용하나, 기타 사용자페이지에서 ExtJS 라이브러리를 로드할 경우 사용한다.
	 * ExtJS라이브러리의 기본적인 스타일시트와 현재 설정된 사이트 언어셋에 따른 언어셋을 호출한다.
	 */
	function loadExtJs() {
		$this->addHeadResource('style',__IM_DIR__.'/styles/extjs.css');
		$this->addHeadResource('style',__IM_DIR__.'/styles/extjs.extend.css');
		$this->addHeadResource('script',__IM_DIR__.'/scripts/extjs.js');
		$this->addHeadResource('script',__IM_DIR__.'/scripts/extjs.extend.js');
	}
	
	/**
	 * 자바스크립트용 언어셋 파일을 호출한다.
	 * 언어셋은 기본적으로 PHP파일을 통해 사용되나 모듈의 자바스크립트에서 언어셋이 필요할 경우 이 함수를 호출하여 자바스크립트상에서 대상.getText() 함수로 언어셋을 불러올 수 있다.
	 *
	 * @param string $type 불러올 대상의 종류 (module, addon, widget)
	 * @param string $module 불러올 대상의 이름
	 * @param string $defaultLanguage 불러올 대상의 기본 언어
	 */
	function loadLanguage($type,$target,$defaultLanguage) {
		$this->javascriptLanguages[] = $type.'@'.$target.'@'.$defaultLanguage;
	}
	
	/**
	 * 웹폰트 스타일시트를 불러온다.
	 *
	 * @param string $font 웹폰트명 (웹폰트명은 /styles/fonts 폴더에 정의되어있으며, 폰트파일은 /fonts 폴더에 존재)
	 * @param boolean $isDefault 사이트 기본폰트로 사용할지 여부
	 */
	function loadWebFont($font,$isDefault=false) {
		if (in_array($font,$this->webFont) == false) $this->webFont[] = $font;
		if ($isDefault == true) $this->webFontDefault = $font;
	}
	
	/**
	 * 언어별로 기본서체를 불러온다.
	 * 사이트템플릿에 영향을 받지 않은 곳에서만 사용된다. (예 : 에러메세지, 관리자화면 등)
	 */
	function loadFont() {
		if ($this->language == 'ko') {
			$this->loadWebFont('NanumBarunGothic',true);
			$this->loadWebFont('OpenSans');
		} else {
			$this->loadWebFont('OpenSans',true);
		}
	}
	
	/**
	 * 사이트 <HEAD> 태그 내부의 리소스를 추가한다.
	 *
	 * @param string $type 리소스종류 (style, script, meta or etc)
	 * @param string[] $value 리소스데이터 (style, script 의 경우 해당 파일의 경로 / 기타 태그의 경우 태그 attribute)
	 * @param boolean $isFirst 처음 호출할지 여부
	 */
	function addHeadResource($type,$value,$isFirst=false) {
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
		
		if ($tag != null && in_array($tag,$this->siteHeader) == false) {
			if ($isFirst === true) array_unshift($this->siteHeader,$tag);
			else array_push($this->siteHeader,$tag);
		}
	}
	
	/**
	 * 사이트 <HEAD> 내부 태그를 가져온다.
	 *
	 * @return string $header HEAD HTML
	 * @todo 캐싱 기능 (로드되는 모든 파일을 하나의 파일로 통합)
	 */
	function getHeadResource() {
		/**
		 * 기본 스타일시트를 불러온다.
		 * 사이트가 존재하고, 사이트템플릿에 common.css 파일이 정의되어 있을 경우 사이트템플릿의 common.css 파일을 불러온다.
		 */
		if ($this->site != null && is_file($this->getSiteTemplet()->getPath().'/styles/common.css') == true) {
			$this->addHeadResource('style',$this->getSiteTemplet()->getPath().'/styles/common.css',true);
		} else {
			$this->addHeadResource('style',__IM_DIR__.'/styles/common.css',true);
		}
		
		$this->addHeadResource('style',__IM_DIR__.'/styles/responsive.css',true);
		
		/**
		 * 자바스크립트 언어셋 요청이 있을 경우 언어셋파일을 자바스크립트로 불러온다.
		 */
		if (count($this->javascriptLanguages) > 0) {
			$this->addHeadResource('script',__IM_DIR__.'/scripts/language.js.php?language='.$this->language.'&languages='.implode(',',$this->javascriptLanguages));
		} else {
			$this->addHeadResource('script',__IM_DIR__.'/scripts/language.js.php?language='.$this->language);
		}
		
		/**
		 * PHP 설정값들 중 자바스크립트에 필수적으로 필요한 정보를 불러온다.
		 */
		$this->addHeadResource('script',__IM_DIR__.'/scripts/php2js.js.php?language='.$this->language.'&menu='.($this->menu != null && $this->menu != '#' ? $this->menu : '').'&page='.($this->page != null && $this->page != '#' ? $this->page : '').'&view='.($this->view != null ? $this->view : '').'&container='.($this->container != null ? $this->container : ''));
		
		/**
		 * 웹폰트 요청이 있을 경우 웹폰트 스타일시트를 불러온다.
		 */
		$this->addHeadResource('style',__IM_DIR__.'/styles/font.css.php?language='.$this->language.'&font='.implode(',',$this->webFont).($this->webFontDefault != null ? '&default='.$this->webFontDefault : ''));
		
		return implode(PHP_EOL,$this->siteHeader).PHP_EOL;
	}
	
	/**
	 * 전체게시물에 게시물데이터를 추가한다.
	 *
	 * @param string $module 모듈명
	 * @param string $context 모듈의 컨텍스트명
	 * @param string $type 게시물 종류
	 * @param int $idx 게시물 고유번호
	 * @param int $reg_date 게시물 등록일 또는 갱신일(UNIXTIME)
	 */
	function setArticle($module,$context,$type,$idx,$reg_date) {
		$check = $this->db()->select($this->table->article)->where('module',$module)->where('type',$type)->where('idx',$idx)->get();
		if ($check == null) {
			$this->db()->insert($this->table->article,array('module'=>$module,'context'=>$context,'type'=>$type,'idx'=>$idx,'reg_date'=>$reg_date,'update_date'=>$reg_date))->execute();
		} else {
			$this->db()->update($this->table->article,array('context'=>$context,'update_date'=>$reg_date))->where('module',$module)->where('type',$type)->where('idx',$idx)->execute();
		}
	}
	
	/**
	 * 전체게시물에 추가되어 있는 기존 게시물을 삭제한다.
	 *
	 * @param string $module 모듈명
	 * @param string $type 게시물 종류
	 * @param int $idx 게시물 고유번호
	 */
	function deleteArticle($module,$type,$idx) {
		$this->db()->delete($this->table->article)->where('module',$module)->where('type',$type)->where('idx',$idx)->execute();
	}
	
	/**
	 * 사이트 레이아웃 구성에 문제가 없는 에러가 모듈, 위젯, 에드온 등에서 발생하였을 경우, 에러메세지 HTML 을 가져온다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param string $message(옵션) 변환된 에러메세지
	 * @return $html 에러메세지 HTML
	 */
	function getError($code,$value=null,$message=null) {
		/**
		 * 사이트 구성에 실패한 상태일 경우 printError()를 호출한다.
		 */
		if ($this->site == null) return $this->printError($code,$value,$message);
		
		/**
		 * 에러메세지를 구성한다.
		 */
		if (is_object($code) == true) {
			$message = $code->message;
			$description = $code->description;
			$type = $code->type;
		} else {
			$error = $this->getErrorText($code,$value,$message,true);
			$message = $error->message;
			$description = $error->description;
			$type = $error->type;
		}

		$link = new stdClass();
		$link->url = $type == 'MAIN' || isset($_SERVER['HTTP_REFERER']) == false || $_SERVER['HTTP_REFERER'] == $this->getHost(true).$_SERVER['REDIRECT_URL'] ? $this->getUrl(false) : $_SERVER['HTTP_REFERER'];
		$link->text = $type == 'MAIN' || isset($_SERVER['HTTP_REFERER']) == false || $_SERVER['HTTP_REFERER'] == $this->getHost(true).$_SERVER['REDIRECT_URL'] ? $this->getText('button/back_to_main') : $this->getText('button/go_back');
		
		/**
		 * 사이트템플릿에 에러메세지 템플릿이 있을 경우, 사이트템플릿을 불러온다.
		 */
		ob_start();
		$IM = $this;
		if (is_file($this->getSiteTemplet()->getPath().'/error.php') == true) {
			INCLUDE $this->getSiteTemplet()->getPath().'/error.php';
		} else {
			$this->addHeadResource('style',__IM_DIR__.'/styles/error.css');
			INCLUDE __IM_PATH__.'/includes/error.php';
		}
		$html = ob_get_contents();
		ob_end_clean();
		
		/**
		 * 이벤트를 발생시킨다.
		 */
		$values = new stdClass();
		$values->message = $message;
		$values->description = $description;
		$values->type = $type;
		$values->link = $link;
		$this->fireEvent('afterGetContext','core','error',$values,null,$html);
		
		if ($type == 'LOGIN') {
			$header = PHP_EOL.'<form id="ErrotForm">'.PHP_EOL;
			$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>$("#ErrotForm").inits(Member.login);</script>'.PHP_EOL;
			
			$html = $header.$html.$footer;
		}
		
		return $html;
	}
	
	/**
	 * 에러메세지를 출력하고, 사이트 레이아웃 렌더링을 즉시 중단한다.
	 *
	 * @param string $code 에러코드
	 * @param object $value(옵션) 에러와 관련된 데이터
	 * @param string $message(옵션) 변환된 에러메세지
	 * @return null
	 */
	function printError($code=null,$value=null,$message=null,$is_force_html=false) {
		if (isset($_SERVER['SCRIPT_NAME']) == true && in_array($_SERVER['SCRIPT_NAME'],array('/scripts/php2js.js.php')) == true) exit;
		
		if (is_string($code) == true) {
			if (preg_match('/^NOT_FOUND/',$code) == true) {
				header('HTTP/1.1 404 Not Found');
			} elseif (preg_match('/^FORBIDDEN/',$code) == true) {
				header('HTTP/1.1 403 Forbidden');
			}
		}
		
		if (preg_match('/\/(api|process)\/index\.php/',$_SERVER['SCRIPT_NAME'],$match) == true && $is_force_html == false) {
			$results = new stdClass();
			$results->success = false;
			
			if ($match[1] == 'api') {
				$results->error = $code;
				$results->message = $value;
			} elseif ($match[1] == 'process') {
				$this->language = Request('_language');
				$results->message = $this->getErrorText($code,$value,$message);
			}
			
			exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		}
		
		if ($this->language == null) {
			$this->language = 'ko';
		}
		/*
		if (preg_match('/\/(api|process)\/index\.php/',$_SERVER['PHP_SELF']) == true) {
			$results = new stdClass();
			$results->success = false;
			$results->message = $this->getErrorText($code,$value,$message);
			exit(json_encode($results,JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
		}
		*/
		$this->setSiteTitle('ERROR!');
		$this->addHeadResource('style',__IM_DIR__.'/styles/common.css');
		$this->addHeadResource('style',__IM_DIR__.'/styles/error.css');
		
		$this->loadFont();
		
		/**
		 * 에러메세지를 구성한다.
		 */
		if (is_object($code) == true) {
			$message = $code->message;
			$description = $code->description;
			$type = $code->type;
		} else {
			$error = $this->getErrorText($code,$value,$message,true);
			$message = $error->message;
			$description = $error->description;
			$type = $error->type;
		}
		
		$link = new stdClass();
		$link->url = $type == 'MAIN' || isset($_SERVER['HTTP_REFERER']) == false || $_SERVER['HTTP_REFERER'] == $this->getHost(true).$_SERVER['REDIRECT_URL'] ? $this->getUrl(false) : $_SERVER['HTTP_REFERER'];
		$link->text = $type == 'MAIN' || isset($_SERVER['HTTP_REFERER']) == false || $_SERVER['HTTP_REFERER'] == $this->getHost(true).$_SERVER['REDIRECT_URL'] ? $this->getText('button/back_to_main') : $this->getText('button/go_back');
		
		/**
		 * 에러메세지 컨테이너를 설정한다.
		 */
		$context = PHP_EOL.'<div data-role="error" data-type="core">'.PHP_EOL;
		
		$IM = $this;
		INCLUDE __IM_PATH__.'/includes/error.php';
		$context.= ob_get_contents();
		ob_end_clean();
		
		/**
		 * 에러메세지 컨테이너를 설정한다.
		 */
		$context.= PHP_EOL.'</div>'.PHP_EOL;
		
		if ($type == 'LOGIN') {
			$header = PHP_EOL.'<form id="ErrotForm">'.PHP_EOL;
			$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>$("#ErrotForm").inits(Member.login);</script>'.PHP_EOL;
			
			$context = $header.$context.$footer;
		}
		
		/**
		 * 기본 헤더파일을 불러온다.
		 */
		INCLUDE __IM_PATH__.'/includes/header.php';
		
		/**
		 * 에러메세지를 출력한다.
		 */
		echo $context;
		
		/**
		 * 기본 푸터파일을 불러온다.
		 */
		INCLUDE __IM_PATH__.'/includes/footer.php';
		
		exit;
	}
	
	/**
	 * 사이트 레이아웃 헤더 HTML 코드를 가져온다.
	 *
	 * @return string $headerHTML
	 */
	function getHeader() {
		if (defined('__IM_HEADER_INCLUDED__') == true) return;
		$site = $this->getSite();
		
		/**
		 * 사이트 설명 META 태그 및 고유주소 META 태그를 정의한다. (SEO)
		 */
		if ($this->getSiteDescription()) $this->addHeadResource('meta',array('name'=>'description','content'=>$this->getSiteDescription()));
		$this->addHeadResource('link',array('rel'=>'canonical','href'=>$this->getSiteCanonical()));
		
		/**
		 * 모바일기기 및 애플 디바이스를 위한 TOUCH-ICON 태그를 정의한다.
		 */
		if ($this->getSiteEmblem() !== null) {
			$this->addHeadResource('link',array('rel'=>'apple-touch-icon','sizes'=>'57x57','href'=>$this->getSiteEmblem(true)));
			$this->addHeadResource('link',array('rel'=>'apple-touch-icon','sizes'=>'114x114','href'=>$this->getSiteEmblem(true)));
			$this->addHeadResource('link',array('rel'=>'apple-touch-icon','sizes'=>'72x72','href'=>$this->getSiteEmblem(true)));
			$this->addHeadResource('link',array('rel'=>'apple-touch-icon','sizes'=>'144x144','href'=>$this->getSiteEmblem(true)));
		}
		
		/**
		 * 사이트 Favicon 태그를 정의한다.
		 */
		if ($this->getSiteFavicon() !== null) {
			$this->addHeadResource('link',array('rel'=>'shortcut icon','type'=>'image/x-icon','href'=>$this->getSiteFavicon(true)));
		}
		
		/**
		 * Safari 브라우저를 위한 Mask아이콘 태그를 정의한다.
		 */
		if ($this->getMaskIcon() !== null) {
			$this->addHeadResource('link',array('rel'=>'mask-icon','href'=>$this->getMaskIcon()->url,'color'=>$this->getMaskIcon()->color));
		}
		
		/**
		 * 템플릿을 불러온다.
		 */
		return $this->getSiteTemplet()->getHeader(get_defined_vars());
	}
	
	/**
	 * 사이트 레이아웃 푸터 HTML 코드를 가져온다.
	 *
	 * @return string $footerHTML
	 */
	function getFooter() {
		if (defined('__IM_FOOTER_INCLUDED__') == true) return;
		$site = $this->getSite();
		
		/**
		 * 템플릿을 불러온다.
		 */
		return $this->getSiteTemplet()->getFooter(get_defined_vars());
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
		 * 사이트맵 구성을 사용하는 모듈의 경우 바로 해당 모듈의 컨텍스트를 호출한다.
		 */
		if (strpos($this->getSite()->templet,'#') === 0 && $this->getModule()->isSitemap(substr($this->getSite()->templet,1)) == true) {
			return $this->getModule(substr($this->getSite()->templet,1))->getContext();
		}
		
		/**
		 * 페이지명이 NULL 일 경우 1차 메뉴의 설정을 가져오고 페이지명이 있을 경우 2차 메뉴의 설정을 가져온다.
		 */
		$config = $page == null ? $this->getMenus($menu) : $this->getPages($menu,$page);
		if ($config == null) return $this->printError('NOT_FOUND_PAGE');
		
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
			$this->page = $config->context->page;
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
			return $this->getModule($config->context->module)->getContext($config->context->context,$config->context->configs);
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
		 * 사이트맵 구성을 사용하는 모듈의 경우 바로 해당 모듈에서 레이아웃을 처리한다.
		 */
		if (strpos($this->getSite()->templet,'#') === 0 && $this->getModule()->isSitemap(substr($this->getSite()->templet,1)) == true) {
			return $context;
		}
		
		/**
		 * 페이지명이 NULL 일 경우 1차 메뉴의 설정을 가져오고 페이지명이 있을 경우 2차 메뉴의 설정을 가져온다.
		 */
		$config = $page == null ? $this->getMenus($menu) : $this->getPages($menu,$page);
		if ($config == null) return $context;
		
		/**
		 * 사이트 레이아웃을 사용하지 않는다고 선언된 경우 ($this->useTemplet 값이 false) 컨텍스트 HTML 코드를 그대로 반환한다.
		 */
		if ($this->useTemplet == false) return $context;
		/**
		 * 템플릿의 레이아웃을 불러온다.
		 */
		return $this->getSiteTemplet()->getLayout($config->layout,$context);
	}
	
	/**
	 * 외부 PHP 파일내용을 가져온다.
	 *
	 * @param string $external 외부 PHP 파일명
	 * @return string $context 컨텍스트 HTML
	 */
	function getExternalContext($external) {
		return $this->getSiteTemplet()->getExternal($external);
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
	 * 메뉴나 컨텍스트 아이콘 설정값을 <i> 태그로 파싱한다.
	 *
	 * @param string $icon 아이콘 설정값
	 * @return string $iconHtml <i> 태그
	 */
	function parseIconString($icon) {
		if ($icon == null || strlen($icon) == 0) return '';
		
		/**
		 * 웹폰트 아이콘일 경우
		 * fa, xi, xi2 의 경우만 현재 지원한다.
		 */
		if (preg_match('/^(fa|xi|xi2) /i',$icon,$match) == true) {
			$fontName = array('fa'=>'FontAwesome','xi'=>'XEIcon','xi2'=>'XEIcon2');
			$this->loadWebFont($fontName[$match[1]]);
			return '<i class="icon '.$icon.'"></i>';
		}
		
		/**
		 * 이미지 파일일 경우
		 * 아이콘설정값이 .gif, .jpg, .jpeg, .png, .svg 로 끝나는 경우
		 */
		if (preg_match('/\.(gif|jpg|jpeg|png|svg)$/i',$icon) == true) {
			return '<i class="icon" style="backgroundImage:url('.$icon.');"></i>';
		}
		
		return '';
	}
	
	/**
	 * 권한코드를 해석하여 권한이 존재하는지 확인한다.
	 *
	 * @param string $permssionString 권한코드
	 * @return boolean $hasPermission true : 권한이 있는 경우 / false : 권한이 없는 경우
	 */
	function parsePermissionString($permissionString) {
		$member = $this->getModule('member')->getMember();
		if ($member->type == 'ADMINISTRATOR') return true;
		
		// replace code
		if ($member->idx == 0) {
			$permissionString = str_replace('{$member.level}','0',$permissionString);
			$permissionString = str_replace('{$member.type}',"'GUEST'",$permissionString);
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
	 * 웹브라우져의 캐싱기능을 막는다.
	 */
	function preventCache() {
		header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', FALSE);
		header('Pragma: no-cache');
	}
	
	/**
	 * 권한코드가 제대로 입력되었는지 확인한다.
	 * 권한코드는 EVAL 함수로 PHP에서 직접 실행되기 때문에 유효성 검증이 필요하다.
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
			return str_replace('{code}',$match[0],$this->getErrorText('UNKNWON_CODE_IN_PERMISSION_STRING',$match[0]));
		}
		
		// check doubleQuotation
		if (preg_match('/"/',$permissionString) == true) {
			return $this->getErrorText('NOT_ALLOWED_DOUBLE_QUOTATION_IN_PERMISSION_STRING');
		}
		
		// eval check
		ob_start();
		$check = eval("return {$permissionString};");
		$content = ob_get_contents();
		ob_end_clean();
		
		if ($content) return $this->getErrorText('PERMISSION_STRING_PARSING_FAILED');
		if (is_bool($check) == false) return $this->getText('NOT_BOOLEAN_PERMISSION_STRING_RESULT');
		
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
		 * 사이트내 글로벌하게 동작하도록 설정된 모듈(예 : member, push 등)을 불러온다.
		 */
		$this->getModule()->loadGlobals();
		
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
		 * PHP 에러가 발생하지 않았다면, 사이트 HTML 코드를 출력한다.
		 */
		$error = error_get_last();
		if ($error == null || $error['type'] == E_NOTICE) {
			echo $html;
		} else {
			$this->printError('PHP_ERROR',$error);
		}
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
		return $this->Event->getEventListeners($event,$target,$caller);
	}
	
	/**
	 * 특정 시점에서 이벤트를 발생시킨다.
	 * 발생된 이벤트는 모듈이나 에드온 등에서 정의된 이벤트리스너를 호출하게 되고, 이벤트 리스너 내부에서 전달된 값들을 수정하여 최정결과값에 반영할 수 있다.
	 * 이벤트 발생 및 이벤트 처리는 Event 클래스에서 관여한다.
	 *
	 * @param string $event 이벤트 타입 (afterInitContext or afterDoProcess ... etc.)
	 * @param string $target 이벤트를 발생시킨 대상 (core 또는 모듈명)
	 * @param string $caller 이벤트를 발생시킨 지점 (보통 이벤트를 발생시킨 함수명)
	 * @param object $values 이벤트 리스너에게 전달시켜줄 데이터
	 * @param object $results 일부 이벤트종류는 결과값을 가진다. (대표적으로 doProcess 에 관련된 이벤트)
	 * @param string &$context 레이아웃에 관여하는 이벤트는 현재까지 파싱된 컨텍스트나 레이아웃 HTML 코드를 전달한다. (call by reference)
	 */
	function fireEvent($event,$target,$caller,$values=null,$results=null,&$context=null) {
		$this->Event->fireEvent($event,$target,$caller,$values,$results,$context);
	}
}
?>