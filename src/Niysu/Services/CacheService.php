<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class CacheService {
	public function setCacheDirectory($directory) {
		if (!is_dir($directory))
			throw new \RuntimeException('The cache directory doesn\'t exist: '.$directory);
		$this->directory = $directory;
	}

	public function setCompressionLevel($level) {
		$this->compressionLevel = $level;
	}

	/// \brief Activates all caching, this is the default value
	public function activate() {
		$this->activated = true;
	}

	public function deactivate() {
		$this->activated = false;
	}
	
	public function exists($key) {
		if (!$this->activated)
			return false;

		// getting the resources list
		$fp = fopen($this->directory.DIRECTORY_SEPARATOR.'resources.list', 'c+b');
		$data = $this->readResourcesList($fp);
		flock($fp, LOCK_SH);

		if (!isset($data[$key])) {
			flock($fp, LOCK_UN);
			fclose($fp);
			return false;
		}

		if (intval($data[$key]) < intval(microtime(true))) {
			if ($this->logService)
				$this->logService->debug('Element is stale: '.$key);

			unset($data[$key]);
			try { unlink($this->keyToFile($key)); } catch(\Exception $e) {}
			flock($fp, LOCK_EX);
			fseek($fp, 0);
			fwrite($fp, serialize($data));
			flock($fp, LOCK_UN);
			fclose($fp);
			return false;
		}

		flock($fp, LOCK_UN);
		fclose($fp);
		return file_exists($this->keyToFile($key));
	}
	
	/// \param $ttl Time in seconds until the element is automatically cleared, or null if no TTL
	public function store($key, $data, $ttl = null) {
		if (!$this->activated)
			return;

		// adding to the resources list
		$fp = fopen($this->directory.DIRECTORY_SEPARATOR.'resources.list', 'c+b');
		flock($fp, LOCK_SH);
		$resourcesList = unserialize(stream_get_contents($fp));
		$resourcesList[$key] = round(intval(microtime(true)) + intval($ttl ? $ttl : 99999));

		flock($fp, LOCK_EX);
		$file = $this->keyToFile($key);
		file_put_contents($file, gzencode($data, $this->compressionLevel));

		fseek($fp, 0);
		fwrite($fp, serialize($resourcesList));
		flock($fp, LOCK_UN);
		fclose($fp);


		if ($this->logService)
			$this->logService->debug('Stored element "'.$key.'", TTL = '.$ttl.' seconds');
	}

	public function load($key) {
		if (!$this->activated)
			throw new \LogicException('Caching is disabled');

		$fp = $this->openResourcesList();
		flock($fp, LOCK_SH);
		$data = $this->readResourcesList($fp);

		// loading file
		if (!isset($data[$key])) {
			flock($fp, LOCK_UN);
			fclose($fp);
			throw new \LogicException('Cache element doesn\'t exist');
		}

		$file = $this->keyToFile($key);
		$fp = fopen($file, 'rb');
		$data = stream_get_contents($fp);
		flock($fp, LOCK_UN);
		fclose($fp);

		if ($this->log)
			$this->log->debug('Loaded element "'.$key.'" from file '.$file);

		return gzdecode($data);
	}
	
	public function clear($key) {
		// removing from resources list
		$fp = $this->openResourcesList();
		$data = $this->readResourcesList($fp);
		flock($fp, LOCK_EX);

		$file = $this->keyToFile($key);
		try { unlink($file); } catch(\Exception $e) {}

		unset($data[$key]);

		$this->writeResourcesList($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
	}

	public function clearAll() {
		$fp = $this->openResourcesList();
		flock($fp, LOCK_SH);
		$data = $this->readResourcesList($fp);

		flock($fp, LOCK_EX);
		foreach ($data as $k => $v) {
			try {
				unlink($this->keyToFile($k));
			} catch(\Exception $e) {
				if ($this->logService)
					$this->logService->warn($e->getMessage());
			}
		}
		$data = [];

		$this->writeResourcesList($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);
	}

	public function __construct($log) {
		$this->log = $log;
	}



	private function keyToFile($key) {
		if (!$this->directory)
			throw new \LogicException('The cache directory has not been configured');
		return $this->directory.DIRECTORY_SEPARATOR.md5(serialize($key)).'.cache.gz';
	}

	private function openResourcesList() {
		return fopen($this->directory.DIRECTORY_SEPARATOR.'resources.list', 'c+b');
	}

	private function readResourcesList($fp) {
		fseek($fp, 0);
		$val = unserialize(stream_get_contents($fp));
		if (!$val) $val = [];
		return $val;
	}

	private function writeResourcesList($fp, $data) {
		fseek($fp, 0);
		ftruncate($fp, 0);
		fwrite($fp, serialize($data));
	}


	private $directory = null;
	private $activated = true;
	private $compressionLevel = -1;
	private $log = null;
};

?>