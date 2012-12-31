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
		if ($ttl == 0)
			$ttl = 3600 * 24 * 365 * 20;

		$path = $this->urlToFileBase($url);
		ksort($varyHeaders);
		foreach ($varyHeaders as $h => $v)
			$path .= '-'.substr(md5($h.':'.$v), 0, 6);
		$path .= '.cache.txt';

		file_put_contents($path, $data);
		touch($path, time() + intval($ttl));

		if ($this->log)
			$this->log->debug('Stored element "'.$key.'" into "'.$path.'", TTL = '.$ttl.' seconds');
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
		$baseFile = $this->urlToFileBase($url);

		// we build the regex that the filename must match
		ksort($requestHeaders);
		$regex = '/^'.str_replace('/', '\\/', preg_quote($baseFile));
		foreach ($requestHeaders as $h => $v)
			$regex .= '(\\-'.substr(md5($h.':'.$v), 0, 6).')?';
		$regex .= '\\.cache\\.txt$/';

		// trying to find an appropriate file
		$chosenFile = null;
		foreach (glob($baseFile.'*.cache.txt') as $f) {
			if (preg_match($regex, $f))
				$chosenFile = $f;
		}
		if (!$chosenFile)
			return null;

		// found a file
		$fp = fopen($chosenFile, 'rb');
		if (!$fp)
			return null;

		// checking whether file is stale
		if (fstat($fp)['mtime'] <= time()) {
			if ($this->log)
				$this->log->debug('Found stale element "'.$url.'" in file '.$chosenFile);
			fclose($fp);
			unlink($chosenFile);
			return null;
		}

		// reading content
		$data = stream_get_contents($fp);
		fclose($fp);
		if ($this->log)
			$this->log->debug('Loaded element "'.$url.'" from file '.$chosenFile);
		return $data;
	}
	
	/**
	 * Clears all entries with a specific wildcard.
	 * If multiple entries with different $requestVaryHeaders have been created, they are all destroyed.
	 *
	 * @param string 	$url 		URL of the resource, with wildcards accepted
	 */
	public function clear($url) {
		$path = $this->urlToFileBase($url);

		foreach (glob($path.'-*.cache.txt') as $f) {
			unlink($f);
			if ($this->log)
				$this->log->debug('Cleared cache file '.$f. ' (wildcard: '.$url.')');
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



	private function urlToFileBase($url) {
		if (!$this->directory)
			throw new \LogicException('The cache directory has not been configured');

		$url = ltrim($url, '/\\');
		$url = str_replace('.', '', $url);
		$url = str_replace('/', '-', $url);
		$url = str_replace('\\', '-', $url);
		return $this->directory.DIRECTORY_SEPARATOR.'cache-'.$url;
	}


	private $directory = null;
	private $log;
};

?>