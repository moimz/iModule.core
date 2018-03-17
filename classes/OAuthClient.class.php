<?php
/**
 * 이 파일은 MoimzTools 의 일부입니다. (https://www.moimz.com)
 *
 * OAuth 프로토콜 처리를 위한 클래스를 정의한다.
 *
 * @file /classes/OAuthClient.class.php
 * @author Arzz
 * @license MIT License
 * @version 1.0.0
 * @modified 2018. 3. 18.
 */
class OAuthClient {
	private $_clientId = null;
	private $_clientSecret = null;
	private $_method = 'post';
	private $_scope = '';
	private $_redirectUrl;
	private $_userAgent = null;
	
	private $_authUrl;
	private $_tokenUrl;
	
	private $_isMakeAccessHeader = false;
	protected $_headers = array();
	protected $_params = array();
	
	private $_accessToken = null;
	private $_accessType = 'online';
	private $_approval_prompt = 'auto';
	private $_grantType = 'authorization_code';
	private $_refreshToken = null;
	private $_error = '';
	
	function __construct() {
		$temp = explode('?',$_SERVER['REQUEST_URI']);
		if (empty($_SERVER['HTTPS']) == true) $this->_redirectUrl = 'http://'.$_SERVER['HTTP_HOST'].array_shift($temp);
		else $this->_redirectUrl = 'https://'.$_SERVER['HTTP_HOST'].array_shift($temp);
		
		if ($this->getSession('OAUTH_ACCESS_TOKEN') == null) {
			$_SESSION['OAUTH_ACCESS_TOKEN'] = array();
		}
	}
	
	function getSession($name) {
		return isset($_SESSION[$name]) == true ? $_SESSION[$name] : null;
	}
	
	function setClientId($clientId) {
		if (!empty($_SESSION['OAUTH_ACCESS_TOKEN'][$clientId])) {
			$this->_accessToken = $_SESSION['OAUTH_ACCESS_TOKEN'][$clientId];
		} else {
			$_SESSION['OAUTH_ACCESS_TOKEN'][$clientId] = null;
		}
		
		if (!empty($_SESSION['OAUTH_REFRESH_TOKEN'][$clientId])) {
			$this->_refreshToken = $_SESSION['OAUTH_REFRESH_TOKEN'][$clientId];
		} else {
			$_SESSION['OAUTH_REFRESH_TOKEN'][$clientId] = null;
		}
		
		$this->_clientId = $clientId;
		return $this;
	}
	
	function setClientSecret($clientSecret) {
		$this->_clientSecret = $clientSecret;
		return $this;
	}
	
	function setMethod($method) {
		$this->_method = $method;
		return $this;
	}
	
	function getScope($scope) {
		return $this->_scope;
	}
	
	function setScope($scope) {
		$this->_scope = $scope;
		return $this;
	}
	
	function setUserAgent($userAgent) {
		$this->_userAgent = $userAgent;
		return $this;
	}
	
	function setAuthUrl($authUrl) {
		$this->_authUrl = $authUrl;
		return $this;
	}
	
	function setTokenUrl($tokenUrl) {
		$this->_tokenUrl = $tokenUrl;
		return $this;
	}
	
	function getRedirectUrl() {
		return $this->_redirectUrl;
	}
	
	function setRedirectUrl($redirectUrl) {
		$this->_redirectUrl = $redirectUrl;
		return $this;
	}
	
	function getAuthenticationUrl() {
		$params = array(
			'response_type'=>'code',
			'client_id' =>$this->_clientId,
			'redirect_uri'=>$this->_redirectUrl,
			'scope'=>$this->_scope,
			'access_type'=>$this->_accessType,
			'approval_prompt'=>$this->_approval_prompt
		);

		return $this->_authUrl.'?'.http_build_query($params,null,'&');
	}
	
	function getAccessToken() {
		if ($this->_accessToken != null && ($this->_accessToken->expires_in == 0 || $this->_accessToken->expires_in > time())) {
			return $this->_accessToken->access_token;
		} elseif ($this->_refreshToken != null) {
			$this->setGrantType('refresh_token');
			$this->authenticate();
			
			return $this->getAccessToken();
		}
		
		return null;
	}
	
	function setAccessType($type) {
		$this->_accessType = $type;
		return $this;
	}
	
	function setApprovalPrompt($approval_prompt) {
		$this->_approval_prompt = $approval_prompt;
		return $this;
	}
	
	function setAccessToken($token,$type='Url',$expires_in=0) {
		$this->_accessToken = new stdClass();
		$this->_accessToken->access_token = $token;
		$this->_accessToken->token_type = $type;
		$this->_accessToken->expires_in = $expires_in;
		
		$_SESSION['OAUTH_ACCESS_TOKEN'][$this->_clientId] = $this->_accessToken;
		
		return $this;
	}
	
