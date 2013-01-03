<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class CacheService {
	/**
	 * Sets the directory that this service will use to store entries.
	 * It is strongly encouraged that the directory be empty.
	 */
	public function setCacheDirectory($directory) {
		if (!is_dir($directory))
			throw new \RuntimeException('The cache directory doesn\'t exist: '.$directory);
		$this->directory = $directory;
	}


	/**
	 * Enables all caching, this is the default value
	 */
	public function enable() {
		$this->activated = true;
	}

	/**
	 * Disables all caching.
	 * Clear and store will have no effect. Load will always return null.
	 */
	public function disable() {
		$this->activated = false;
	}
	
	/**
	 * Sets the content of the cache for this key.
	 * The type of data that is stored is user-defined. It's the same data that will be retreived using load().
	 *
	 * If there is already an entry for this key, then it is replaced.
	 *
	 * If $ttl is 0 or null, it will be set to a huge amount of seconds.
	 *
	 * @param string 	$key 				The key that will be used to load the resource again
	 * @param string 	$data				Data to store in the cache
	 * @param integer 	$ttl				Number of seconds to keep this cache entry alive
	 * @param string 	$category 			The category where to store the element
	 * @return string
	 */
	public function store($key, $data, $ttl = null, $category = '') {
		if (!$this->activated)
			return;

		if ($ttl == 0)
			$ttl = 3600 * 24 * 365 * 20;

		$dir = $this->directory.rtrim(DIRECTORY_SEPARATOR.$category, DIRECTORY_SEPARATOR);
		if (!file_exists($dir)) {
			if (!mkdir($dir, 0755, true))
				throw new \RuntimeException('Impossible to create directory '.$dir);
		}

		$path = $this->keyToFile($key, $category);
		file_put_contents($path, $data);
		touch($path, time() + intval($ttl));

		if ($this->log)
			$this->log->debug('Stored element "'.$key.'" into "'.$path.'", TTL = '.$ttl.' seconds');
	}

	/**
	 * Returns the content of the cache for this key.
	 *
	 * Returns null if the cache has no matching entry.
	 *
	 * @param string 	$key		The key to load
	 * @param string 	$category 	The category where the element belongs
	 * @return string
	 */
	public function load($key, $category = '') {
		if (!$this->activated)
			return null;

		$file = $this->keyToFile($key, $category);
		if (!file_exists($file))
			return null;
		return $this->loadFile($file, $key);
	}

	/**
	 * Returns the content of the cache for this key that matches a regex.
	 *
	 * Returns null if the cache has no matching entry.
	 * If there are multiple available entries, it is undefined which one is chosen.
	 *
	 * @param string 	$key		The regex of the key to load
	 * @param string 	$category 	The category where the element belongs
	 * @return string
	 */
	public function loadMatch($regex, $category = '') {
		if (!$this->activated)
			return null;

		$regex2 = $this->regexToFile($regex);
		$dir = $this->directory.rtrim(DIRECTORY_SEPARATOR.$category, DIRECTORY_SEPARATOR);
		if (!file_exists($dir))
			return null;

		$chosenFile = null;
		foreach (scandir($dir) as $f) {
			if (preg_match($regex2, $f))
				$chosenFile = $f;
		}
		if (!$chosenFile)
			return null;

		return $this->loadFile($dir.DIRECTORY_SEPARATOR.$chosenFile, $regex);
	}
	
	/**
	 * Clears the entry with a specific key.
	 * @param string 	$key 		Key of the entry
	 * @param string 	$category 	The category where the element belongs
	 */
	public function clear($key, $category = '') {
		if (!$this->activated)
			return;

		$file = $this->keyToFile($key, $category);
		try {
			unlink($file);
			if ($this->log)
				$this->log->debug('Cleared cache file '.$f. ' (key: '.$key.')');
		} catch(\Exception $e) {}
	}
	
	/**
	 * Clears all entries that match a regex.
	 *
	 * @param string 	$key 		Regex of the key to remove
	 * @param string 	$category 	The category where the element belongs
	 */
	public function clearMatch($regex, $category = '') {
		if (!$this->activated)
			return;

		$transformedRegex = $this->regexToFile($regex);
		$dir = $this->directory.rtrim(DIRECTORY_SEPARATOR.$category, DIRECTORY_SEPARATOR);
		if (!file_exists($dir))
			return null;

		foreach (scandir($dir) as $f) {
			try {
				if (!preg_match($transformedRegex, $f))
					continue;
				unlink($dir.DIRECTORY_SEPARATOR.$f);
				if ($this->log)
					$this->log->debug('Cleared cache file '.$f. ' (regex: '.$regex.')');
			} catch(\Exception $e) {}
		}
	}

	/**
	 * Clears all entries created by this service.
	 * @param string 	$category 	The category where the elements belongs
	 */
	public function clearAll($category = '') {
		$this->clearMatch('/.*/', $category);
	}

	/**
	 * Constructor.
	 * You can specify a logging object that will be used to debug manipulations.
	 * @param \Monolog\Logger 	$log 		Log object that will be used for debug entries, or null
	 */
	public function __construct(\Monolog\Logger $log = null) {
		$this->log = $log;
	}



	private function keyToFile($key, $category) {
		if (!$this->directory)
			throw new \LogicException('The cache directory has not been configured');

		$key = ltrim($key, '/\\');
		$key = str_replace('.', '', $key);
		$key = str_replace('/', '-', $key);
		$key = str_replace('\\', '-', $key);
		$key = str_replace('{', '', $key);
		$key = str_replace('}', '', $key);
		return $this->directory.rtrim(DIRECTORY_SEPARATOR.$category, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$key.'.cache.txt';
	}

	private function regexToFile($regex) {
		if (!$this->directory)
			throw new \LogicException('The cache directory has not been configured');

		$regex = preg_replace('/\\^\\\\\\//', '^', $regex);
		$regex = preg_replace('/\\\\\\./', '', $regex);
		$regex = preg_replace('/(.+)\\/(.+)/', '$1-$2', $regex);
		$regex = preg_replace('/(.+)\\\\\\\\(.+)/', '$1-$2', $regex);
		$regex = preg_replace('/\\\\\\{/', '', $regex);
		$regex = preg_replace('/\\\\\\}/', '', $regex);
		$regex = preg_replace('/\\$/', '\\.cache\\.txt$', $regex);
		return $regex;
	}

	private function loadFile($file, $key = '') {
		$fp = fopen($file, 'rb');
		if (!$fp)
			return null;

		// checking whether file is stale
		if (fstat($fp)['mtime'] <= time()) {
			if ($this->log)
				$this->log->debug('Found stale element "'.$key.'" in file '.$file);
			fclose($fp);
			unlink($file);
			return null;
		}

		// reading content
		$data = stream_get_contents($fp);
		fclose($fp);
		if ($this->log)
			$this->log->debug('Loaded element "'.$key.'" from file '.$file);
		return $data;
	}


	private $directory = null;
	private $activated = true;
	private $compressionLevel = -1;
	private $log = null;
};

?>