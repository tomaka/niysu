<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class SessionService implements \ArrayAccess {
	/**
	 * Sets the category that will be passed to the CacheService.
	 * By default, this is "sessions"
	 */
	public function setCategory($category) {
		$this->category = $category;
	}

	public function setSessionsDuration($duration) {
		$this->ttl = $duration;
	}


	public function offsetExists($id) {
		return null !== $this->cacheService->load($id, $this->category);
	}

	public function offsetGet($id) {
		$data = $this->cacheService->load($id, $this->category);
		return (object)unserialize($data);
	}

	public function offsetSet($id, $value) {
		if (!is_array($value))
			throw new \LogicException('Value must be an array');
		if (!$id)
			$id = self::generateSessionID();
		$this->cacheService->store($id, serialize($value), $this->ttl, $this->category);
	}

	public function offsetUnset($id) {
		$this->cacheService->clear($id, $this->category);
	}

	public function __construct(CacheService $cacheService) {
		$this->cacheService = $cacheService;
	}

	/**
	 * Generates a new session ID.
	 * This function does nothing except build an ID.
	 * @return string
	 */
	static public function generateSessionID() {
		if (function_exists('openssl_random_pseudo_bytes'))
			return bin2hex(openssl_random_pseudo_bytes(32, true));
		return sha1(mt_rand());
	}


	private $cacheService;							// CacheService
	private $category = 'sessions';
	private $ttl = null;
};

?>