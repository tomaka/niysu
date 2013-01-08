<?php
namespace Niysu\Filters;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPBasicAuthFilter extends \Niysu\HTTPRequestFilterInterface {
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\Services\AuthService $authService, \Monolog\Logger $log = null) {
		parent::__construct($request);
		$this->authService = $authService;
		$this->log = $log;
	}

	/**
	 * @return False if the client didn't provide any username/password, or the result of calling the auth function
	 */
	public function login() {
		if (!$this->getInput()->getHeader('Authorization'))
			return false;

		// getting login/password from headers
		$authHeader = $this->getInput()->getHeader('Authorization');
	    if (preg_match('/\s*Basic\s+(.*)$/i', $authHeader, $matches))
            list($login, $password) = explode(':', base64_decode($matches[1]));        	
        else
			return false;

		if (!$login || !$password)
			return false;

		if ($this->log)
			$this->log->debug('Login attempt through basic HTTP authentication');

		// warning if not using HTTPS
		if (!$this->getInput()->isHTTPS() && isset($this->log) && strtolower($this->getInput()->getHeader('X-Forwarded-Proto')) != 'https')
			$this->log->notice('HTTP basic authentication is discouraged if you don\'t use HTTPS');

		// calling auth function
		return $this->authService->login([ 'login' => $login, 'password' => $password ]);
	}

	private $authService;
	private $log;
};

?>