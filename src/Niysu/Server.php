<?php
namespace Niysu;

require_once __DIR__.'/HTTPRequestInterface.php';
require_once __DIR__.'/HTTPResponseInterface.php';
require_once __DIR__.'/RoutesCollection.php';
require_once __DIR__.'/Scope.php';

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class Server {
	/**
	 * Initializes the server with the given configuration
	 *
	 * @param mixed 	$environment 	Either the name of a file to load or an array containing the config
	 */
	public function __construct($environment = null) {
		// building the main RoutesCollection
		$this->routesCollection = new RoutesCollection('');

		// building the monolog object
		$this->log = new \Monolog\Logger('NiysuServer');

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
		$this->scope->elapsedTime = function() { $now = microtime(true); return $now - $_SERVER['REQUEST_TIME_FLOAT']; };
		$this->scope->passByRef('elapsedTime', false);
		$this->scope->log = $this->log;
		$this->scope->passByRef('log', false);
		
		// building default services providers
		$this->setServiceProvider('cache', 'Niysu\\Services\\CacheService');
		$this->setServiceProvider('cookies', 'Niysu\\Services\\CookiesService');
		$this->setServiceProvider('database', 'Niysu\\Services\\DatabaseService');
		$this->setServiceProvider('databaseProfiling', 'Niysu\\Services\\DatabaseProfilingService');
		$this->setServiceProvider('email', 'Niysu\\Services\\EmailService');
		$this->setServiceProvider('httpBasicAuth', 'Niysu\\Services\\HTTPBasicAuthService');
		$this->setServiceProvider('inputJSON', 'Niysu\\Services\\InputJSONService');
		$this->setServiceProvider('inputURLEncoded', 'Niysu\\Services\\InputURLEncodedService');
		$this->setServiceProvider('inputXML', 'Niysu\\Services\\InputXMLService');
		$this->setServiceProvider('maintenanceMode', 'Niysu\\Services\\MaintenanceModeService');
		$this->setServiceProvider('outputCSV', 'Niysu\\Services\\OutputCSVService');
		$this->setServiceProvider('outputExcel', 'Niysu\\Services\\OutputExcelService');
		$this->setServiceProvider('outputJSON', 'Niysu\\Services\\OutputJSONService');
		$this->setServiceProvider('outputXML', 'Niysu\\Services\\OutputXMLService');
		$this->setServiceProvider('session', 'Niysu\\Services\\SessionService');
		$this->setServiceProvider('xslt', 'Niysu\\Services\\XSLTService');

		// building filters
		$this->setFilterProvider('cacheResponse', 'Niysu\\Filters\\CacheResponseFilter');
		$this->setFilterProvider('contentEncodingResponse', 'Niysu\\Filters\\ContentEncodingResponseFilter');
		$this->setFilterProvider('csvResponse', 'Niysu\\Filters\\CSVResponseFilter');
		$this->setFilterProvider('debugPanelResponse', 'Niysu\\Filters\\DebugPanelResponseFilter');
		$this->setFilterProvider('errorPagesResponse', 'Niysu\\Filters\\ErrorPagesResponseFilter');
		$this->setFilterProvider('etagResponse', 'Niysu\\Filters\\ETagResponseFilter');
		$this->setFilterProvider('excelResponse', 'Niysu\\Filters\\ExcelResponseFilter');
		$this->setFilterProvider('jsonRequest', 'Niysu\\Filters\\JSONRequestFilter');
		$this->setFilterProvider('jsonResponse', 'Niysu\\Filters\\JSONResponseFilter');
		$this->setFilterProvider('maintenanceModeResponse', 'Niysu\\Filters\\MaintenanceModeResponseFilter');
		$this->setFilterProvider('postRequest', 'Niysu\\Filters\\POSTRequestFilter');
		$this->setFilterProvider('tidyResponse', 'Niysu\\Filters\\TidyResponseFilter');
		$this->setFilterProvider('xmlRequest', 'Niysu\\Filters\\XMLRequestFilter');
		$this->setFilterProvider('xmlResponse', 'Niysu\\Filters\\XMLResponseFilter');

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
	 * Registers a filter.
	 * Overwrites any existing filter with the same name.
	 *
	 * @param string 	$filterName 	The name of the filter to set (without any "Filter" suffix)
	 * @param mixed 	$provider 		A callable accepted by Scope::call which returns an instance of the filter
	 */
	public function setFilterProvider($filterName, $provider) {
		$this->filterProviders[$filterName] = $provider;
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
	public function register($url = null, $method = '.*', $callback = null) {
		return $this->routesCollection->register($url, $method, $callback);
	}

	/**
	 * Registers all the files of a static directory as resources.
	 *
	 * @param string 	$path 		The absolute path on the server which contains the files
	 * @param string 	$prefix 	A prefix to add to the name of the static files
	 * @see RouteCollection::registerStaticDirectory
	 */
	public function registerStaticDirectory($path) {
		return $this->routesCollection->registerStaticDirectory($path);
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
		return $this->routesCollection->redirect($url, $method, $target, $statusCode);
	}

	public function before($callable) {
		$this->routesCollection->before($callable);
	}

	public function buildCollection($prefix = '') {
		return $this->routesCollection->newChild($prefix);
	}

	/**
	 * Handles the request described in $input.
	 *
	 * This function will go through all registered routes. All routes that match the requested URL will be called in the order of their registration.
	 * The server passes a scope containing variables coming from it. See generateQueryScope for more infos.
	 *
	 * If no route is found or if an error is triggered, the server will start a pseudo-route. This will call all the before handles of the server and return a 404 or 500 code.
	 *
	 * @param HTTPRequestInterface 		$input		The request to handle (if null, an instance of HTTPRequestGlobal)
	 * @param HTTPResponseInterface 	$output		The response where to write the output (if null, an instance of HTTPResponseGlobal)
	 */
	public function handle(HTTPRequestInterface $input = null, HTTPResponseInterface $output = null) {
		if (!$input)	$input = new HTTPRequestGlobal();
		if (!$output)	$output = new HTTPResponseGlobal();

		$this->log->debug('Starting handling of resource', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);

		$this->currentResponsesStack[] = $output;

		try {
			if ($this->routesCollection->handle($input, $output, $this->generateQueryScope())) {
				$output->flush();
				$this->log->debug('Successful handling of resource', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);
				if ($nb = gc_collect_cycles())
					$this->log->notice('gc_collect_cycles() returned non-zero value: '.$nb);
				return;
			}

		} catch(Exception $exception) {
			try { $this->log->err($exception->getMessage(), $exception->getTrace()); } catch(\Exception $e) {}
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
		$this->log->debug('Didn\'t find any route for request, going to the 404 route', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);
		$this->followPseudoRoute($input, $output, 404);
		array_pop($this->currentResponsesStack);
	}

	/**
	 * Generates a Scope that contains the variables provided by the server and accessible to a route.
	 *
	 * This scope includes:
	 *  - services, where each service has a "Service" suffix (eg. if you register a service named "log", it is accessed by "$logService")
	 *  - filters, where each filter has a "Filter" suffix (eg. if you register a filter named "jsonInput", it is accessed by "$jsonInputFilter")
	 *  - $elapsedTime, a function that returns the number of seconds between the start of the request and the moment when it was called
	 *  - $log, the monolog logger
	 *  - $server, the server
	 *
	 * @return Scope
	 */
	public function generateQueryScope() {
		$handleScope = $this->scope->newChild();

		foreach($this->serviceProviders as $serviceName => $provider) {
			$handleScope->callback($serviceName.'Service', function(Scope $s) use ($serviceName, $provider/*, $log*/) {
				$this->log->debug('Building service '.$serviceName);
				return $s->call($provider);
			});
		}

		foreach($this->filterProviders as $filterName => $provider) {
			$handleScope->callback($filterName.'Filter', function(Scope $s) use ($filterName, $provider/*, $log*/) {
				$this->log->debug('Building filter '.$filterName);
				$filter = $s->call($provider);

				if (is_a($filter, 'Niysu\HTTPRequestInterface', true)) {
					if (isset($s->request))
						$s->request = $filter;
				}
				if (is_a($filter, 'Niysu\HTTPResponseInterface', true)) {
					if (isset($s->response))
						$s->response = $filter;
				}

				return $filter;
			});
		}

		return $handleScope;
	}

	/**
	 * Returns the list of all registered routes
	 *
	 * Returns an array with instances of the Niysu\Route class.
	 *
	 * @return array
	 */
	public function getRoutesList() {
		return $this->routesCollection->getRoutesList();
	}

	/**
	 * Parses a class and registers all resources defined in it.
	 *
	 * This function will analyse the comments of each method of the class and create the appropriate routes.
	 * The routes are created in a child RoutesCollection that is returned by this function.
	 *
	 * See RoutesCollection::parseClass for details.
	 *
	 * @param string 	$className 		Name of the class to parse
	 * @param string 	$prefix 		(optional) Prefix to add to all URLs of this class
	 * @return RoutesCollection
	 */
	public function parseClass($className, $prefix = '') {
		return $this->routesCollection->parseClass($className, $prefix);
	}

	/**
	 * Search for a route with this name in the main collection and its children.
	 *
	 * Returns null if no route is found.
	 *
	 * @param string 	$name 		Name of the route to look for
	 * @return Route
	 */
	public function getRouteByName($name) {
		return $this->routesCollection->getRouteByName($name);
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

		$this->log->debug('Configuration successfully loaded');

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
						$this->routesCollection->before($v);
				else
					$this->routesCollection->before($value);

			} else if ($key === 'config') {
				if (!is_callable($value))
					throw new \LogicException('The "config" function in environment data must be callable');
				$this->configFunctions[] = $value;

			} else if ($key === 'logConfig') {
				if (!is_callable($value))
					throw new \LogicException('The "log" function in environment data must be callable');
				call_user_func($value, $this->log);

			} else {
				if (is_numeric($key) && is_array($value)) {
					$this->loadEnvironmentData($value);
				} else {
					throw new \LogicException('Unknown environment option: '.$key);
				}
			}
		}
	}

	private function followPseudoRoute($request, $response, $code) {
		$route = new Route('/', '.*', function(HTTPResponseInterface $response) use ($code) {
			$response->setStatusCode($code);
		});

		foreach ($this->routesCollection->getBeforeFunctions() as $r)
			$route->before($r);

		$route->handleNoURLCheck($request, $response, $this->generateQueryScope());
		$response->flush();
	}
	
	private function replaceErrorHandling() {
		// in case of critical fault
		register_shutdown_function(function() {
			$error = error_get_last();
			if ($error != null) {
				try {
					$this->log->crit($error['message'], $error);
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
				try {
					$this->log->notice($errstr, [ 'file' => $errfile, 'line' => $errline, 'content' => $errcontext ]);
				} catch(\Exception $e) {}
				return true;
			}

			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		});
		
		// changing the handler to be called when an exception is not handled
		set_exception_handler(function($exception) {
			try { $this->log->err($exception->getMessage(), $exception->getTrace()); } catch(\Exception $e) {}
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
			$response->removeHeader('ETag');
			$response->removeHeader('Content-Encoding');
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
	private $log;
	private $printErrors = false;
	private $handleErrors = true;
	private $showRoutesOn404 = false;
	private $routesCollection;					// main RoutesCollection
	private $configFunctions = [];				// configuration functions (coming from the environment) to call
	private $serviceProviders = [];
	private $filterProviders = [];
	private $currentResponsesStack = [];		// at every call to handle(), the response is pushed on top of this stack, and removed when the handle() is finished
};

?>