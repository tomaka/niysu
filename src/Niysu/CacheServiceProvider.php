<?php
namespace Niysu;

class CacheServiceProvider {
	public function __construct($directory = null) {
		try {
			$this->directory = $directory;
		} catch(Exception $e) {
			$this->directory = null;
		}
	}
	
	public function setCacheDirectory($dir) {
		$this->directory = $dir;
	}
	
	public function __invoke() {
		if (!$this->directory)
			throw new \LogicException('The cache directory has not been configured');
		if (!is_dir($this->directory))
			throw new \LogicException('The cache directory doesn\'t exist: '.$this->directory);
		if (!is_writable($this->directory))
			throw new \LogicException('The cache directory is not writable: '.$this->directory);
		
		return new CacheService($this->directory);
	}
	
	private $directory;
};

?>