<?php
namespace Niysu;

class RoutesCollection {
	public function register($url, $method, $callback) {
		$registration = new Route($this->prefix.$url, $method, $callback);
		foreach ($this->globalBefores as $b)
			$registration->before($b);
		$this->routes[] = $registration;
		return $registration;
	}

	public function registerStaticDirectory($path, $prefix = '/') {
		while (substr($prefix, -1) == '/')
			$prefix = substr($prefix, 0, -1);
		$this->register($prefix.'/{file}', 'get', function($file, $response, $elapsedTime) {
			if (!extension_loaded('fileinfo'))
				throw new \LogicException('The "fileinfo" extension must be activated');

			$finfo = finfo_open(FILEINFO_MIME);
			$mime = finfo_file($finfo, $file);
			finfo_close($finfo);
			$pathinfo = pathinfo($file);
			if ($pathinfo['extension'] == 'css')
				$mime = 'text/css';
			if ($pathinfo['extension'] == 'js')
				$mime = 'application/javascript';
			if ($pathinfo['extension'] == 'svg')
				$mime = 'image/svg+xml';
			if (substr($mime, 0, 15) == 'application/xml' && ($pathinfo['extension'] == 'htm' || $pathinfo['extension'] == 'html'))
				$mime = 'application/xhtml+xml';
			$response->setHeader('Content-Type', $mime);
			$response->appendData(file_get_contents($file));
		})
		->pattern('file', '([^\\.]{2,}.*|.)')
		->before(function(&$file, &$isWrongResource) use ($path) {
			$file = $path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file);
			if (file_exists($file)) {
				if (is_dir($file))
					$isWrongResource = true;
				return;
			}

			$checkDir = dirname($file);
			if (!file_exists($checkDir) || !is_dir($checkDir)) {
				$isWrongResource = true;
				return;
			}

			$dirToCheck = dir($checkDir);
			while ($entry = $dirToCheck->read()) {
				$entryLong = $dirToCheck->path.'/'.$entry;
				$pathinfo = pathinfo($entryLong);
				if ($pathinfo['dirname'].DIRECTORY_SEPARATOR.$pathinfo['filename'] == $file) {
					$file = $entryLong;
					return;
				}
			}

			$isWrongResource = true;
		});
	}

	public function redirect($url, $method, $target, $statusCode = 301) {
		$registration = new Route($this->prefix.$url, $method, function($response) use ($target, $statusCode) { $response->setStatusCode($statusCode); $response->setHeader('Location', $target); });
		$this->routes[] = $registration;
		return $registration;
	}

	public function __construct($prefix = '') {
		$this->setPrefix($prefix);
	}

	public function setPrefix($prefix) {
		if (count($this->routes) >= 1)
			throw new \LogicException('Cannot change prefix once routes have been registered');

		while (substr($prefix, -1) == '/')
			$prefix = substr($prefix, 0, -1);

		$this->prefix = $prefix;
	}

	public function before($f) {
		if (!is_callable($f))
			throw new \LogicException('The before function/object is not callable');
		$this->globalBefores[] = $f;
	}

	/// \brief Returns an array of all the registered Routes
	public function getRoutesList() {
		return $this->routes;
	}


	private $routes = [];					// array of instances of Route
	private $prefix = '';					// prefix to add to all URLs
	private $globalBefores = [];			// array of functions that are automatically added as ->before
};

?>