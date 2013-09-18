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
		$serverCreationTime = microtime(true);

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
		$this->scope->elapsedTime = function() use ($serverCreationTime) { $now = microtime(true); return $now - $serverCreationTime; };
		$this->scope->passByRef('elapsedTime', false);
		$this->scope->log = $this->log;
		$this->scope->passByRef('log', false);
		
		// building default services providers
		$this->setProvider('authService', 'Niysu\\Services\\AuthService');
		$this->setProvider('cacheService', 'Niysu\\Services\\CacheService');
		$this->setProvider('databaseService', 'Niysu\\Services\\DatabaseService');
		$this->setProvider('databaseProfilingService', 'Niysu\\Services\\DatabaseProfilingService');
		$this->setProvider('emailService', 'Niysu\\Services\\EmailService');
		$this->setProvider('formValidationService', 'Niysu\\Services\\FormValidationService');
		$this->setProvider('maintenanceModeService', 'Niysu\\Services\\MaintenanceModeService');
		$this->setProvider('phpTemplateService', 'Niysu\\Services\\PHPTemplateService');
		$this->setProvider('resourcesCacheService', 'Niysu\\Services\\ResourcesCacheService');
		$this->setProvider('sessionService', 'Niysu\\Services\\SessionService');
		$this->setProvider('xsltService', 'Niysu\\Services\\XSLTService');

		// building filters
		$this->setProvider('contentEncodingResponseFilter', 'Niysu\\Filters\\ContentEncodingResponseFilter');
		$this->setProvider('debugPanelResponseFilter', 'Niysu\\Filters\\DebugPanelResponseFilter');
		$this->setProvider('errorPagesResponseFilter', 'Niysu\\Filters\\ErrorPagesResponseFilter');
		$this->setProvider('etagResponseFilter', 'Niysu\\Filters\\ETagResponseFilter');
		$this->setProvider('formAnalyserResponseFilter', 'Niysu\\Filters\\FormAnalyserResponseFilter');
		$this->setProvider('maintenanceModeResponseFilter', 'Niysu\\Filters\\MaintenanceModeResponseFilter');
		$this->setProvider('serverCacheResponseFilter', 'Niysu\\Filters\\ServerCacheResponseFilter');
		$this->setProvider('tidyResponseFilter', 'Niysu\\Filters\\TidyResponseFilter');
		$this->setProvider('wwwAuthenticateResponseFilter', 'Niysu\\Filters\\WWWAuthenticateResponseFilter');

		$this->setProvider('cookiesContext', 'Niysu\\Contexts\\CookiesContext');
		$this->setProvider('httpBasicAuthContext', 'Niysu\\Contexts\\HTTPBasicAuthContext');
		$this->setProvider('sessionContext', 'Niysu\\Contexts\\SessionContext');
		$this->setProvider('sessionAuthContext', 'Niysu\\Contexts\\SessionAuthContext');

		$this->setProvider('formInput', 'Niysu\\Input\\FormInput');
		$this->setProvider('jsonInput', 'Niysu\\Input\\JSONInput');
		$this->setProvider('postInput', 'Niysu\\Input\\POSTInput');
		$this->setProvider('xmlInput', 'Niysu\\Input\\XMLInput');

		// other providers
		$this->setProvider('csvOutput', 'Niysu\\Output\\CSVOutput');
		$this->setProvider('phpExcelOutput', 'Niysu\\Output\\PHPExcelOutput');
		$this->setProvider('jsonOutput', 'Niysu\\Output\\JSONOutput');
		$this->setProvider('plainTextOutput', 'Niysu\\Output\\PlainTextOutput');
		$this->setProvider('phpTemplateOutput', 'Niysu\\Output\\PHPTemplateOutput');
		$this->setProvider('redirectionOutput', 'Niysu\\Output\\RedirectionOutput');
		$this->setProvider('tcpdfOutput', 'Niysu\\Output\\TCPDFOutput');
		$this->setProvider('twigOutput', 'Niysu\\Output\\TwigOutput');
		$this->setProvider('xmlOutput', 'Niysu\\Output\\XMLOutput');

		// facultative service providers
		$this->setProvider('twigService', function($scope) {
			if (!class_exists('Twig_Environment'))
				throw new \LogicException('Can only use $twigService if twig is installed');
			return $scope->call('Niysu\\Services\\TwigService');
		});
		
		
		// calling configuration functions
		foreach ($this->configFunctions as $f) {
			// building scope for configuration
			$configScope = $this->scope->newChild();
			foreach ($this->providers as $name => $provider)
				$configScope->set($name.'Provider', $provider);
			$configScope->call($f);
		}
	}

	/**
	 * Returns the object previously registered by setProvider
	 *
	 * @param string 	$name 	The name of the provider previously registered
	 * @return mixed
	 * @throws LogicException If no provider of this name has been registered
	 */
	public function getProvider($name) {
		if (!$this->providers[$name])
			throw new \LogicException('Provider "'.$name.'" doesn\'t exist');
		return $this->providers[$name];
	}

	/**
	 * Registers a provider.
	 * Overwrites any existing service with the same name.
	 *
	 * A provider is an object that is called when it is being accessed by a route.
	 * If the object returned by the provider is a derivate of HTTPRequestInterface and/or HTTPResponseInterface, then it will replace the current request and/or response when being accessed for the first time.
	 * If the object returned by the provider is a derivate of OutputInterface, then the "output" variable of the scope will be set to this object. If a derivate of OutputInterface has already been accessed, then the route is invalid and an exception will be thrown.
	 *
	 * @param string 	$name 			The name of the service to set (without any "Service" suffix)
	 * @param mixed 	$provider 		A callable accepted by Scope::call which returns an instance of the object
	 */
	public function setProvider($name, $provider) {
		$this->providers[$name] = $provider;
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
	 * @see generateQueryScope
	 */
	public function handle(HTTPRequestInterface $input = null, HTTPResponseInterface $output = null) {
		if (!$input)	$input = new HTTPRequestGlobal();
		if (!$output)	$output = new HTTPResponseGlobal();

		$this->log->debug('Starting handling of resource', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);

		$localScope = $this->generateQueryScope();

		// updating $elapsedTime
		$atResourceHandlingStart = microtime(true);
		$localScope->elapsedTime = function() use ($atResourceHandlingStart) { return microtime(true) - $atResourceHandlingStart; };

		$this->currentResponsesStack[] = $output;

		try {
			if ($this->routesCollection->handle($input, $output, $localScope)) {
				$output->flush();
				$this->log->debug('Successful handling of resource', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);

			} else {
				// handling 404 if we didn't find any handler
				$this->log->debug('Didn\'t find any route for request', [ 'url' => $input->getURL(), 'method' => $input->getMethod() ]);
				$this->followPseudoRoute($input, $output, 404, $localScope);
			}

			// flushing output
			if (isset($localScope->output) && $localScope->output instanceof OutputInterface) {
				$this->log->debug('Flushing the OutputInterface object');
				$localScope->output->flush();

			} else {
				$this->log->debug('No OutputInterface object has been found');
			}

			// flush response
			$this->log->debug('Flushing the updated HTTPResponseInterface (with filters)');
			$output->flush();

			// gc_collect
			if ($nb = gc_collect_cycles())
				$this->log->notice('gc_collect_cycles() returned non-zero value: '.$nb);

		} catch(\Exception $exception) {
			try { $this->log->err($exception->getMessage(), $exception->getTrace()); } catch(\Exception $e) {}

			if ($this->printErrors) {
				$this->printError($exception, $output);

			} else {
				if (!$output->isHeadersListSent())
					$output->setStatusCode(500);
				$output->appendData('A server-side error occured. Please try again later.');
			}

			$output->flush();
		}
	}

	/**
	 * Generates a Scope that contains the variables provided by the server and accessible to a route.
	 *
	 * This scope includes:
	 *  - providers, where each provider is accessible by its name
	 *  - $output, initially null but will be set to the first derivate of OutputInterface returned by any provider
	 *  - $input, initially null but wlil be set to the first derivate of InputInterface whose isValid() function returns true
	 *  - $elapsedTime, a function that returns the number of seconds between the start of the request and the moment when it was called
	 *  - $log, the monolog logger
	 *  - $server, the server
	 *
	 * @return Scope
	 */
	public function generateQueryScope() {
		$handleScope = $this->scope->newChild();

		foreach($this->providers as $name => $provider) {
			$handleScope->callback($name, function(Scope $s) use ($handleScope, $name, $provider) {
				$this->log->debug('Calling provider for '.$name);
				$obj = $s->call($provider);

				if ($obj instanceof HTTPRequestInterface) {
					if (isset($s->request))
						$s->request = $obj;
				}
				if ($obj instanceof HTTPResponseInterface) {
					if (isset($s->response))
						$s->response = $obj;
				}
				if ($obj instanceof InputInterface) {
					if ($obj->isValid())
						$handleScope->input = $obj;
				}
				if ($obj instanceof OutputInterface) {
					if (isset($handleScope->output))
						throw new \RuntimeException('Only one output object can be active at any time');
					$handleScope->output = $obj;
				}

				return $obj;
			}, (is_string($provider) ? $provider : null));
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

	private function followPseudoRoute(&$request, &$response, $code, $scope) {
		$route = new Route('/', '.*', function(HTTPResponseInterface $response) use ($code) {
			$response->setStatusCode($code);
		});

		foreach ($this->routesCollection->getBeforeFunctions() as $r)
			$route->before($r);

		$this->log->debug('Following a pseudo-route that will return a '.$code.' status code');

		$route->handleNoURLCheck($request, $response, $scope);
	}
	
	private function replaceErrorHandling() {
		// in case of critical fault
		register_shutdown_function(function() {
			$error = error_get_last();
			if ($error != null && $error['type'] == E_ERROR) {
				try {
					$this->log->crit($error['message'], $error);
					$response = new HTTPResponseGlobal();
					
					if ($this->printErrors) {
						$this->printError(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']), $response);
						
					} else {
						if (!$response->isHeadersListSent())
							$response->setStatusCode(500);
					}

					$response->flush();

				} catch(\Exception $e) { }
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

			$response = new HTTPResponseGlobal();

			if ($this->printErrors) {
				$this->printError($exception, $response);

			} else {
				if (!$response->isHeadersListSent())
					$response->setStatusCode(500);
			}

			$response->flush();
		});
		
		// finally disabling error reporting
		//error_reporting(0);
	}

	private function printError(\Exception $e, HTTPResponseInterface $output) {
		if (!$output->isHeadersListSent()) {
			$output->setStatusCode(500);
			$output->removeHeader('ETag');
			$output->removeHeader('Content-Encoding');
			$output->setHeader('Content-Type', 'text/html; charset=utf8');
			$output->setHeader('Cache-Control', 'no-cache');
		}

		$headersSentFile = null;
		$headersSentLine = null;
		headers_sent($headersSentFile, $headersSentLine);

		$output->appendData(
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
	}
	

	private $scope;
	private $log;
	private $printErrors = false;
	private $handleErrors = true;
	private $showRoutesOn404 = false;
	private $routesCollection;					// main RoutesCollection
	private $configFunctions = [];				// configuration functions (coming from the environment) to call
	private $providers = [];					// all providers
};