	function setRefreshToken($token) {
		$this->_refreshToken = $token;
		
		$_SESSION['OAUTH_REFRESH_TOKEN'][$this->_clientId] = $this->_refreshToken;
		
		// Store Refresh Token Here
		
		return $this;
	}
	
	function setGrantType($type) {
		$this->_grantType = $type;
		
		return $this;
	}
	
	function getRefreshToken() {
		return $this->_refreshToken;
	}
	
	function getError() {
		return $this->_error;
	}
	
	function authenticate($code='') {
		if ($this->_grantType == 'authorization_code' && strlen($code) == 0) die('Error');
		
		$params = array();
		$params['grant_type'] = $this->_grantType;
		$params['client_id'] = $this->_clientId;
		$params['client_secret'] = $this->_clientSecret;
		$params['redirect_uri'] = $this->_redirectUrl;
		
		if ($this->_grantType == 'authorization_code') {
			$params['code'] = $code;
		} elseif ($this->_grantType == 'refresh_token') {
			if (empty($this->_refreshToken)) die('Error');
			$params['refresh_token'] = $this->_refreshToken;
		}
		
		$token = $this->executeRequest($this->_tokenUrl,$params,$this->_method);
		
		if ($token !== false && !empty($token->access_token)) {
			$this->setAccessToken($token->access_token,isset($token->token_type) == true ? strtoupper($token->token_type) : 'URL',isset($token->expires_in) == true ? time() + $token->expires_in : 0);
			if (!empty($token->refresh_token)) $this->setRefreshToken($token->refresh_token);
			$this->setAccessType('online');
			$this->setGrantType('authorization_code');
			
			return true;
		} else {
			if ($this->_grantType == 'refresh_token') $this->setRefreshToken(null);
			return false;
		}
	}
	
	function refreshToken() {
		if ($this->_refreshToken !== null) {
			$this->setGrantType('refresh_token');
			$this->authenticate();
		} else {
			$location = $this->getAuthenticationUrl();
			echo '<script>location.href="'.$location.'";</script>';
			exit;
		}
	}
	
	function makeAccessHeader() {
		if ($this->_isMakeAccessHeader === true) return;
		
		switch ($this->_accessToken->token_type) {
			case 'URL' :
				$this->_params['access_token'] = $this->_accessToken->access_token;
			case 'BEARER' :
				$this->_headers['Authorization'] = 'Bearer '.$this->_accessToken->access_token;
				break;
		}
		
		$this->_isMakeAccessHeader = true;
	}
	
	function get($url,$params=array(),$headers=array()) {
		if ($this->_accessToken == null) $this->refreshToken();
		
		$this->makeAccessHeader();
		$headers = array_merge($this->_headers,$headers);
		$params = array_merge($this->_params,$params);
		
		return $this->executeRequest($url,$params,'get',$headers);
	}
	
	function post($url,$params=array(),$headers=array()) {
		if ($this->_accessToken == null) $this->refreshToken();
		
		$this->makeAccessHeader();
		$headers = array_merge($this->_headers,$headers);
		$params = array_merge($this->_params,$params);
		
		return $this->executeRequest($url,$params,'post',$headers);
	}
	
	function executeRequest($url,$params=array(),$method='post',$headers=array(),$isRefresh=false) {
		$ch = curl_init();
		
		if (empty($headers) == false) {
			$httpHeaders = array();
			foreach($headers as $key=>$value) {
				$httpHeaders[] = $key.': '.$value;
			}
			
			curl_setopt($ch,CURLOPT_HTTPHEADER,$httpHeaders);
		}
		
		if ($this->_userAgent != null) {
			curl_setopt($ch,CURLOPT_USERAGENT,$this->_userAgent);
		}
		
		if ($method == 'post') {
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_POST,true);
			curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
		} else {
			if (empty($params) == true) {
				curl_setopt($ch,CURLOPT_URL,$url);
			} else {
				$url.= '?'.http_build_query($params,null,'&');
				curl_setopt($ch,CURLOPT_URL,$url);
			}
		}
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		
		$result = curl_exec($ch);
		$http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
		$content_type = explode(';',curl_getinfo($ch,CURLINFO_CONTENT_TYPE));
		$content_type = array_shift($content_type);
		
		if ($http_code == 401 && $isRefresh == false) {
			$this->refreshToken();
			return $this->executeRequest($url,$params,$method,$headers,true);
		}
		
		if ($http_code == 200) {
			curl_close($ch);
			
			$this->_error = '';
			if ($content_type == 'application/json') {
				return json_decode($result);
			} else {
				parse_str($result,$result);
				return (object)$result;
			}
		} else {
			$this->_error = '['.$http_code.'] '.$result;
			curl_close($ch);
			return false;
		}
	}
}
?>