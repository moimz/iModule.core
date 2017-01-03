<?php
/**
 * This file is part of Moimz Tools - https://www.moimz.com
 *
 * @file json.class.php
 * @author Arzz
 * @version 1.1.7
 * @license MIT License
 */
class json {
	private $db;
	
	private $_function = null;
	private $_table = null;
	private $_columns = null;
	private $_prefix;
	private $_join = array();
	private $_where = array();
	private $_orderBy = array();
	private $_groupBy = array();
	private $_limit = null;
	public $count = 0;

	public function __construct($db=null) {
		$this->db = $db;
	}
	
	public function check($db) {
		return true;
	}
	
	function setPrefix($prefix) {
		$this->_prefix = $prefix;
		return $this;
	}
	
	function error($msg,$query='') {
		die($msg.'<br>'.$query);
	}
	
	private function reset() {
		$this->_function = null;
		$this->_table = null;
		$this->_columns = null;
		$this->_where = array();
		$this->_join = array();
		$this->_orderBy = array();
		$this->_groupBy = array(); 
		$this->_limit = null;
		$this->count = 0;
	}
	
	public function execute() {
		$ch = curl_init();
		
		$params = array(
			'key'=>$this->db->key,
			'schema'=>json_encode(array(
				'_function'=>$this->_function,
				'_table'=>$this->_table,
				'_columns'=>$this->_columns,
				'_where'=>$this->_where,
				'_join'=>$this->_join,
				'_orderBy'=>$this->_orderBy,
				'_groupBy'=>$this->_groupBy,
				'_limit'=>$this->_limit
			))
		);
		
		curl_setopt($ch,CURLOPT_URL,$this->db->host);
		curl_setopt($ch,CURLOPT_POST,true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		
		$result = curl_exec($ch);
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		$content_type = explode(';',curl_getinfo($ch,CURLINFO_CONTENT_TYPE));
		$content_type = array_shift($content_type);
		
		if ($http_code == 200) {
			$data = json_decode($result);
			if ($data->success == true) {
				$this->count = count($data->data);
				return $data->data;
			}
		}
		
		return null;
	}
	
	public function has() {
		return $this->count() > 0;
	}
	
	public function count() {
		$this->execute();
		return $this->count;
	}
	
	public function get() {
		return $this->execute();
	}
	
	public function getOne() {
		$res = $this->get();
		if (is_object($res) == true) return $res;
		if (isset($res[0]) == true) return $res[0];
		return null;
	}
	
	public function select($table,$columns='*') {
		$this->_function = 'select';
		$this->_table = $table;
		$this->_columns = $columns;
		return $this;
	}
	
	public function where($whereProp,$whereValue=null,$operator=null) {
		$this->_where[] = array('AND',$whereProp,$whereValue,$operator);
		return $this;
	}
	
	public function orWhere($whereProp,$whereValue=null,$operator=null) {
		$this->_where[] = array('OR',$whereProp,$whereValue,$operator);
		return $this;
	}
	
	public function join($joinTable,$joinCondition,$joinType='') {
		$this->_join[] = array($joinTable,$joinCondition,$joinType);
		return $this;
	}
	
	public function orderBy($orderByField,$orderbyDirection='DESC',$customFields=null) {
		$this->_orderBy[] = array($orderByField,$orderbyDirection,$customFields);
		return $this;
	}
	
	public function groupBy($groupByField) {
		$this->_groupBy[] = $groupByField;
		return $this;
	}
	
	public function limit($start,$limit=null) {
		if ($limit != null) {
			$this->_limit = array($start,$limit);
		} else {
			$this->_limit = array(0,$start);
		}
		return $this;
	}
	
	public function ping() {
		$this->_function = 'ping';
		return $this->execute();
	}
	
	public function copy() {
		$copy = unserialize(serialize($this));
		return $copy;
	}
}
?>