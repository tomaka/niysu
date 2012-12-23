<?php
namespace Niysu;

/**
 * A route is a path that the server can follow in order to answer a request.
 *
 * It is composed of a handler, and before functions. Both are callable objects.
 * The handler is responsible of building the resource's content, and before functions have various roles: filtering, configuring services, etc.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class Route {
	public function __construct($url, $method = '.*', $callback = null) {
		$this->urlPattern = new URLPattern($url);
		$this->method = $method;
		if ($callback)
			$this->handler($callback);
	}

	/**
	 * Returns the name of the route.
	 * 
	 * Returns null if the name has not been set.
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the name of the route.
	 * 
	 * @param string 	$name 	Name of the route
	 */
	public function name($name) {
		$this->name = $name;
	}

	/**
	 * Tries to handle an HTTP request through this route.
	 *
	 * Returns true if the request was handled, false if the route didn't match this request.
	 * Note that for example answering a 500 error, or stopping the route because $stopRoute was set to true (see below), still counts as a successful handling.
	 *
	 * This function will call all before functions, all onlyIf functions, and all validate functions in the order where they have been defined.
	 * These functions are called using a normal child of the scope given as parameter.
	 *
	 * This scope will also contain two variables named "stopRoute" and "isRightResource".
	 * If one of the before/onlyIf/validate handlers sets the "stopRoute" variable to true, then the route is stopped and handling is considered successful. For example, the resource was loaded from cache, so there is no need to go further in this route.
	 * If one of the before/onlyIf/validate handlers sets the "isRightResource" variable to false, then the route is stopped and handling is considered failed. For example, a route which is supposed to display a user but the user doesn't exist.
	 *
	 * The scope accessible to before functions and handler is the one passed as parameter, plus:
	 *  - the "stopRoute" and "isRightResource" variables
	 *  - the "request" and "reponse" variables
	 *
	 * @param Scope 	$scope 		Scope that will contain the variables accessible to the route
	 * @param string 	$prefix 	(optional) A prefix to append to the Route's URL
	 * @return boolean
	 */
	public function handle(HTTPRequestInterface &$request, HTTPResponseInterface &$response, Scope $scope = null, $prefix = '') {
		return $this->doHandle($request, $response, $scope, $prefix, false);
	}

	/**
	 * Same as handle() but continues even if wrong URL or method.
	 */
	public function handleNoURLCheck(HTTPRequestInterface &$request, HTTPResponseInterface &$response, Scope $scope = null, $prefix = '') {
		return $this->doHandle($request, $response, $scope, $prefix, true);
	}

	/**
	 * Changes the regex pattern that the method must match.
	 *
	 * The method() function returns $this.
	 *
	 * @param string 	$method 		Regular expression (without / /)
	 * @return Route
	 */
	public function method($method) {
		$this->method = $method;
		return $this;
	}

	/**
	 * Changes the regex pattern that a route parameter must match.
	 *
	 * The default pattern is '\w+'. This function allows you to change it.
	 *
	 * The pattern() function returns $this.
	 *
	 * @param string 	$varName 		Name of the route parameter
	 * @param string 	$regex 			Regular expression (without / /)
	 * @return Route
	 */
	public function pattern($varName, $regex) {
		$this->urlPattern->pattern($varName, $regex);
		return $this;
	}

	/**
	 * Adds a function to be called before the handler and which can stop the route.
	 *
	 * If the $callable returns false, then the route is stopped and handling is successful.
	 * Furthermore, the response will receive a status code determined in this function.
	 *
	 * The validate() function returns $this.
	 *
	 * @param callable 	$callable 		Callable by Scope::call
	 * @param integer 	$statusCode 	Status code to set if $callable returns false
	 * @return Route
	 */
	public function validate($callable, $statusCode = 500) {
		$this->before(function($scope, &$callHandler, $response) use ($callable, $statusCode) {
			$ret = $scope->call($callable);
			if (!$ret) {
				$response->setStatusCode($statusCode);
				$callHandler = false;
			}
		});
		return $this;
	}

	/**
	 * Adds a function to be called before the handler and which determines whether handling will be successful.
	 *
	 * If the $callable returns false, then the route is stopped and handling is unsuccessful.
	 *
	 * The onlyIf() function returns $this.
	 *
	 * @param callable 	$callable 	Callable by Scope::call
	 * @return Route
	 */
	public function onlyIf($callable) {
		$this->before(function($scope, &$isRightResource, $user) use ($callable) {
			$ret = $scope->call($callable);
			if (!$ret)	$isRightResource = false;
		});
		return $this;
	}

	/**
	 * Adds a filter to either an request or a response.
	 *
	 * If the class is a derivate of HTTPRequestInterface, then it will replace the request.
	 * If the class is a derivate of HTTPResponseInterface, then it will replace the response.
	 *
	 * The filter() function returns $this.
	 *
	 * @param string 	$className 		Name of a class derivate of HTTPRequestInterface or HTTPResponseInterface
	 * @return Route
	 */
	public function filter($className) {
			throw new \LogicException('Not yet implemented');
		/*if (is_a($className, 'Niysu\\HTTPRequestInterface')) {
			throw new \LogicException('Not yet implemented');

		} else if (is_a($className, 'Niysu\\HTTPResponseInterface')) {
			$this->before(function(&$response) use ($className) {
				$ret = $scope->call($callable);
				if (!$ret) {
					$response->setStatusCode($statusCode);
					$callHandler = false;
				}
			});

		}*/

		return $this;
	}

	/**
	 * Adds a function to be called before the handler.
	 *
	 * Returns $this.
	 *
	 * @param callable 	$handler 	Callable by Scope::call
	 * @return Route
	 */
	public function before($callable) {
		// inserting into $this->before
		$this->before[] = $callable;
		return $this;
	}

	/**
	 * Sets the handling function who is in charge of building the resource.
	 *
	 * Returns $this.
	 *
	 * @param callable 	$handler 	Callable by Scope::call
	 * @return Route
	 */
	public function handler($handler) {
		$this->callback = $handler;
		return $this;
	}

	/**
	 * Adds a function to be called after the handler.
	 *
	 * Returns $this.
	 *
	 * @param callable 	$handler 	Callable by Scope::call
	 * @return Route
	 */
	public function after($callable) {
		// inserting into $this->before
		$this->after[] = $callable;
		return $this;
	}

	/**
	 * Returns the regular expression to match with an URL.
	 *
	 * Includes / and / around the regex.
	 *
	 * @return string
	 */
	public function getURLRegex() {
		return $this->urlPattern->getURLRegex();
	}

	/**
	 * Returns the original pattern that was passed to the constructor.
	 * @return string
	 */
	public function getOriginalPattern() {
		return $this->urlPattern->getOriginalPattern();
	}

	/**
	 * Returns the URL of the route.
	 *
	 * @param array 	$parameters 	An associative array of parameter => value
	 * @return string
	 * @throws RuntimeException If some parameters are missing in the array
	 * @throws RuntimeException If a parameter does not match the corresponding regex
	 */
	public function getURL($parameters = []) {
		return $this->urlPattern->getURL($parameters);
	}



	private function doHandle(HTTPRequestInterface &$request, HTTPResponseInterface &$response, $scope, $prefix, $noURLCheck) {
		if (!$scope)
			$scope = new Scope();

		// checking method
		if (!$noURLCheck &&!preg_match_all('/'.$this->method.'/i', $request->getMethod()))
			return false;

		// checking prefix
		$url = $request->getURL();
		if (!$noURLCheck &&$prefix && strpos($url, $prefix) !== 0)
			return false;
		$url = substr($url, strlen($prefix));
		if ($url === false)
			$url = '/';
		
		// checking whether the URL matches
		$result = $this->urlPattern->testURL($url);
		if (!$noURLCheck && $result === null)
			return false;
		
		// getting log service
		$logService = $scope->logService;
		if ($logService)
			$logService->debug('URL '.$request->getURL().' matching route '.$this->urlPattern->getOriginalPattern().' with prefix '.$prefix.' ; regex is: '.$this->urlPattern->getURLRegex());

		// checking that the handler was defined
		if (!$this->callback)
			throw new \LogicException('The handler of the route '.$this->originalPattern.' has not been defined');

		// creating the local scope
		$localScope = clone $scope;
		
		// adding parts of the URL inside scope
		if ($result) {
			foreach ($result as $varName => $value)
				$localScope->set($varName, $value);
		}
		
		// adding controlling variables to scope.
		$localScope->set('request', $request);
		$localScope->passByRef('request', true);
		$localScope->set('response', $response);
		$localScope->passByRef('response', true);
		$localScope->set('isWrongResource', false);		// DEPRECATED
		$localScope->set('ignoreHandler', false);		// DEPRECATED
		$localScope->set('isRightResource', true);
		$localScope->set('callHandler', true);
		$localScope->set('stopRoute', false);
		
		// calling befores
		foreach ($this->before as $before) {
			$localScope->call($before);

			// checking controlling variables
			if ($localScope->get('isWrongResource') === true) {		// DEPRECATED
				if ($logService)
					$logService->err('The isWrongResource parameter is deprecated');
				return false;
			}
			if ($localScope->get('ignoreHandler') === true) {			// DEPRECATED
				if ($logService)
					$logService->err('The ignoreHandler parameter is deprecated');
				return true;
			}
			if ($localScope->get('isRightResource') === false) {
				if ($logService)
					$logService->debug('Route ignored by before handler');
				return false;
			}
			if ($localScope->get('callHandler') === false) {
				if ($logService)
					$logService->debug('Route\'s handler won\'t get called because of before handler');
				return true;
			}
			if ($localScope->get('stopRoute') === true) {
				if ($logService)
					$logService->debug('Route\'s handler has been stopped by before handler');
				return true;
			}
		}
		
		// calling the handler
		if ($logService)
			$logService->debug('Calling handler of route '.$this->getOriginalPattern());
		$localScope->call($this->callback);
		
		// calling after
		foreach ($this->after as $filter)
			$localScope->call($filter);

		// pushing back in variables
		$request = $localScope->request;
		$response = $localScope->response;

		return true;
	}


	private $before = [];						// an array of callable
	private $after = [];						// an array of callable
	private $urlPattern;						// contains an instance of URLPattern
	private $callback = null;					// the main function that handles the resource
	private $method = null;
	private $name = null;
};

?>