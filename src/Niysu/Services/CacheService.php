<?php
namespace Niysu\Services;

class CacheService {
	public function setCacheDirectory($directory) {
		if (!is_dir($directory))
			throw new \LogicException('The cache directory doesn\'t exist: '.$directory);
		$this->directory = $directory;
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
		$data = unserialize(stream_get_contents($fp));

		if (!isset($data[$key])) {
			fclose($fp);
			return false;
		}

		if (intval($data[$key]) < intval(microtime(true))) {
			if ($this->logService)
				$this->logService->debug('Element is stale: '.$key);

			unset($data[$key]);
			unlink($this->keyToFile($key));
			fseek($fp, 0);
			fwrite($fp, serialize($data));
			fclose($fp);
			return false;
		}

		fclose($fp);
		return file_exists($this->keyToFile($key));
	}
	
	/// \param $ttl Time in seconds until the element is automatically cleared, or null if no TTL
	public function store($key, $data, $ttl = null) {
		if (!$this->activated)
			return;

		$file = $this->keyToFile($key);

		$fp = fopen('compress.zlib://'.$file, 'wb');
		if (!$fp) {
			(new LogWriter())->write('Error while opening file "'.$file.'" for caching');
			return;
		}

		if (!fwrite($fp, $data)) {
			(new LogWriter())->write('Error while writing file "'.$file.'" for caching');
			fclose($fp);
			unlink($file);
			return;
		}
		fclose($fp);

		// adding to the resources list
		$fp = fopen($this->directory.DIRECTORY_SEPARATOR.'resources.list', 'c+b');
		$data = unserialize(stream_get_contents($fp));
		$data[$key] = round(intval(microtime(true)) + intval($ttl ? $ttl : 99999));
		fseek($fp, 0);
		fwrite($fp, serialize($data));
		fclose($fp);


		if ($this->logService)
			$this->logService->debug('Stored element "'.$key.'", TTL = '.$ttl.' seconds');
	}

	public function load($key) {
		if (!$this->activated)
			throw new \LogicException('Caching is disabled');

		// loading file
		if (!$this->exists($key))
			throw new \LogicException('Cache element doesn\'t exist');

		$file = $this->keyToFile($key);
		$fp = fopen('compress.zlib://'.$file, 'rb');
		$data = stream_get_contents($fp);
		fclose($fp);

		if ($this->logService)
			$this->logService->debug('Loaded element "'.$key.'" from file '.$file);

		return $data;
	}
	
	public function clear($key) {
		$file = $this->keyToFile($key);
		unlink($file);

		// removing from resources list
		$fp = $this->openResourcesList();
		$data = $this->readResourcesList($fp);
		unset($data[$key]);
		$this->writeResourcesList($data);
		fclose($fp);
	}

	public function clearAll() {

	}

	public function __construct($logService) {
		$this->logService = $logService;
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
		return unserialize(stream_get_contents($fp));
	}

	private function writeResourcesList($fp, $data) {
		fseek($fp, 0);
		fwrite($fp, serialize($data));
	}


	private $directory = null;
	private $activated = true;
	private $logService = null;
};

?>