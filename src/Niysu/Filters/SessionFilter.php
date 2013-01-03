<?php
namespace Niysu\Filters;

/**
 * Filter that loads and stores sessions.
 * 
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class SessionFilter extends \Niysu\HTTPRequestFilterInterface implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;

	/**
	 * Constructor.
	 */
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\HTTPResponseInterface $response, \Niysu\Services\SessionService $sessionService, \Niysu\Filters\CookiesFilter $cookiesFilter, \Monolog\Logger $log = null) {
		parent::__construct($request);
		$this->outputResponse = $response;

		$this->sessionService = $sessionService;
		$this->cookiesFilter = $cookiesFilter;
		$this->log = $log;
	}

	/**
	 * Returns true if the current session has a variable.
	 * @param string 	$varName 	Name of the variable to check
	 * @return boolean
	 */
	public function __isset($varName) {
		return $this->__get($varName) !== null;
	}

	/**
	 * Returns the value of a variable in the current session.
	 * @param string 	$varName 	Name of the variable to retreive
	 * @return mixed
	 * @throws RuntimeException If the variable doesn't exist or if no session is started
	 */
	public function __get($varName) {
		if (!$this->hasSessionLoaded())
			throw new \RuntimeException('No session is currently started');
		$val = $this->sessionService[$this->getSessionID()];
		if (!isset($val[$varName]))
			throw new \RuntimeException('Variable doesn\'t exist in the current session');
		return $val[$varName];
	}

	/**
	 * Sets the value of a variable in the current session.
	 * This also starts a session if no session currently exists.
	 * @param string 	$varName 	Name of the variable to set
	 * @param mixed 	$value 		Value
	 */
	public function __set($varName, $value) {
		if (!$this->getSessionID())
			$this->cookiesFilter->{$this->cookieName} = $this->sessionService->generateSessionID();

		$v = $this->sessionService[$this->getSessionID()];
		if (!$v) $v = [];
		if ($value === null)	unset($v[$varName]);
		else 					$v[$varName] = $value;
		$this->sessionService[$this->getSessionID()] = $v;
	}

	/**
	 * Destroys a variable in the current session.
	 * @param string 	$varName 	Name of the variable to destroy
	 */
	public function __unset($varName) {
		$this->__set($varName, null);
	}

	/**
	 * Returns true if a session is currently started.
	 * @return boolean
	 */
	public function hasSessionLoaded() {
		if (!isset($this->cookiesFilter->{$this->cookieName}))
			return false;
		$sessionID = $this->cookiesFilter->{$this->cookieName};
		return isset($this->sessionService[$sessionID]);
	}

	/**
	 * Returns the ID of the current session.
	 * @return string
	 * @throws RuntimeException If no session is currently in progress
	 */
	public function getSessionID() {
		if (!$this->hasSessionLoaded())
			throw new \RuntimeException('No session loaded');
		return $this->cookiesFilter->{$this->cookieName};
	}

	/**
	 * Changes the name of the cookie to look for.
	 * @param string 	$name 		Name of the cookie
	 */
	public function setCookieName($name) {
		$this->cookieName = $name;
	}


	private $cookieName = 'session';
	private $sessionService;
	private $cookiesFilter;
	private $log;
};

?>