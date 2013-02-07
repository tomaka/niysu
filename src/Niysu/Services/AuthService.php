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
	 *
	 * @param Scope 	$scope 		The scope that will be accessible to callbacks
	 * @param Logger 	$log 		Logging object
	 */
	public function __construct(\Niysu\Scope $scope = null, \Monolog\Logger $log = null) {
		$this->scope = $scope;
		if (!$scope)
			$this->scope = new \Niysu\Scope();

		$this->log = $log;
	}

	/**
	 * Sets the callback to use when a login attempt is detected.
	 * 
	 * This callback will have access to the scope passed to the constructor.
	 * It will also have access to the variables passed to the login() function, ie. usually "login" and "password".
	 *
	 * The callback should return an ID for the client, or something non-true in case of login failure.
	 *
	 * @param callable 		$callback 		Callback function
	 */
	public function setLoginCallback($callback) {
		$this->loginCallback = $callback;
	}

	/**
	 * Sets the callback to use when an access request is made.
	 * 
	 * This callback will have access to the scope passed to the constructor.
	 * It will also have access to the variables "userID" and "access", which are the parameters to test for.
	 * If "userID" is false, this means the guest user.
	 * 
	 * The callback should return true if the user has the specified access, or false if it has not.
	 *
	 * @param callable 		$callback 		Callback function
	 */
	public function setAccessTestCallback($callback) {
		$this->accessCallback = $callback;
	}

	/**
	 * Login attempt of a client.
	 *
	 * Calls the login callback and returns what the login callback returns.
	 * A non-true value (loose comparaison) means that the login failed.
	 *
	 * The parameter must be an associative array. The keys are not specified, but "login" and "password" are recommended if available.
	 * The content of this array will be passed to the login function.
	 * 
	 * @param array 	$data 		See description
	 * @return mixed
	 */
	public function login($data) {
		return $this->scope->newChild($data, true, false, true)->call($this->loginCallback);
	}

	/**
	 * Tests whether a user has a specific access.
	 * 
	 * This function calls the callback previously set with setAccessTestCallback.
	 * 
	 * The userID is a value that the login callback returned, or false for the guest user.
	 * The access can be any string. It is recommended to use URI-like access, eg. "read:/users/1".
	 * 
	 * @param mixed 	$userID 	Identifier of the user
	 * @param string 	$access 	The access to test
	 * @return boolean
	 */
	public function hasAccess($userID, $access) {
		return $this->scope->newChild([ 'userID' => $userID, 'access' => $access ], true, false, true)->call($this->accessCallback);
	}


	private $scope;
	private $loginCallback;
	private $accessCallback;
	private $log;
};
