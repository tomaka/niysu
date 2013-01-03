<?php
namespace Niysu;
require_once __DIR__.'/HTTPRequestInterface.php';

/**
 * Implementation of HTTPRequestInterface which reads all informations from the environment.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
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

	public function getHeader($header, $index = 0) {
		foreach ($this->headersList as $key => $val) {
			if (strtolower($key) == strtolower($header)) {
				if ($index-- == 0)
					return $val;
			}
		}

		return null;
	}

	public function getHeadersList() {
		return $this->headersList;
	}

	public function getRawData() {
		return file_get_contents('php://input');
	}

	public function isHTTPS() {
		return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] && $_SERVER['HTTPS'] != 'off';
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