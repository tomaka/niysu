<?php
namespace Niysu\Services;

class CookiesService {
	public function __construct(&$request, &$response) {
		$this->request =& $request;
		$this->response =& $response;
	}

	public function setDefaultLifetime($ttl) {
		/*if (is_string($ttl))
			$ttl = new \DateInterval($ttl);
		if ($ttl instanceof \DateInterval)
			$ttl = */

		if (!is_numeric($ttl))
			throw new \LogicException('Wrong format for default cookies lifetime');

		$this->defaultLifetime = $ttl;
	}

	public function __get($varName) {
		return $this->get($varName);
	}

	public function __set($varName, $value) {
		return $this->add($varName, $value);
	}

	public function __isset($varName) {
		return $this->has($varName);
	}

	public function getCookiesList() {

	}

	public function has($cookieName) {
		return isset($requestCookies[$cookieName]);
	}

	public function get($cookieName) {
		if (!isset($requestCookies[$cookieName]))
			return null;
		return $requestCookies[$cookieName];
	}

	public function add($name, $value, $expires = null, $path = null, $domaine = null, $secure = false, $httponly = false) {

	}


	private $request;
	private $response;
	private $requestCookies = [];		// cookies read from the request ; array of format name => value
	private $cookies = [];				// each element of the array
	private $defaultLifetime = 0;		// default lifetime for cookies when expires is null
};

?>