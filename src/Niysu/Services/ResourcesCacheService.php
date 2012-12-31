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
	 * Sets the directory that this service will use to store entries.
	 * All the entries will start by a prefix, so the directory doesn't necessarly need to be empty. However for safety it is really recommended to have an empty directory.
	 */
	public function setCacheDirectory($directory) {
		if (!is_dir($directory))
			throw new \RuntimeException('The cache directory doesn\'t exist: '.$directory);
		$this->directory = $directory;
	}
	
	/**
	 * Sets the content of the cache for this resource.
	 * The type of data that is stored is user-defined. It's the same data that will be retreived using load().
	 *
	 * If there is already an entry for this $url+$requestVaryHeaders, then it is replaced.
	 *
	 * If $ttl is 0 or null, it will be set to a huge amount of seconds.
	 *
	 * @param string 	$url 					The URL of the resource
	 * @param string 	$data					Data to store in the cache
	 * @param array 	$requestVaryHeaders		Array of headers=>value that determine the cache entry
	 * @param integer 	$ttl					Number of seconds to keep this cache entry alive
	 * @return string
	 */
	public function store($url, $data, $requestVaryHeaders = [], $ttl = null) {
		if ($ttl == 0)
			$ttl = 3600 * 24 * 365 * 20;

		$file = $this->keyToFile($url, $requestVaryHeaders);
		file_put_contents($file, $data);
		touch($file, time() + intval($ttl));

		if ($this->log)
			$this->log->debug('Stored element "'.$key.'", TTL = '.$ttl.' seconds');
	}

	/**
	 * Returns the content of the cache for this resource.
	 *
	 * The $requestVaryHeaders must match exactly the value set on storage.
	 * Returns null if the cache has no entry.
	 *
	 * @param string 	$url 					The URL of the resource
	 * @param array 	$requestVaryHeaders		Array of headers=>value that determine the cache entry
	 * @return string
	 */
	public function load($url, $requestVaryHeaders = []) {
		$file = $this->keyToFile($url, $requestVaryHeaders);

		$fp = fopen($file, 'rb');
		if (!$fp)
			return null;
		if (fstat($fp)['mtime'] >= time()) {
			if ($this->log)
				$this->log->debug('Found stale element "'.$url.'" in file '.$file);

			// stale
			fclose($fp);
			unlink($file);
			return null;
		}
		$data = stream_get_contents($fp);
		fclose($fp);

		if ($this->log)
			$this->log->debug('Loaded element "'.$url.'" from file '.$file);

		return $fp;
	}
	
	/**
	 * Clears all entries with a specific wildcard.
	 * If multiple entries with different $requestVaryHeaders have been created, they are all destroyed.
	 *
	 * @param string 	$url 		URL of the resource, with wildcards accepted
	 */
	public function clear($url) {
		if (!$this->directory)
			throw new \LogicException('The cache directory has not been configured');

		$url = ltrim($url, '/\\');
		$url = str_replace('.', '', $url);
		$url = str_replace('/', '-', $url);
		$url = str_replace('\\', '-', $url);

		foreach (glob($this->directory.DIRECTORY_SEPARATOR.'cache-'.$url.'-*.txt') as $f) {
			unlink($f);
			if ($this->log)
				$this->log->debug('Cleared cache file '.$file. ' (wildcard: '.$url.')');
		}
	}

	/**
	 * Clears all entries created by this service.
	 */
	public function clearAll() {
		$this->clear('*');
	}

	/**
	 * Constructor.
	 * You can specify a logging object that will be used to debug manipulations.
	 * @param \Monolog\Logger 	$log 		Log object that will be used for debug entries, or null
	 */
	public function __construct(\Monolog\Logger $log = null) {
		$this->log = $log;
	}



	private function keyToFile($url, $requestVaryHeaders) {
		if (!$this->directory)
			throw new \LogicException('The cache directory has not been configured');

		$url = ltrim($url, '/\\');
		$url = str_replace('.', '', $url);
		$url = str_replace('/', '-', $url);
		$url = str_replace('\\', '-', $url);
		return $this->directory.DIRECTORY_SEPARATOR.'cache-'.$url.'-'.md5(serialize($requestVaryHeaders)).'.txt';
	}


	private $directory = null;
	private $log;
};

?>