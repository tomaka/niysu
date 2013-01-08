<?php
namespace Niysu;

require_once __DIR__.'/URLPattern.php';

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
	/**
	 * Creates a new route.
	 *
	 * @param mixed 	$url 				The URL to match with the route
	 * @param string 	$method 			Regex that the method must match
	 * @param callable 	$callback 			The handler
	 * @param callable 	$prefixCallback		Function that returns the prefix of the route
	 */
	public function __construct($url = null, $method = '.*', $callback = null, $prefixCallback = null) {
		if (!empty($url)) {
			if (is_string($url))			$this->urlPatterns[] = new URLPattern($url);
			else if (is_array($url))		foreach ($url as $u) $this->urlPatterns[] = new URLPattern($u);
		}

		$this->method = $method;
		if ($callback)
			$this->handler($callback);

		$this->prefixCallback = $prefixCallback;
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
	 * This function returns $this.
	 * 
	 * @param string 	$name 	Name of the route
	 * @return Route
	 */
	public function name($name) {
		$this->name = $name;
		return $this;
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
	 * @return boolean
	 */
	public function handle(HTTPRequestInterface &$request, HTTPResponseInterface &$response, Scope $scope = null) {
		$prefix = '';
		if ($this->prefixCallback)
			$prefix = call_user_func($this->prefixCallback);;
		return $this->doHandle($request, $response, $scope, $prefix, false);
	}

	/**
	 * Same as handle() but continues even if wrong URL or method.
	 */
	public function handleNoURLCheck(HTTPRequestInterface &$request, HTTPResponseInterface &$response, Scope $scope = null) {
		$prefix = '';
		if ($this->prefixCallback)
			$prefix = call_user_func($this->prefixCallback);;
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
	 * @throws LogicException If none of the URLs contain $varName
	 */
	public function pattern($varName, $regex) {
		$anyOk = false;

		foreach ($this->urlPatterns as $p) {
			try {
				$p->pattern($varName, $regex);
				$anyOk = true;
			} catch(\Exception $e) {}
		}

		if (!$anyOk)
			throw new \LogicException('No URL contain the variable '.$varName);

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
	 * Returns the number of URLs registered to this service
	 *
	 * @return integer
	 */
	public function getURLsCount() {
		return count($this->urlPatterns);
	}


	/**
	 * Returns the regular expression to match with an URL.
	 *
	 * Includes / and / around the regex.
	 *
	 * @param integer 	$index 		0-based index of the URL to get
	 * @param boolean 	$includePrefix 		True if the prefix should be prepended
	 * @return string
	 */
	public function getURLRegex($index = 0, $includePrefix = true) {
		$prefix = '';
		if ($this->prefixCallback)
			$prefix = call_user_func($this->prefixCallback);;

		return $this->urlPatterns[$index]->getURLRegex();
	}

	/**
	 * Returns the original pattern of an URL.
	 * 
	 * @param integer 	$index 				0-based index of the URL to get
	 * @param boolean 	$includePrefix 		True if the prefix should be prepended
	 * @return string
	 */
	public function getOriginalPattern($index = 0, $includePrefix = true) {
		$prefix = '';
		if ($includePrefix && $this->prefixCallback)
			$prefix = call_user_func($this->prefixCallback);;

		return $prefix.$this->urlPatterns[$index]->getOriginalPattern();
	}

	/**
	 * Returns the URL of the route.
	 *
	 * @param array 	$parameters 	An associative array of parameter => value
	 * @return string
	 * @param integer 	$index 		0-based index of the URL to get
	 * @throws RuntimeException If some parameters are missing in the array
	 * @throws RuntimeException If a parameter does not match the corresponding regex
	 * @throws RuntimeException If the route has no associated URL pattern
	 */
	public function getURL($parameters = [], $index = 0) {
		$prefix = '';
		if ($this->prefixCallback)
			$prefix = call_user_func($this->prefixCallback);

		if (!isset($this->urlPatterns[$index]))
			throw new \RuntimeException('Route has no associated pattern');

		$val = $prefix.$this->urlPatterns[$index]->getURL($parameters);
		if (strlen($val) >= 2)
			$val = rtrim($val, '/');
		return $val;
	}


	/**
	 * Converts a "onlyIf"-type function to a "before"-type function.
	 *
	 * The onlyIf function is called by Scope::call and should return either true or false.
	 * If it returns false, then the route stops and the server searches for another route.
	 *
	 * This function will convert an onlyIf function to a before function.
	 *
	 * @param mixed 	$onlyIf 	Callable by Scope::call
	 * @return callable
	 */
	public static function convertOnlyIfToBefore($onlyIf) {
		return function(Scope $scope, &$isRightResource) use ($onlyIf) {
			if (!$scope->call($onlyIf))
				$isRightResource = false;
		};
	}

	/**
	 * Converts a "validate"-type function to a "before"-type function.
	 *
	 * The validate function is called by Scope::call and should return either true or false.
	 * If it returns false, then the route stops and the response status code is set to the given status code.
	 *
	 * This function will convert a validate function to a before function.
	 *
	 * @param mixed 	$validate 		Callable by Scope::call
	 * @param integer 	$statusCode 	Status code to return if $validate returns false
	 * @return callable
	 */
	public static function convertValidateToBefore($validate, $statusCode) {
		return function(Scope $scope, &$stopRoute, $response) use ($validate, $statusCode) {
			if (!$scope->call($validate)) {
				$response->setStatusCode($statusCode);
				$stopRoute = true;
			}
		};
	}

	/**
	 * Builds a "before"-type function that will invoke a filter.
	 *
	 * The returned before function will invoke a filter registered towards the server so that it will replace the request or response.
	 *
	 * @param mixed 	$filter 	Name of a filter or a filter class, in both cases must have been registered towards the server
	 * @return callable
	 */
	public static function convertFilterToBefore($filter) {
		return function(Scope $scope) use ($filter) {
			$val = $scope->get($filter.'Filter');
			if (!$val)
				$scope->getByType($filter);
		};
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
		$result = null;
		foreach ($this->urlPatterns as $p) {
			$result = $p->testURL($url);
			if (isset($result))
				break;
		}
		if (!$noURLCheck && !isset($result))
			return false;
		
		// logging
		//$scope->log->debug('URL '.$request->getURL().' matching route '.$this->urlPattern->getOriginalPattern().' with prefix '.$prefix.' ; regex is: '.$this->urlPattern->getURLRegex());

		// checking that the handler was defined
		if (!$this->callback)
			throw new \LogicException('The handler of the route '.$this->originalPattern.' has not been defined');

		// creating the local scope
		$localScope = clone $scope;
		
		// adding parts of the URL inside scope
		if (isset($result)) {
			foreach ($result as $varName => $value)
				$localScope->set($varName, $value);
		}
		
		// adding controlling variables to scope.
		$localScope->set('request', $request);
		$localScope->passByRef('request', true);
		$localScope->set('response', $response);
		$localScope->passByRef('response', true);
		$localScope->set('isRightResource', true);
		$localScope->set('stopRoute', false);
		
		// calling befores
		foreach ($this->before as $before) {
			$localScope->call($before);

			// checking controlling variables
			if ($localScope->get('isRightResource') === false) {
				if (isset($scope->log))
					$scope->log->debug('Route ignored by before function');
				
				// pushing back in variables
				$request = $localScope->request;
				$response = $localScope->response;
				return false;
			}
			if ($localScope->get('stopRoute') === true) {
				if (isset($scope->log))
					$scope->log->debug('Route\'s handler has been stopped by before function');

				// pushing back in variables
				$request = $localScope->request;
				$response = $localScope->response;
				return true;
			}
		}
		
		// calling the handler
		/*$scope->log->debug('Calling handler of route '.$this->getOriginalPattern());*/
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
	private $urlPatterns = [];					// contains an instance of URLPattern
	private $callback = null;					// the main function that handles the resource
	private $method = null;
	private $name = null;
	private $prefixCallback = null;				// callback function that returns the prefix
};

?>