<?php
namespace Niysu\Filters;

/**
 * Allows authentication with sessions.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class SessionAuthFilter extends SessionFilter {
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\SessionFilter $sessionFilter, \Niysu\Services\AuthService $authService, \Monolog\Logger $log = null) {
		parent::__construct($request);
		$this->authService = $authService;
		$this->sessionFilter = $sessionFilter;
		$this->log = $log;
	}

	/**
	 * @return False if the client didn't provide any username/password, or the result of calling the auth function
	 */
	public function login() {
		if (!$this->sessionFilter->hasSessionLoaded())
			return false;
		if (!isset($this->sessionFilter->userID))
			return false;
		return $this->sessionFilter->userID;
	}

	/**
	 * Returns whether the current user has the given access.
	 */
	public function hasAccess($access) {
		return $this->authService->hasAccess($this->login(), $access);
	}

	private $authService;
	private $sessionFilter;
	private $log;
};

?>