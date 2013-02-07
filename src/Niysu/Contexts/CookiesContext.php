<?php
namespace Niysu\Contexts;

/**
 * Reads and/or writes cookies from the request or to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class CookiesContext {
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\HTTPResponseInterface $response, \Monolog\Logger $log = null) {
		$this->inputRequest = $request;
		$this->outputResponse = $response;
		$this->log = $log;
		$this->refreshRequestCookies();
	}

	/**
	 * Sets the default lifetime of a cookie if not set.
	 *
	 * If you call add() without precising any expiration, then the value set using this function will be used instead.
	 *
	 * @param mixed 	$ttl 		See add() for the format
	 */
	public function setDefaultLifetime($ttl) {
		$this->defaultLifetime = $ttl;
	}

	/**
	 * Alias of get()
	 * @param string 	$varName 	Name of the cookie to read
	 */
	public function __get($varName) {
		return $this->get($varName);
	}

	/**
	 * Alias of add()
	 *
	 * This function is an alias of add($varName, $value).
	 * Therefore, the default lifetime will be used.
	 *
	 * @param string 	$varName 	Name of the cookie to set
	 * @param string 	$value 		Value to set
	 * @see setDefaultLifetime
	 */
	public function __set($varName, $value) {
		$this->add($varName, $value);
	}

	/**
	 * Alias of destroy()
	 * @param string 	$varName 	Name of the cookie to destroy
	 */
	public function __unset($varName) {
		$this->destroy($varName);
	}

	/**
	 * Returns true if this cookie exists in the request or has previously been set.
	 *
	 * @param string 	$varName 	Name of the cookie to check
	 * @return boolean
	 */
	public function __isset($varName) {
		return $this->get($varName) !== null;
	}

	/**
	 * Returns the list of cookies.
	 *
	 * Returns an associative array of cookieName => value.
	 *
	 * @return array
	 */
	public function getCookiesList() {
		$val = array_merge($this->requestCookies, $this->updatedCookies);
		while ($pos = array_search(null, $val))
			unset($val[$pos]);
		return $val;
	}

	/**
	 * Reads a cookie.
	 *
	 * This function reads a cookie from the request.
	 * Returns null if the cookie doesn't exist.
	 *
	 * If a cookie of this name has been set using add(), then its value is returned instead.
	 * If a cookie of this name has been destroyed using destroy(), then null is returned instead.
	 *
	 * @param string 	$varName 	The name of the cookie to read
	 * @return string
	 */
	public function get($cookieName) {
		if (isset($this->updatedCookies[$cookieName]))
			return $this->updatedCookies[$cookieName];

		if (!isset($this->requestCookies[$cookieName]))
			return null;

		return $this->requestCookies[$cookieName];
	}

	/**
	 * Destroys a cookie.
	 *
	 * This function will destroy a cookie by sending to the response a expiration date in the past.
	 * Further attempts to get this cookie using get() will return null.
	 *
	 * @param string 	$cookieName 	Name of the cookie to destroy
	 */
	public function destroy($cookieName) {
		$this->add($cookieName, null);
	}

	/**
	 * Sets the value of a cookie.
	 *
	 * This function will send a Set-Cookie header to the response containing the informations about this cookie.
	 * It will also add the cookie to an internal array. Any attempt to get the value of the cookie will return the value set here, even if there is already a cookie in the getInput().
	 *
	 * The $expires parameter can be:
	 *  - a string passable to DateInterval::createFromDateString ; example: '1 day', '2 hours', etc.
	 *  - a string passable to DateInterval::__construct ; example 'P1D', 'P2M', etc.
	 *  - a number of seconds
	 *  - an instance of \DateInterval
	 *  - a string representing the date where to delete the cookie
	 *
	 * @param string 	$name 		Name of the cookie to set
	 * @param string 	$value 		Value of the cookie
	 * @param mixed 	$expires 	See description
	 * @param string 	$path 		The path where this cookie is valid
	 * @param string 	$domain 	The domain where this cookie is valid
	 * @param boolean 	$secure 	True if the cookie should only be sent through HTTPS
	 * @param boolean 	$httponly 	True if the cookie is accessible through Javascript
	 */
	public function add($name, $value, $expires = null, $path = null, $domain = null, $secure = false, $httponly = false) {
		if ($expires === null)
			$expires = $this->defaultLifetime;

		$this->updatedCookies[$name] = $value;

		if (is_string($expires) && substr($expires, 0, 1) == 'P')
			$expires = new \DateInterval($expires);
		else if (is_string($expires))
			$expires = \DateInterval::createFromDateString($expires);
		if ($expires instanceof \DateInterval)
			$expires = ((($expires->y * 365.25 + $expires->m * 30.4 + $expires->d) * 24 + $expires->h) * 60 + $expires->i) * 60 + $expires->s;
		if (is_numeric($expires))
			$expires = date('r', time() + intval($expires));

		// setting date in the past if we want to clear the cookie
		if ($value === null)
			$expires = date('r', time() - 24 * 3600);

		$header = $name.'='.$value;
		if ($expires)	$header .= '; Expires='.$expires;
		if ($path)		$header .= '; Path='.$path;
		if ($domain)	$header .= '; Domain='.$domain;
		if ($secure)	$header .= '; Secure';
		if ($httponly)	$header .= '; HttpOnly';

		if ($this->outputResponse) {
			$this->log->debug('Sending cookie '.$name.' set to value: '.$value);
			if ($this->inputRequest && !$this->inputRequest->isHTTPS() && $secure)
				$this->log->notice('Setting a secure cookie not through HTTPS is pointless');

			$this->outputResponse->addHeader('Set-Cookie', $header);
		}
	}



	private function refreshRequestCookies() {
		$this->requestCookies = [];

		if (!$this->inputRequest) return;
		$header = $this->inputRequest->getHeader('Cookie');
		if (!$header) return;

		foreach (explode(';', $header) as $cookie) {
			list($name, $value) = explode('=', $cookie, 2);
			$this->requestCookies[trim($name)] = trim($value);
		}
	}


	private $inputRequest;
	private $outputResponse;
	private $log;
	private $requestCookies = [];			// cookies read from the request ; array of format name => value
	private $updatedCookies = [];			// same format as $requestCookies but for cookies that have been set by this function
	private $defaultLifetime = null;		// default lifetime for cookies when expires is null
};
