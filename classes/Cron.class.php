<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * iModule 에서 주기적으로 처리해야하는 데이터를 crontab 으로 실행한다.
 * 
 * @file /classes/Cron.class.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 8. 22.
 */
class Cron {
	/**
	 * DB 관련 변수정의
	 *
	 * @private string[] $table DB 테이블 별칭 및 원 테이블명을 정의하기 위한 변수
	 */
	private $table;
	
	/**
	 * 크론작업을 할 사이트 호스트
	 */
	private $hosts = array();
	
	function __construct($hosts=array()) {
		/**
		 * 전역변수 설정
		 */
		define('__IM_CRON__',true);
		
		/**
		 * 크론작업을 할 사이트 호스트
		 */
		$this->hosts = count($hosts) == 0 ? array() : $hosts;
		
		/**
		 * DB 테이블 별칭 정의
		 */
		$this->table = new stdClass();
		$this->table->cron = 'cron_table';
	}
	
	/**
	 * 모듈 설치시 정의된 DB코드를 사용하여 모듈에서 사용할 전용 DB클래스를 반환한다.
	 *
	 * @return DB $DB
	 */
	function db() {
		if ($this->DB == null || $this->DB->ping() === false) $this->DB = $this->IM->db();
		return $this->DB;
	}
	
	/**
	 * 정해진 작업을 실행한다.
	 */
	function run($host=null) {
		global $_CONFIGS;
		
		if ($host == null) {
			/**
			 * 웹상에서 동작될 경우 작업을 중단한다.
			 */
			if (isset($_SERVER['HTTP_HOST']) == true) exit;
			
			/**
			 * 크론작업을 할 호스트별 정해진 작업을 실행한다.
			 */
			for ($i=0, $loop=count($this->hosts);$i<$loop;$i++) {
				$this->run($this->hosts[$i]);
			}
		} else {
			$_SERVER['HTTP_HOST'] = $host;
			
			REQUIRE_ONCE __IM_CRON_PATH__.'/configs/init.config.php';
			
			$IM = new iModule();
			$site = $IM->getSite(false);
			
			$hour = date('G');
			
			/**
			 * 크론작업이 필요한 모듈을 불러온다.
			 */
			$modules = $IM->getModule()->getCronModules();
			for ($i=0, $loop=count($modules);$i<$loop;$i++) {
				$this->hourly($IM,$modules[$i]->module);
				if ($hour == 4) $this->daily($IM,$modules[$i]->module);
				if ($hour == 5) $this->weekly($IM,$modules[$i]->module);
			}
		}
	}
	
	/**
	 * 모듈별 일별 작업을 실행한다.
	 */
	function hourly($IM,$module) {
		$me = $IM->getModule($module);
		
		/**
		 * 시간별 작업파일이 있는지 확인한다.
		 */
		if (is_file($me->getModule()->getPath().'/crons/hourly.php') == true) {
			$start_date = time();
			$start_time = $IM->getMicroTime();
			
			ob_start();
			INCLUDE $me->getModule()->getPath().'/crons/hourly.php';
			$result = ob_get_clean();
			
			$end_date = time();
			$runtime = $IM->getMicroTime() - $start_time;
			
			if ($result) $IM->db()->replace($this->table->cron,array('host'=>$_SERVER['HTTP_HOST'],'module'=>$module,'type'=>'HOURLY','date'=>date('Y-m-d'),'result'=>$result,'start_date'=>$start_date,'end_date'=>$end_date,'runtime'=>$runtime))->execute();
		}
	}
	
	/**
	 * 모듈별 일별 작업을 실행한다.
	 */
	function daily($IM,$module) {
		$me = $IM->getModule($module);
		
		/**
		 * 일별 작업파일이 있는지 확인한다.
		 */
		if (is_file($me->getModule()->getPath().'/crons/daily.php') == true) {
			$start_date = time();
			$start_time = $IM->getMicroTime();
			
			ob_start();
			INCLUDE $me->getModule()->getPath().'/crons/daily.php';
			$result = ob_get_clean();
			
			$end_date = time();
			$runtime = $IM->getMicroTime() - $start_time;
			
			if ($result) $IM->db()->replace($this->table->cron,array('host'=>$_SERVER['HTTP_HOST'],'module'=>$module,'type'=>'DAILY','date'=>date('Y-m-d'),'result'=>$result,'start_date'=>$start_date,'end_date'=>$end_date,'runtime'=>$runtime))->execute();
		}
	}
	
	/**
	 * 모듈별 주별 작업을 실행한다.
	 */
	function weekly($IM,$module) {
		$week = date('w');
		
		$me = $IM->getModule($module);
		
		/**
		 * 주별 작업파일이 있는지 확인한다.
		 */
		if (is_file($me->getModule()->getPath().'/crons/weekly.'.$week.'.php') == true) {
			$start_date = time();
			$start_time = $IM->getMicroTime();
			
			ob_start();
			INCLUDE $me->getModule()->getPath().'/crons/weekly.'.$week.'.php';
			$result = ob_get_clean();
			
			$end_date = time();
			$runtime = $IM->getMicroTime() - $start_time;
			
			if ($result) $IM->db()->replace($this->table->cron,array('host'=>$_SERVER['HTTP_HOST'],'module'=>$module,'type'=>'WEEKLY','date'=>date('Y-m-d'),'result'=>$result,'start_date'=>$start_date,'end_date'=>$end_date,'runtime'=>$runtime))->execute();
		}
	}
}
?>