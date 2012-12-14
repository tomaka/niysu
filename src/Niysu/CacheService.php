<?php
namespace Niysu;

class CacheService {
	public function __construct($directory) {
		if (!is_dir($directory))
			throw new \LogicException('The cache directory doesn\'t exist: '.$directory);
		$this->directory = $directory;
	}
	
	public function exists($key) {
		return file_exists($this->keyToFile($key));
	}
	
	/// \param $ttl Time in seconds until the element is automatically cleared, or null if no TTL
	public function store($key, $data, $ttl = null) {
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
	}

	public function load($key) {
		$file = $this->keyToFile($key);
		if (!file_exists($file))
			throw new \LogicException('Cache element doesn\'t exist');

		$fp = fopen('compress.zlib://'.$file, 'rb');
		$data = '';
		while (!feof($fp))
			$data .= fread($fp, 4096);
		fclose($fp);
		return $data;
	}
	
	public function clear($key) {
		$file = $this->keyToFile($key);
		unlink($file);
	}

	public function clearAll() {

	}



	private function keyToFile($key) {
		return $this->directory.'/'.md5(serialize($key)).'.cache.gz';
	}

	private $directory;
};

?>