<?php
namespace Niysu;

class Server {
	public function __construct($environment = null) {
		$constructionTime = microtime(true);
		
		// building global scope
		$this->scope = new Scope();
		$this->scope->add('server', $this);
		$this->scope->setVariablePassByRef('server', false);
		$this->scope->add('elapsedTime', function() use ($constructionTime) { $now = microtime(true); return round(1000 * ($now - $constructionTime)); });
		$this->scope->setVariablePassByRef('elapsedTime', false);
		
		// building default services providers
		$this->setServiceProvider('database', new DatabaseServiceProvider());
		$this->setServiceProvider('log', new LogServiceProvider());
		$this->setServiceProvider('cacheMe', new ResourceCacheServiceProvider());
		$this->setServiceProvider('cache', new CacheServiceProvider(__DIR__.'/../cache'));

		if (class_exists('Twig_Loader_Filesystem'))
			$this->setServiceProvider('twig', new TwigServiceProvider());

		// loading environment
		if ($environment)
			$this->loadEnvironment($environment);
	}

	public function getServiceProvider($serviceName) {
		if (!$this->serviceProviders[$serviceName])
			throw new \LogicException('Service "'.$serviceName.'" doesn\'t exist');
		return $this->serviceProviders[$serviceName];
	}

	public function setServiceProvider($serviceName, $provider) {
		$this->serviceProviders[$serviceName] = $provider;
	}
	
	public function getService($serviceName) {
		if (!isset($this->serviceProviders[$serviceName]))
			throw new \LogicException('Service "'.$serviceName.'" doesn\'t exist');

		$localScope = clone $this->scope;
		foreach($this->serviceProviders as $sNameIter => $provider) {
			$localScope->addByCallback($sNameIter, function(Scope $s) use ($provider) {
				return $s->callFunction($provider);
			});
		}

		$val = $this->serviceProviders[$serviceName];
		if (is_callable($val)) {
			return $localScope->callFunction($val);
		}

		throw new \LogicException('Unvalid service provider format');
	}

	public function register($url, $method, $callback) {
		$registration = new Route($url, $method, $callback);
		foreach ($this->globalBefores as $b)
			$registration->before($b);
		$this->routes[] = $registration;
		return $registration;
	}

	public function registerStaticDirectory($path, $prefix = '/') {
		if (substr($prefix, -1) == '/')
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
		$registration = new Route($url, $method, function($response) use ($target, $statusCode) { $response->setStatusCode($statusCode); $response->setHeader('Location', $target); });
		$this->routes[] = $registration;
		return $registration;
	}

	/// \brief Handles the request described in $input by calling the functions from $output
	public function handle(HTTPRequestInterface $input = null, HTTPResponseInterface $output = null) {
		if (!$input)	$input = new HTTPRequestGlobal();
		if (!$output)	$output = new HTTPResponseGlobal();

		$log = $this->getService('log');
		$log->debug('Starting handling of resource', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);

		$this->currentResponsesStack[] = $output;

		try {
			$handleScope = clone $this->scope;
			$handleScope->add('request', $input);
			$handleScope->add('response', $output);

			foreach($this->serviceProviders as $serviceName => $provider) {
				$handleScope->addByCallback($serviceName, function(Scope $s) use ($provider) {
					return $s->callFunction($provider);
				});
				$handleScope->addByCallback($serviceName.'Service', function(Scope $s) use ($provider) {
					return $s->callFunction($provider);
				});
			}
			
			foreach ($this->routes as $route) {
				$localScope = clone $handleScope;
				if ($route->handle($localScope)) {
					$log->debug('Successful handling of resource', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);
					if ($nb = gc_collect_cycles())
						$log->warn('gc_collect_cycles() returned non-zero value: '.$nb);
					return;
				}
			}


		} catch(Exception $exception) {
			try { $this->getService('log')->err($exception->getMessage(), $exception->getTrace()); } catch(Exception $e) {}
			if (!$output->isHeadersListSent())
				$output->setStatusCode(500);
			if ($this->printErrors) {
				$this->printError($exception);

			} else {
				$output->setPlainTextData('A server-side error occured. Please try again later.');
			}
		}

		// handling 404 if we didn't find any handler
		$log->debug('Didn\'t find any route for request, returning 404', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);
		$output->setStatusCode(404);
		array_pop($this->currentResponsesStack);
	}

	/// \brief Loads either a file or an array
	public function loadEnvironment($environment) {
		if (is_array($environment)) {
			$this->loadEnvironmentData($environment);

		} else if (is_string($environment)) {
			if (!file_exists($environment))
				throw new \LogicException('File doesn\'t exist: '.$environment);
			$val = (include $environment);
			if (!$val)
				throw new \LogicException('Environment file didn\'t return any data');
			$this->loadEnvironmentData($val);
		}
	}
	
