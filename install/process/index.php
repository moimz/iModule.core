<?php
/**
 * 이 파일은 iModule 의 일부입니다. (https://www.imodules.io)
 *
 * iModule의 설치작업을 처리한다.
 * 
 * @file /install/process/index.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2019. 4. 8.
 */
REQUIRE_ONCE str_replace(DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.'process','',__DIR__).'/configs/init.config.php';
header("Content-type: text/json; charset=utf-8",true);

set_time_limit(0);
@ini_set('memory_limit',-1);
@ini_set('zlib.output_compression','Off');
@ini_set('output_buffering','Off');
@ini_set('output_handler','');
if (function_exists('apache_setenv') == true) {
	@apache_setenv('no-gzip',1);
}

$action = Request('action');
$results = new stdClass();
if ($action == 'dependency') {
	$dependency = Request('dependency');
	$version = Request('version');
	
	$check = CheckDependency($dependency,$version);
	$results->success = true;
	$results->installed = $check->installed;
	$results->installedVersion = $check->installedVersion;
	$results->dependency = $dependency;
	$results->version = $version;
}

if ($action == 'requirement') {
	$requirement = Request('requirement');
	$version = Request('version');
	
	if (is_dir(__IM_PATH__.'/'.$requirement) == true && is_file(__IM_PATH__.'/'.$requirement.'/package.json') == true) {
		$package = json_decode(file_get_contents(__IM_PATH__.'/'.$requirement.'/package.json'));
		if ($package == null) {
			$results->success = true;
			$results->installed = false;
			$results->installedVersion = null;
		} else {
			$results->success = true;
			$results->installed = true;
			$results->installedVersion = $package->version;
		}
	} else {
		$results->success = true;
		$results->installed = false;
		$results->installedVersion = null;
	}
	
	$results->requirement = $requirement;
	$results->version = $version;
}

if ($action == 'directory') {
	$directory = Request('directory');
	$permission = Request('permission');
	
	$results->success = true;
	$results->directory = $directory;
	$results->created = CheckDirectoryPermission(strpos($directory,'/') === 0 ? $directory : __IM_PATH__.DIRECTORY_SEPARATOR.$directory,$permission);
	$results->permission = $permission;
}

if ($action == 'config') {
	$config = Request('config');
	$results->success = true;
	$results->config = $config;
	$results->not_exists = !is_file(__IM_PATH__.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.$config.'.config.php');
}

if ($action == 'preset') {
	$preset = Request('preset');
	$results->success = true;
	$results->preset = $preset;
	$results->not_exists = !is_file(__IM_PATH__.DIRECTORY_SEPARATOR.$preset.'.preset.php');
	$results->configs = new stdClass();
	$results->configs->key = $_CONFIGS->presets->key;
	$results->configs->db = $_CONFIGS->presets->db;
}

