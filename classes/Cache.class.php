<?php
class Cache {
	private $IM;
	private $cachePath;
	private $enabled;
	
	function __construct($IM) {
		global $_CONFIGS;
		
		$this->IM = $IM;
		$this->cachePath = $IM->getAttachmentPath().'/cache';
		$this->enabled = isset($_CONFIGS->enableCache) == false || $_CONFIGS->enableCache === true;
		
		if (is_dir($this->cachePath) == false) {
			mkdir($this->cachePath);
			chmod($this->cachePath,0707);
		}
	}
	
	function check($controller,$component,$code) {
		if ($this->enabled === false || !$this->IM->domain || !$this->IM->language) return 0;
		
		if (file_exists($this->cachePath.'/'.$controller.'.'.$component.'.'.$code.'.'.$this->IM->domain.'.'.$this->IM->language.'.cache') == true) {
			return filemtime($this->cachePath.'/'.$controller.'.'.$component.'.'.$code.'.'.$this->IM->domain.'.'.$this->IM->language.'.cache');
		} else {
			return 0;
		}
	}
	
	function get($controller,$component,$code) {
		if ($this->enabled === false || !$this->IM->domain || !$this->IM->language) return null;
		
		if (file_exists($this->cachePath.'/'.$controller.'.'.$component.'.'.$code.'.'.$this->IM->domain.'.'.$this->IM->language.'.cache') == true) {
			return file_get_contents($this->cachePath.'/'.$controller.'.'.$component.'.'.$code.'.'.$this->IM->domain.'.'.$this->IM->language.'.cache');
		} else {
			return null;
		}
	}
	
	function store($controller,$component,$code,$data) {
		if ($this->enabled === false || !$this->IM->domain || !$this->IM->language) return false;
		
		return file_put_contents($this->cachePath.'/'.$controller.'.'.$component.'.'.$code.'.'.$this->IM->domain.'.'.$this->IM->language.'.cache',$data);
	}
}
?>