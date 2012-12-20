<?php
namespace Niysu;

/**
 * A route is a path that the server can follow in order to answer a request.
 *
 * It is composed of a handler, and before functions. Both are callable objects.
 * The handler is responsible of building the resource's content, and before functions have various roles: filtering, configuring services, etc.
 */
class Route {
	public function __construct($url, $method = '.*', $callback = null) {
		$this->setURLPattern($url);
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
	 * Note that answering a 500 error, or stopping the route because $stopRoute was set to true (see below), still counts as a successful handling.
	 *
	 * This function will call all before functions, all onlyIf functions, and all validate functions in the order where they have been defined.
	 * These functions are called using a child of the scope given as parameter.
	 *
	 * This scope will also contain two variables named "stopRoute" and "isRightResource".
	 * If one of the before/onlyIf/validate handlers sets the "stopRoute" variable to true, then the route is stopped and handling is considered successful. For example, the resource was loaded from cache, so there is no need to go further in this route.
	 * If one of the before/onlyIf/validate handlers sets the "isRightResource" variable to false, then the route is stopped and handling is considered failed. For example, a route which is supposed to display a user but the user doesn't exist.
	 *
	 * @param Scope 	$scope 		Scope that will contain the variables accessible to the route
	 * @return boolean
	 */
	public function handle(Scope $scope) {
		// some routine checks
		if (!$scope->get('request'))	throw new \LogicException('The "request" variable in the scope must be defined');
		if (!$scope->get('response'))	throw new \LogicException('The "response" variable in the scope must be defined');

		$request = $scope->get('request');
		if (!preg_match_all('/'.$this->method.'/i', $request->getMethod()))
			return false;
		
		// checking whether the URL matches
		$result = preg_match(implode($this->patternRegex), $request->getURL(), $matches);
		if (!$result)	return false;
		
		// getting log service
		$logService = $scope->logService;
		if ($logService)
			$logService->debug('URL '.$request->getURL().' matching route '.$this->originalPattern.' ; regex is: '.implode($this->patternRegex));

		// checking that the handler was defined
		if (!$this->callback)
			throw new \LogicException('The handler of the route '.$this->originalPattern.' has not been defined');
		
		// adding parts of the URL inside scope
		for ($i = 1; $i < count($matches); ++$i) {
			$varName = $this->patternRegexMatches[$i];
			$value = urldecode($matches[$i]);
			$scope->set($varName, $value);
		}
		
		// adding controlling variables to scope
		$scope->set('isWrongResource', false);		// DEPRECATED
		$scope->set('ignoreHandler', false);		// DEPRECATED
		$scope->set('isRightResource', true);
		$scope->set('callHandler', true);
		$scope->set('stopRoute', false);
		
		// calling befores
		foreach ($this->before as $before) {
			$scope->call($before);

			// checking controlling variables
			if ($scope->get('isWrongResource') === true) {		// DEPRECATED
				if ($logService)
					$logService->err('The isWrongResource parameter is deprecated');
				return false;
			}
			if ($scope->get('ignoreHandler') === true) {			// DEPRECATED
				if ($logService)
					$logService->err('The ignoreHandler parameter is deprecated');
				return true;
			}
			if ($scope->get('isRightResource') === false) {
				if ($logService)
					$logService->debug('Route ignored by before handler');
				return false;
			}
			if ($scope->get('callHandler') === false) {
				if ($logService)
					$logService->debug('Route\'s handler won\'t get called because of before handler');
				return true;
			}
			if ($scope->get('stopRoute') === true) {
				if ($logService)
					$logService->debug('Route\'s handler has been stopped by before handler');
				return true;
			}
		}
		
		// calling the handler
		if ($logService)
			$logService->debug('Calling handler of route '.$this->originalPattern);
		$scope->call($this->callback);
		
		// calling after
		foreach ($this->after as $filter)
			$scope->call($filter);

		return true;
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
		foreach ($this->patternRegexMatches as $pos => $match) {
			if ($match != $varName)
				continue;
			$this->patternRegex[$pos*2] = '('.$regex.')';
		}

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
	 * Returns the regular expression to match with an URL.
	 *
	 * Includes / and / around the regex.
	 *
	 * @return string
	 */
	public function getURLRegex() {
		return implode($this->patternRegex);
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
		// cloning the pattern
		$patternRegex = $this->patternRegex;

		foreach ($this->patternRegexMatches as $offset => $varName) {
			if (!isset($parameters[$varName]))
				throw new \RuntimeException('Parameter missing in the array: '.$varName);

			$val = $parameters[$varName];
			if (!preg_match_all('/'.$patternRegex[$offset * 2].'/', $val))
				throw new \RuntimeException('Parameter does not match its regex: '.$varName.' doesn\'t match '.$patternRegex[$offset * 2]);

			$patternRegex[$offset * 2] = $val;
		}
		
		return implode($patternRegex);
	}



	private function setURLPattern($pattern) {
		$this->originalPattern = $pattern;

		$currentOffset = 0;
		$this->patternRegex = ['/^'];
		$this->patternRegexMatches = [];
		while (preg_match('/\{(\w+)\}/', $pattern, $match, PREG_OFFSET_CAPTURE, $currentOffset)) {
			$matchBeginOffset = $match[0][1];
			$varName = $match[1][0];

			$this->patternRegex[] = str_replace('/', '\/', preg_quote(substr($pattern, $currentOffset, $matchBeginOffset - $currentOffset)));
			$this->patternRegex[] = '(\w+)';

			$this->patternRegexMatches[count($this->patternRegexMatches) + 1] = $varName;

			$currentOffset = $matchBeginOffset + strlen($match[0][0]);
		}
		$this->patternRegex[] = str_replace('/', '\/', preg_quote(substr($pattern, $currentOffset)));
		$this->patternRegex[] = '$/';

		//var_dump($pattern.' '.implode($this->patternRegex));
	}

	private $before = [];						// an array of callable
	private $after = [];						// an array of callable
	private $patternRegex = [];
	private $patternRegexMatches = [];			// for each match offset, contains the variable name
	private $callback = null;					// the main function that handles the resource
	private $method = null;
	private $originalPattern = '';				// the original pattern
	private $name = null;
};

?>