<?php
namespace Niysu\Services;

class HTTPBasicAuthService {
	public static function beforeMustBeAuthenticated($realm = 'private') {
		return function($httpBasicAuthService, $request, $response, &$callHandler) use ($realm) {
			$statusOnFail = $request->getHeader('X-StatusOnLoginFail');
			if (!$statusOnFail || $statusOnFail < 400 || $statusOnFail >= 500)
				$statusOnFail = 401;

			if ($httpBasicAuthService->login())
				return;
			$response->setStatusCode($statusOnFail);
			$response->setHeader('WWW-Authenticate', 'Basic realm="'.$realm.'"');
			$callHandler = false;
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
		// checking
		if (!$this->authFunction)
			throw new \LogicException('Auth function has not been set');

		//
		if (!$this->request->isHTTPS())
			$this->logService->warn('HTTP basic authentication is discouraged if you don\'t use HTTPS');
		if (!$this->request->getHeader('Authorization'))
			return false;

		// getting login/password from headers
		$authHeader = $this->request->getHeader('Authorization');
	    if (preg_match('/\s*Basic\s+(.*)$/i', $authHeader, $matches))
            list($login, $password) = explode(':', base64_decode($matches[1]));        	
        else
			return false;

		// calling auth function
		$localScope = clone $this->scope;
		$localScope->login = $login;
		$localScope->password = $password;
		$retValue = $localScope->callFunction($this->authFunction);
		return $retValue;
	}

	private $authFunction;				// callable function
	private $request;
	private $logService;
	private $scope;
};

?>