<?php
namespace Niysu\Filters;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPBasicAuthFilter {
	public static function beforeMustBeAuthenticated($realm = 'private') {
		return function($httpBasicAuthService, $request, $response, &$stopRoute) use ($realm) {
			$statusOnFail = $request->getHeader('X-StatusOnLoginFail');
			if (!$statusOnFail || $statusOnFail < 400 || $statusOnFail >= 500)
				$statusOnFail = 401;

			if ($httpBasicAuthService->login())
				return;
			$response->setStatusCode($statusOnFail);
			$response->setHeader('WWW-Authenticate', 'Basic realm="'.$realm.'"');
			$stopRoute = true;
		};
	}

	public function __construct(&$request, $logService, $scope) {
		$this->request = &$request;
		$this->logService = $logService;
		$this->scope = $scope;
	}

	public function setAuthFunction($callable) {
		if (!is_callable($callable))
			throw new \LogicException('Auth function must be callable');
		$this->authFunction = $callable;
	}

	/// \ret False if the client didn't provide any username/password, or the result of calling the auth function
	/// \todo Give access to parent scope (eg. merge function in Scope or something)
	public function login() {
		//
		if (!$this->request->isHTTPS() && $this->logService && strtolower($this->request->getHeader('X-Forwarded-Proto')) != 'https')
			$this->logService->warn('HTTP basic authentication is discouraged if you don\'t use HTTPS');
		if (!$this->request->getHeader('Authorization'))
			return false;

		// getting login/password from headers
		$authHeader = $this->request->getHeader('Authorization');
	    if (preg_match('/\s*Basic\s+(.*)$/i', $authHeader, $matches))
            list($login, $password) = explode(':', base64_decode($matches[1]));        	
        else
			return false;

		// checking
		if (!$this->authFunction)
			throw new \LogicException('Auth function has not been set');

		// calling auth function
		$retValue = $this->scope->newChild(['login' => $login, 'password' => $password], true, false, false)->call($this->authFunction);

		if ($this->logService)
			$this->logService->info('Successful user login by basic HTTP authentication');

		return $retValue;
	}

	private $authFunction;				// callable function
	private $request;
	private $logService;
	private $scope;
};

?>