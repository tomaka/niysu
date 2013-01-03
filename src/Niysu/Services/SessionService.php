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
	 * @param string 	$category 		The category in the CacheService
	 */
	public function setCategory($category) {
		$this->category = $category;
	}

	/**
	 * Sets the duration of session written with "offsetSet".
	 * @param integer 		$duration 		Number of seconds until the session expires
	 */
	public function setSessionsDuration($duration) {
		$this->ttl = $duration;
	}

	/**
	 * Returns true if the session with this ID exists.
	 * @param string 	$id 	Session ID
	 * @return boolean
	 */
	public function offsetExists($id) {
		$data = $this->cacheService->load($id, $this->category);
		return $data !== null;
	}

	/**
	 * Returns the content of the session with this ID.
	 * Returns an associative array with variables => values
	 * @param string 	$id 	Session ID
	 * @return array
	 * @throws RuntimeException If no session exists with this ID
	 */
	public function offsetGet($id) {
		$data = $this->cacheService->load($id, $this->category);
		if ($data === null)
			throw new \RuntimeException('Trying to access a non-existing session: '.$id);
		return unserialize($data);
	}

	/**
	 * Sets the content of the session with this ID.
	 * $value must be an associative array with variables => values
	 * @param string 	$id 		Session ID
	 * @param array 	$value 		Array containing the session variables
	 */
	public function offsetSet($id, $value) {
		if (!is_array($value))
			throw new \LogicException('Value must be an array');
		if (!$id)
			$id = self::generateSessionID();
		$this->cacheService->store($id, serialize($value), $this->ttl, $this->category);
	}

	/**
	 * Deletes the session with this ID.
	 * @param string 	$id 	Session ID
	 */
	public function offsetUnset($id) {
		$this->cacheService->clear($id, $this->category);
	}

	/**
	 * Constructor.
	 * @param CacheService 		$cacheService 		The cache service to use
	 */
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