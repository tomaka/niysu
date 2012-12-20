<?php
namespace Niysu;

class Server {
	/**
	 * Initializes the server with the given configuration
	 *
	 * @param mixed 	$environment 	Either the name of a file to load or an array containing the config
	 */
	public function __construct($environment = null) {
		$constructionTime = microtime(true);

		// loading environment
		if ($environment) {
			try {
				$this->loadEnvironment($environment);
			} catch(\Exception $e) {
				if ($_SERVER['HTTP_HOST'] == 'localhost')
					$this->printError($e);
			}
		}
		
		// building global scope
		$this->scope = new Scope();
		$this->scope->server = $this;
		$this->scope->passByRef('server', false);
		$this->scope->elapsedTime = function() use ($constructionTime) { $now = microtime(true); return round(1000 * ($now - $constructionTime)); };
		$this->scope->passByRef('elapsedTime', false);
		
		// building the main RoutesCollection
		$mainCollection = new RoutesCollection('');
		foreach ($this->globalBefores as $b)
			$mainCollection->before($b);
		$this->routesCollections[] = $mainCollection;
		
		// building default services providers
		$this->setServiceProvider('cacheMe', 'Niysu\\Services\\CacheMeService');
		$this->setServiceProvider('cache', 'Niysu\\Services\\CacheService');
		$this->setServiceProvider('cookies', 'Niysu\\Services\\CookiesService');
		$this->setServiceProvider('database', 'Niysu\\Services\\DatabaseService');
		$this->setServiceProvider('databaseProfiling', 'Niysu\\Services\\DatabaseProfilingService');
		$this->setServiceProvider('debugPanel', 'Niysu\\Services\\DebugPanelService');
		$this->setServiceProvider('email', 'Niysu\\Services\\EmailService');
		$this->setServiceProvider('httpBasicAuth', 'Niysu\\Services\\HTTPBasicAuthService');
		$this->setServiceProvider('inputJSON', 'Niysu\\Services\\InputJSONService');
		$this->setServiceProvider('inputURLEncoded', 'Niysu\\Services\\InputURLEncodedService');
		$this->setServiceProvider('inputXML', 'Niysu\\Services\\InputXMLService');
		$this->setServiceProvider('log', new Services\LogServiceProvider());
		$this->setServiceProvider('maintenanceMode', 'Niysu\\Services\\MaintenanceModeService');
		$this->setServiceProvider('outputJSON', 'Niysu\\Services\\OutputJSONService');
		$this->setServiceProvider('outputXML', 'Niysu\\Services\\OutputXMLService');
		$this->setServiceProvider('session', 'Niysu\\Services\\SessionService');
		$this->setServiceProvider('xslt', 'Niysu\\Services\\XSLTService');

		// facultative service providers
		$this->setServiceProvider('twig', function($scope) {
			if (!class_exists('Twig_Environment'))
				throw new \LogicException('Can only use $twigService if twig is installed');
			return $scope->call('Niysu\\Services\\TwigService');
		});
		
		
		// calling configuration functions
		foreach ($this->configFunctions as $f) {
			// building scope for configuration
			$configScope = $this->scope->newChild();
			foreach ($this->serviceProviders as $serviceName => $provider)
				$configScope->set($serviceName.'Provider', $provider);
			$configScope->call($f);
		}
	}

	/**
	 * Returns the object previously registered by setServiceProvider
	 *
	 * @param string 	$serviceName 	The name of the service previously registered
	 * @return mixed
	 * @throws LogicException If no service of this name has been registered
	 */
	public function getServiceProvider($serviceName) {
		if (!$this->serviceProviders[$serviceName])
			throw new \LogicException('Service "'.$serviceName.'" doesn\'t exist');
		return $this->serviceProviders[$serviceName];
	}

	/**
	 * Registers a service.
	 * Overwrites any existing service with the same name.
	 *
	 * @param string 	$serviceName 	The name of the service to set (without any "Service" suffix)
	 * @param mixed 	$provider 		A callable accepted by Scope::call which returns an instance of the service
	 */
	public function setServiceProvider($serviceName, $provider) {
		$this->serviceProviders[$serviceName] = $provider;
	}
	
	/**
	 * Builds a new instance of a service.
	 * A new instance will always be created, even if an instance exists somewhere.
	 *
	 * @param string 	$serviceName 	The name of the service to get (without any "Service" suffix)
	 * @return mixed
	 */
	public function getService($serviceName) {
		if (!isset($this->serviceProviders[$serviceName]))
			throw new \LogicException('Service "'.$serviceName.'" doesn\'t exist');

		$localScope = $this->scope->newChild();
		foreach($this->serviceProviders as $sNameIter => $provider) {
			$localScope->callback($sNameIter.'Service', function(Scope $s) use ($provider) {
				return $s->call($provider);
			});
		}

		$val = $this->serviceProviders[$serviceName];
		return $localScope->call($val);
	}

