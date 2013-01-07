<?php
namespace Niysu\Services;

/**
 * Class which allows authentication.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class AuthService {
	/**
	 * Constructs the AuthService.
	 */
	public function __construct(\Monolog\Logger $log = null) {
		$this->log = $log;
	}

	public function setLoginCallback($callback) {

	}

	public function setAccessTestCallback($callback) {

	}

	/**
	 * 
	 */
	public function login() {
		
	}

	/**
	 * Tests whether a user has a specific access.
	 * This function calls the callback previously set with setAccessTestCallback.
	 * @param string 	$userID 	Identifier of the user
	 * @return boolean
	 */
	public function hasAccess($userID, $access) {

	}


	private $log = null;
};

?>