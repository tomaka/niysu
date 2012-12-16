<?php
namespace Niysu\Services;

class CacheServiceProvider {
	public function __construct($directory = null) {
		$this->directory = $directory;
	}
	
	public function setCacheDirectory($dir) {
		$this->directory = $dir;
	}
	
	public function __invoke() {
		return new CacheService($this->directory);
	}
	
	private $directory;
};

?>