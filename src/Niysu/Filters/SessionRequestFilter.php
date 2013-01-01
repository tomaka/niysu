<?php
namespace Niysu\Filters;

/**
 * Filter that loads and stores sessions.
 * 
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class SessionRequestFilter extends \Niysu\HTTPRequestFilterInterface {
	/**
	 * Constructor.
	 */
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\Services\SessionService $sessionService, \Niysu\Services\CookiesService $cookiesService, \Monolog\Logger $log = null) {
		parent::__construct($request);

		$this->sessionService = $sessionService;
		$this->cookiesService = $cookiesService;
		$this->log = $log;
	}

	public function __isset($varName) {
		return $this->__get($varName) !== null;
	}

	public function __get($varName) {
		if (!$this->getSessionID())
			return null;
		return $this->sessionService[$this->getSessionID()][$varName];
	}

	public function __set($varName, $value) {
		if (!$this->getSessionID())
			$this->cookiesService->{$this->cookieName} = $this->sessionService->generateSessionID();

		$v = $this->sessionService[$this->getSessionID()];
		if (!$v) $v = [];
		if ($value === null)	unset($v[$varName]);
		else 					$v[$varName] = $value;
		$this->sessionService[$this->getSessionID()] = $v;
	}

	public function __unset($varName) {
		$this->__set($varName, null);
	}

	public function hasSessionLoaded() {
		return $this->getSessionID() !== null;
	}

	public function getSessionID() {
		return $this->cookiesService->{$this->cookieName};
	}


	private $cookieName = 'session';
	private $sessionService;
	private $cookiesService;
	private $log;
};

?>