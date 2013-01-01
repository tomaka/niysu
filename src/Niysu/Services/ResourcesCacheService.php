<?php
namespace Niysu\Services;

/**
 * Service that manages server-side caching of resources.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class ResourcesCacheService {
	/**
	 * Sets the category that will be passed to the CacheService.
	 * By default, this is "resources"
	 */
	public function setCategory($category) {
		$this->category = $category;
	}
	
	/**
	 * Sets the content of the cache for this resource.
	 * The type of data that is stored is user-defined. It's the same data that will be retreived using load().
	 *
	 * Each cache entry is defined by its URL, but also by its value of "$varyHeaders".
	 * If there is already an entry for this $url+$varyHeaders, then it is replaced.
	 *
	 * If $ttl is 0 or null, it will be set to a huge amount of seconds.
	 *
	 * @param string 	$url 				The URL of the resource
	 * @param string 	$data				Data to store in the cache
	 * @param array 	$varyHeaders		Array of headers=>value that determine the cache entry
	 * @param integer 	$ttl				Number of seconds to keep this cache entry alive
	 * @return string
	 */
	public function store($url, $data, $varyHeaders = [], $ttl = null) {
		$path = $url;
		ksort($varyHeaders);
		foreach ($varyHeaders as $h => $v)
			$path .= '-'.substr(md5($h.':'.$v), 0, 6);

		$this->cache->store($path, $data, $ttl, $this->category);
	}

	/**
	 * Returns the content of the cache for this resource.
	 *
	 * The $requestHeaders array contains a list of headers.
	 * This array must be a superset of an array set during the call to store().
	 *
	 * Returns null if the cache has no entry.
	 * If there are multiple available entries, it is implementation-defined as for which one is chosen.
	 *
	 * @param string 	$url 				The URL of the resource
	 * @param array 	$requestHeaders		Array of headers=>value that determine the cache entry
	 * @return string
	 */
	public function load($url, $requestHeaders = []) {
		// we build the regex that the filename must match
		ksort($requestHeaders);
		$regex = '/^'.str_replace('/', '\\/', preg_quote($url));
		foreach ($requestHeaders as $h => $v)
			$regex .= '(\\-'.substr(md5($h.':'.$v), 0, 6).')?';
		$regex .= '$/';

		return $this->cache->loadMatch($regex, $this->category);
	}
	
	/**
	 * Clears all entries with a regex.
	 * If multiple entries with different $requestVaryHeaders have been created, they are all destroyed.
	 *
	 * @param string 	$url 		URL of the resource, with wildcards accepted
	 */
	public function clear($url) {
		throw new \LogicException('Not yet implemented');
		/*$path = $this->urlToFileBase($url);

		foreach (glob($path.'*.cache.txt') as $f) {
			unlink($f);
			if ($this->log)
				$this->log->debug('Cleared cache file '.$f. ' (wildcard: '.$url.')');
		}*/
	}

	/**
	 * Clears all entries created by this service.
	 */
	public function clearAll() {
		$this->cache->clearAll($this->category);
	}

	/**
	 * Constructor.
	 * @param CacheService 		$cache 		Cache service that will be used for caching
	 */
	public function __construct(CacheService $cache) {
		$this->cache = $cache;
	}



	private $category = 'resources';
	private $cache;
};

?>