if ($action == 'install') {
	REQUIRE_ONCE __IM_PATH__.'/classes/DB/mysql.class.php';
	
	$language = Request('language');
	$package = json_decode(file_get_contents(__IM_PATH__.'/package.json'));
	
	$errors = array();
	if ($_CONFIGS->presets->key == true) {
		if (Request('key') != $_CONFIGS->key) $errors['key'] = 'key_preset';
	} elseif (is_file(__IM_PATH__.'/configs/key.config.php') == true) {
		$keyFile = explode("\n",file_get_contents(__IM_PATH__.'/configs/key.config.php'));
		$key = $keyFile[1];
		if (Request('key') != $key) $errors['key'] = 'key_exists';
	} else {
		$key = Request('key') ? Request('key') : $errors['key'] = 'key';
	}
	$admin_email = Request('admin_email') ? Request('admin_email') : $errors['admin_email'] = 'admin_email';
	$admin_password = Request('admin_password') ? Request('admin_password') : $errors['admin_password'] = 'admin_password';
	$admin_name = Request('admin_name') ? Request('admin_name') : $errors['admin_name'] = 'admin_name';
	$admin_nickname = Request('admin_nickname') ? Request('admin_nickname') : $errors['admin_nickname'] = 'admin_nickname';
	
	if ($_CONFIGS->presets->key == true) {
		$db = $_CONFIGS->db;
	} elseif (is_file(__IM_PATH__.'/configs/db.config.php') == true) {
		$dbFile = explode("\n",file_get_contents(__IM_PATH__.'/configs/db.config.php'));
		$db = json_decode(Decoder($dbFile[1],$key));
	} else {
		$db = new stdClass();
		$db->type = 'mysql';
		$db->host = Request('db_host');
		$db->port = Request('db_port');
		$db->username = Request('db_id');
		$db->password = Request('db_password');
		$db->database = Request('db_name');
	}
	
	if ($db->type == 'mysql') {
		$mysqli = new mysql();
		if ($mysqli->check($db) === false) {
			$errors['db_host'] = $errors['db_id'] = $errors['db_password'] = $errors['db_name'] = 'db';
		} else {
			$dbConnect = new mysql($db);
			$dbConnect->setPrefix(__IM_DB_PREFIX__);
			$dbConnect->connect();
			
			$check = $dbConnect->exists('member_table') == true && $dbConnect->select('member_table')->where('email',$admin_email)->where('idx',1,'>')->has();
			if ($check == true) $errors['admin_email'] = 'admin_email_exists';
		}
	}
	
	if (count($errors) == 0) {
		$results->success = false;
		
		if ($_CONFIGS->presets->key == false) $keyFile = @file_put_contents(__IM_PATH__.'/configs/key.config.php','<?php /*'.PHP_EOL.$key.PHP_EOL.'*/ ?>');
		else $keyFile = true;
		if ($_CONFIGS->presets->db == false) $dbFile = @file_put_contents(__IM_PATH__.'/configs/db.config.php','<?php /*'.PHP_EOL.Encoder(json_encode($db),$key).PHP_EOL.'*/ ?>');
		else $dbFile = true;
		
		if (is_dir(__IM_PATH__.'/attachments/cache') == false) {
			mkdir(__IM_PATH__.'/attachments/cache',0707);
		}
		
		if (is_dir(__IM_PATH__.'/attachments/temp') == false) {
			mkdir(__IM_PATH__.'/attachments/temp',0707);
		}
		
		if ($keyFile !== false && $dbFile !== false) {
			if (CreateDatabase($dbConnect,$package->databases) == true) {
				if ($dbConnect->select('site_table')->count() == 0) {
					$dbConnect->insert('site_table',array('domain'=>$_SERVER['HTTP_HOST'],'language'=>$language,'title'=>'iModule','description'=>'Site Description','templet'=>'default','templet_configs'=>'{}','logo'=>'{"default":-1,"footer":-1}','is_ssl'=>(isset($_SERVER['HTTPS']) == true ? 'TRUE' : 'FALSE'),'is_default'=>'TRUE','sort'=>0))->execute();
				} else {
					$dbConnect->update('site_table',array('templet_configs'=>'{}'))->where('templet_configs','')->execute();
				}
				
				if ($dbConnect->select('sitemap_table')->count() == 0) {
					$dbConnect->insert('sitemap_table',array('domain'=>$_SERVER['HTTP_HOST'],'language'=>$language,'menu'=>'index','page'=>'','title'=>'INDEX','type'=>'EXTERNAL','layout'=>'index','context'=>'{"external":"\/templets\/default\/externals\/index.php"}','sort'=>0))->execute();
				}
				
				$IM = new iModule();
				$IM->init();
				
				$results->success = true;
				if ($results->success == true) {
					$installed = $IM->Module->install('attachment');
					if ($installed !== true) {
						$results->success = false;
						$results->message = $installed;
						$results->target = 'attachment';
					}
				}
				
				if ($results->success == true) {
					$installed = $IM->Module->install('push');
					if ($installed !== true) {
						$results->success = false;
						$results->message = $installed;
						$results->target = 'push';
					}
				}
				
				if ($results->success == true) {
					$installed = $IM->Module->install('keyword');
					if ($installed !== true) {
						$results->success = false;
						$results->message = $installed;
						$results->target = 'keyword';
					}
				}
				
				if ($results->success == true) {
					$installed = $IM->Module->install('wysiwyg');
					if ($installed !== true) {
						$results->success = false;
						$results->message = $installed;
						$results->target = 'wysiwyg';
					}
				}
				
				if ($results->success == true) {
					$installed = $IM->Module->install('email');
					if ($installed !== true) {
						$results->success = false;
						$results->message = $installed;
						$results->target = 'email';
					}
				}
				
				if ($results->success == true) {
					$installed = $IM->Module->install('member',null,'default',false);
					if ($installed !== true) {
						$results->success = false;
						$results->message = $installed;
						$results->target = 'member';
					} else {
						$mHash = new Hash();
						$password = $mHash->password_hash($admin_password);
						
						if ($dbConnect->select('member_table')->where('idx',1)->has() == true) {
							$dbConnect->update('member_table',array('domain'=>'*','type'=>'ADMINISTRATOR','email'=>$admin_email,'password'=>$password,'name'=>$admin_name,'nickname'=>$admin_nickname,'status'=>'ACTIVATED'))->where('idx',1)->execute();
						} else {
							$dbConnect->insert('member_table',array('idx'=>1,'domain'=>'*','type'=>'ADMINISTRATOR','email'=>$admin_email,'password'=>$password,'name'=>$admin_name,'nickname'=>$admin_nickname,'reg_date'=>time(),'status'=>'ACTIVATED'))->execute();
						}
						
						$IM->Module->updateSize('member');
					}
				}
				
				if ($results->success == true) {
					$installed = $IM->Module->install('admin');
					if ($installed !== true) {
						$results->success = false;
						$results->message = $installed;
						$results->target = 'admin';
					}
				}
			} else {
				$results->message = 'table';
			}
		} else {
			$results->message = 'file';
		}
	} else {
		$results->success = false;
		$results->errors = $errors;
	}
}

exit(json_encode($results));
?>