	private function loadEnvironmentData($enviData) {
		foreach ($enviData as $key => $value) {
			// note: no switch here because in PHP 0 == 'any string'
			if ($key === 'name') {

			} else if ($key === 'handleErrors') {
				if ($value == true)
					$this->replaceErrorHandling();

			} else if ($key === 'printErrors') {
				$this->printErrors = ($value == true);

			} else if ($key === 'showRoutesOn404') {
				$this->showRoutesOn404 = ($value == true);

			} else if ($key === 'before') {
				if (is_array($value))	$this->globalBefores = $value;
				else					$this->globalBefores = [ $value ];

			} else if ($key === 'config') {
				if (!is_callable($value))
					throw new \LogicException('The "config" function in environment data must be callable');

				// building scope for configuration
				$configScope = clone $this->scope;
				foreach ($this->serviceProviders as $serviceName => $provider)
					$configScope->add($serviceName.'Provider', $provider);
				$configScope->callFunction($value);

			} else {
				if (is_numeric($key) && is_array($value)) {
					$this->loadEnvironmentData($value);
				} else {
					throw new \LogicException('Unknown environment option: '.$key);
				}
			}
		}
	}
	
	private function replaceErrorHandling() {
		// in case of critical fault
		register_shutdown_function(function() {
			$error = error_get_last();
			if ($error != null) {
				try {
					$this->getService('log')->crit($error['message'], $error);
					if ($this->printErrors)
						$this->printError(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));

					if (count($this->currentResponsesStack) >= 1) {
						$response = $this->currentResponsesStack[count($this->currentResponsesStack) - 1];
						if (!$response->isHeadersListSent())
							$response->setStatusCode(500);
					}
				} catch(Exception $e) { }
			}
		});
		
		// handler to be called when a PHP error is detected (like calling an unvalid function)
		set_error_handler(function($errno, $errstr, $errfile, $errline, $errcontext) {
			if ($errno == E_NOTICE || $errno == E_USER_NOTICE) {
				try { $this->getService('log')->notice($errstr, [ 'file' => $errfile, 'line' => $errline, 'content' => $errcontext ]); } catch(Exception $e) {}
				return true;
			}

			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
		
		// changing the handler to be called when an exception is not handled
		set_exception_handler(function($exception) {
			try { $this->getService('log')->err($exception->getMessage(), $exception->getTrace()); } catch(Exception $e) {}
			if ($this->printErrors)
				$this->printError($exception);

			if (count($this->currentResponsesStack) >= 1) {
				$response = $this->currentResponsesStack[count($this->currentResponsesStack) - 1];
				if (!$response->isHeadersListSent())
					$response->setStatusCode(500);
			}
		});
		
		// finally disabling error reporting
		error_reporting(0);
	}

	private function printError(Exception $e) {
		if (count($this->currentResponsesStack) == 0)
			return;

		$response = $this->currentResponsesStack[count($this->currentResponsesStack) - 1];

		if (!$response->isHeadersListSent()) {
			$response->setStatusCode(500);
			$response->setHeader('Content-Type', 'text/html; charset=utf8');
			$response->setHeader('Cache-Control', 'no-cache');
		}

		$headersSentFile = null;
		$headersSentLine = null;
		headers_sent($headersSentFile, $headersSentLine);

		$response->appendData(
			'<html>
				<head><title>Error</title></head>

				<body style="font-family:Verdana, sans-serif; background-color:#ddd;">
					<div style="position:relative; width:960px; margin:auto; background-color:white; border:1px solid black; border-radius:10px; padding:0 1em;">
						<h1 style="text-align:center; margin-bottom:2em;">Unhandled exception</h1>

						<h3>Message</h3>
						<p>'.nl2br(htmlentities($e->getMessage())).'</p>

						<h3>Infos</h3>
						<p>File: '.nl2br(htmlentities($e->getFile())).'<br />
						Line: '.nl2br(htmlentities($e->getLine())).'<br />
						Code: '.nl2br(htmlentities($e->getCode())).'<br />
						Headers sent: '.nl2br(htmlentities($headersSentFile.':'.$headersSentLine)).'</p>

						<h3>Stack</h3>
						<p>'.nl2br(htmlentities($e->getTraceAsString())).'</p>
					</div>
				</body>
			</html>');

		exit(1);
	}
	

	private $scope;
	private $printErrors = false;
	private $showRoutesOn404 = false;
	private $routes = [];
	private $serviceProviders = [];
	private $globalBefores = [];
	private $currentResponsesStack = [];		// at every call to handle(), the response is pushed on top of this stack, and removed when the handle() is finished
};

?>