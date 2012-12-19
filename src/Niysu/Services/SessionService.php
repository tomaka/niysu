<?php
namespace Niysu\Services;

class SessionService {
	public static function beforeSetToCacheFilesStorage() {
		return function($sessionService) {
			$sessionService->setToCacheFilesStorage();
		};
	}

	public function setToCacheFilesStorage() {
		$this->writeFunction = function($sessionID, $sessionData) {
			$this->scope->cacheService->store('sessions/'.$sessionID, $sessionData);
		};

		$this->readFunction = function($sessionID) {
			$service = $this->scope->cacheService;
			if (!$service->exists('sessions/'.$sessionID))
				return null;
			return $service->load('sessions/'.$sessionID);
		};

		$this->storeSession();
		$this->currentSessionID = null;
	}

	/*public function setToDatabaseStorage() {

	}*/

	public function __isset($var) {
		$this->loadSession();
		return isset($this->currentSessionData[$var]);
	}

	public function __unset($var) {
		$this->loadSession();
		unset($this->currentSessionData[$var]);
		$this->flushRequired = true;
		$this->scope->cookiesService->add('sessionID', $this->currentSessionID, $this->sessionDuration);
	}

	public function __get($var) {
		$this->loadSession();
		return $this->currentSessionData[$var];
	}

	public function __set($var, $value) {
		$this->loadSession();
		$this->currentSessionData[$var] = $value;
		$this->flushRequired = true;
		$this->scope->cookiesService->add('sessionID', $this->currentSessionID, $this->sessionDuration);
	}

	public function __construct($scope) {
		$this->scope = $scope;
	}

	public function __destruct() {
		$this->storeSession();
	}

	public function getID() {
		$this->loadSession();
		return $this->currentSessionID;
	}

	public function getVariables() {
		$this->loadSession();
		return $this->currentSessionData;
	}



	private function loadSession() {
		if ($this->currentSessionID)
			return;

		if (!$this->readFunction)
			throw new \LogicException('Session storage mode not defined');

		try {
			$this->currentSessionID = $this->scope->cookiesService->sessionID;
		} catch(\Exception $e) {
			$this->currentSessionID = null;
		}

		$this->currentSessionData = [];

		if ($this->currentSessionID) {
			if ($val = call_user_func($this->readFunction, $this->currentSessionID))
				$this->currentSessionData = unserialize($val);

		} else {
			if (!$this->currentSessionID)
				$this->currentSessionID = self::generateSessionID();
			// TODO: check collision with existing session
		}
	}

	private function storeSession() {
		if (!$this->currentSessionID)
			return;
		if (!$this->flushRequired)
			return;
		$this->flushRequired = false;

		call_user_func($this->writeFunction, $this->currentSessionID, serialize($this->currentSessionData));
	}

	static private function generateSessionID() {
		if (function_exists('openssl_random_pseudo_bytes'))
			return bin2hex(openssl_random_pseudo_bytes(32, true));
		return sha1(mt_rand());
	}

	private $scope;
	private $currentSessionID = null;
	private $currentSessionData = [];
	private $readFunction = null;
	private $writeFunction = null;
	private $flushRequired = false;
	private $sessionDuration = '2 hours';
};

?>