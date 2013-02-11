<?php
namespace Niysu\Contexts;

/**
 * Allows authentication with sessions.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class SessionAuthContext {
	public function __construct(\Niysu\Contexts\SessionContext $sessionContext, \Niysu\Services\AuthService $authService, \Monolog\Logger $log = null) {
		$this->authService = $authService;
		$this->sessionContext = $sessionContext;
		$this->log = $log;
	}

	/**
	 * @return boolean False if the client is not currently logged in, otherwise returns the user's ID.
	 */
	public function login() {
		if (!$this->sessionContext->hasSessionLoaded())
			return false;
		if (!isset($this->sessionContext->userID))
			return false;
		return $this->sessionContext->userID;
	}

	/**
	 * Returns whether the current user has the given access.
	 * @return boolean
	 */
	public function hasAccess($access) {
		return $this->authService->hasAccess($this->login(), $access);
	}

	/**
	 * Sets the login and password when the client sends them.
	 * @return boolean The UserID (returned by AuthService's login function) or false.
	 */
	public function setLogin($login, $password) {
		if (!isset($login) && !isset($password)) {
			unset($this->sessionContext->userID);
			return false;
		}

		if ($id = $this->authService->login([ 'login' => $login, 'password' => $password ]))
			$this->sessionContext->userID = $id;
		return $id;
	}

	/**
	 * 
	 */
	public function clearLogin() {
		$this->setLogin(null, null);
	}



	private $authService;
	private $sessionContext;
	private $log;
};
