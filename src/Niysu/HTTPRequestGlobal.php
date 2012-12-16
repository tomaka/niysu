<?php
namespace Niysu;

class HTTPRequestGlobal extends HTTPRequestInterface {
	public function getURL() {
		$uri = $_SERVER['REQUEST_URI'];
		$pos = strpos($uri, '?');
		if ($pos !== false)
			$uri = substr($uri, 0, $pos);
		return $uri;
	}

	public function getMethod() {
		return strtoupper($_SERVER['REQUEST_METHOD']);
	}

	public function getHeader($header) {
		foreach ($this->headersList as $key => $val) {
			if (strtolower($key) == strtolower($header))
				return $val;
		}
	}

	public function getHeadersList() {
		return $this->headersList;
	}

	public function getRawData() {
		return file_get_contents('php://input');
	}

	public function isHTTPS() {
		return $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off';
	}

	public function getCookiesList() {
		return $_COOKIE;
	}


	public function __construct() {
	    if (function_exists('apache_request_headers'))
	        $this->headersList = apache_request_headers();

	    else {
		    foreach($_SERVER as $key => $value) { 
	            if (substr($key, 0, 5) == 'HTTP_') {
	                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
	                $this->headersList[$key] = $value;
	            }
	        }
	    }
	}

	private $headersList = null;
}

?>