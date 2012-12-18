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
	}

	public function load($key) {
		if (!$this->activated)
			throw new \LogicException('Caching is disabled');

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
		if (!$this->directory)
			throw new \LogicException('The cache directory has not been configured');
		return $this->directory.'/'.md5(serialize($key)).'.cache.gz';
	}


	private $directory = null;
	private $activated = true;
};

?>