	/**
	 * Creates a new route in the main collection.
	 *
	 * @param string 	$url 		The url to register
	 * @param string 	$method 	A regular expression to match the request method with
	 * @param callable 	$callback 	The handler of the route (has access to the scope)
	 * @return Route
	 * @see Route::__construct
	 */
	public function register($url, $method = '.*', $callback = null) {
		return $this->routesCollections[0]->register($url, $method, $callback);
	}

	/**
	 * Registers all the files of a static directory as resources.
	 *
	 * @param string 	$path 		The absolute path on the server which contains the files
	 * @param string 	$prefix 	A prefix to add to the name of the static files
	 * @see RouteCollection::registerStaticDirectory
	 */
	public function registerStaticDirectory($path, $prefix = '/') {
		return $this->routesCollections[0]->registerStaticDirectory($path, $prefix);
	}

	/**
	 * Registers an URL that will redirect to another when accessed.
	 *
	 * This is different from an alias.
	 *
	 * @param string 	$url 			The pattern of the URL
	 * @param string 	$method 		A regular expression to match the request method with
	 * @param string 	$target 		The destination resource
	 * @param number 	$statusCode 	The status code to send alongside with the 'Location' header
	 * @return Route
	 */
	public function redirect($url, $method, $target, $statusCode = 301) {
		return $this->routesCollections[0]->redirect($url, $method, $target, $statusCode);
	}

	public function before($callable) {
		foreach ($this->routesCollections as $collec)
			$collec->before($callable);
		$this->globalBefores[] = $callable;
	}

	public function buildCollection($prefix) {
		$newCollection = new RoutesCollection($prefix);
		foreach ($this->globalBefores as $b)
			$newCollection->before($b);
		$this->routesCollections[] = $newCollection;
		return $newCollection;
	}

	/**
	 * Handles the request described in $input.
	 *
	 * This function will go through all registered routes. All routes that match the requested URL will be called in the order of their registration.
	 *
	 * @param HTTPRequestInterface 		$input		The request to handle (if null, an instance of HTTPRequestGlobal)
	 * @param HTTPResponseInterface 	$output		The response where to write the output (if null, an instance of HTTPResponseGlobal)
	 */
	public function handle(HTTPRequestInterface $input = null, HTTPResponseInterface $output = null) {
		if (!$input)	$input = new HTTPRequestGlobal();
		if (!$output)	$output = new HTTPResponseGlobal();

		$log = $this->getService('log');
		$log->debug('Starting handling of resource', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);

		$this->currentResponsesStack[] = $output;

		try {
			$handleScope = $this->scope->newChild();
			$handleScope->set('request', $input);
			$handleScope->passByRef('request', true);
			$handleScope->set('response', $output);
			$handleScope->passByRef('response', true);

			foreach($this->serviceProviders as $serviceName => $provider) {
				$handleScope->callback($serviceName.'Service', function(Scope $s) use ($serviceName, $provider, $log) {
					$log->debug('Building service '.$serviceName);
					return $s->call($provider);
				});
			}
			
			foreach ($this->routesCollections as $collection) {
				foreach ($collection->getRoutesList() as $route) {
					$localScope = $handleScope->newChild();
					if (!$route->handle($localScope))
						continue;

					$localScope->response->flush();
					$log->debug('Successful handling of resource', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);
					if ($nb = gc_collect_cycles())
						$log->notice('gc_collect_cycles() returned non-zero value: '.$nb);
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
				$output->flush();
			}
		}

		// handling 404 if we didn't find any handler
		$log->debug('Didn\'t find any route for request, returning 404', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);
		$output->setStatusCode(404);
		array_pop($this->currentResponsesStack);
	}



	/// \brief Loads either a file or an array
	private function loadEnvironment($environment) {
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

		if ($this->handleErrors == true)
			$this->replaceErrorHandling();
	}
	
	private function loadEnvironmentData($enviData) {
		foreach ($enviData as $key => $value) {
			// note: no switch here because in PHP 0 == 'any string'
			if ($key === 'name') {

			} else if ($key === 'handleErrors') {
				$this->handleErrors = $value;

			} else if ($key === 'printErrors') {
				$this->printErrors = ($value == true);

			} else if ($key === 'showRoutesOn404') {
				$this->showRoutesOn404 = ($value == true);

			} else if ($key === 'before') {
				if (is_array($value))
					foreach ($value as $v)
						$this->globalBefores[] = $v;
				else
					$this->globalBefores[] = $value;

			} else if ($key === 'config') {
				if (!is_callable($value))
					throw new \LogicException('The "config" function in environment data must be callable');
				$this->configFunctions[] = $value;

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
						$this->printError(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));

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

			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
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
		//error_reporting(0);
	}

	private function printError(\Exception $e) {
		$response = new HTTPResponseGlobal();

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

		$response->flush();
		exit(1);
	}
	

	private $scope;
	private $printErrors = false;
	private $handleErrors = true;
	private $showRoutesOn404 = false;
	private $routesCollections = [];			// array of instances of RoutesCollection
	private $configFunctions = [];				// configuration functions (coming from the environment) to call
	private $serviceProviders = [];
	private $globalBefores = [];
	private $currentResponsesStack = [];		// at every call to handle(), the response is pushed on top of this stack, and removed when the handle() is finished
};

?>