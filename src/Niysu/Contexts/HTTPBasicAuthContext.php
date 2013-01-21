<?php
namespace Niysu\Contexts;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPBasicAuthContext {
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\Services\AuthService $authService, \Monolog\Logger $log = null) {
		$this->request = $request;
		$this->authService = $authService;
		$this->log = $log;
	}

	/**
	 * @return False if the client didn't provide any username/password, or the result of calling the auth function
	 */
	public function login() {
		if (!$this->request->getHeader('Authorization'))
			return false;

		// getting login/password from headers
		$authHeader = $this->request->getHeader('Authorization');
	    if (preg_match('/\s*Basic\s+(.*)$/i', $authHeader, $matches))
            list($login, $password) = explode(':', base64_decode($matches[1]));        	
        else
			return false;

		if (!$login || !$password)
			return false;

		if ($this->log)
			$this->log->debug('Login attempt through basic HTTP authentication');

		// warning if not using HTTPS
		if (!$this->request->isHTTPS() && isset($this->log) && strtolower($this->request->getHeader('X-Forwarded-Proto')) != 'https')
			$this->log->notice('HTTP basic authentication is discouraged if you don\'t use HTTPS');

		// calling auth function
		return $this->authService->login([ 'login' => $login, 'password' => $password ]);
	}

	/**
	 * Returns whether the current user has the given access.
	 */
	public function hasAccess($access) {
		return $this->authService->hasAccess($this->login(), $access);
	}

	private $authService;
	private $request;
	private $log;
};

?>