<?php
namespace Niysu\Services;

/// \todo Better implementation
class SessionService {
	public function __isset($var) {
		return isset($_SESSION[$var]);
	}
	
	public function __unset($var) {
		unset($_SESSION[$var]);
	}
	
	public function __get($var) {
		return $_SESSION[$var];
	}
	
	public function __set($var, $value) {
		$_SESSION[$var] = $value;
	}
	
	public function __construct() {
		session_start();
	}
};

?>