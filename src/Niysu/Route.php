<?php
namespace Niysu;

class Route {
	public function __construct($url, $method, $callback = null) {
		$this->setURLPattern($url);
		$this->method = strtoupper($method);
		if ($callback)
			$this->handler($callback);
	}

	public function name($name) {
	}

	/// \brief Tries to handle an HTTP request
	/// \ret True if the request was handled, false if it was not
	/// \param $scope Scope that will contain the variables accessible to the handler and before functions ; the "request" and "response" elements must be defined
	public function handle(Scope $scope) {
		// some routine checks
		if (!$scope->get('request'))	throw new \LogicException('The "request" variable in the scope must be defined');
		if (!$scope->get('response'))	throw new \LogicException('The "response" variable in the scope must be defined');

		$request = $scope->get('request');
		if ($request->getMethod() != $this->method)
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

	/// \brief Changes the regex pattern that will match the given variable
	/// \note The default pattern is '\w+'
	public function pattern($varName, $regex) {
		foreach ($this->patternRegexMatches as $pos => $match) {
			if ($match != $varName)
				continue;
			$this->patternRegex[$pos*2] = '('.$regex.')';
		}

		return $this;
	}

	/// \brief If $callable doesn't return true, the output returns a $statusCode status code answer
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

	/// \brief Adds a callback which returns true or false whether the request must be handled
	public function onlyIf($callable) {
		$this->before(function($scope, &$isRightResource, $user) use ($callable) {
			$ret = $scope->call($callable);
			if (!$ret)	$isRightResource = false;
		});
		return $this;
	}

	/// \brief Adds a function to call before the handle is called
	public function before($callable) {
		// inserting into $this->before
		$this->before[] = $callable;
		return $this;
	}

	/// \brief Changes the callback
	public function handler($handler) {
		if (!is_callable($handler))
			throw new \LogicException('Handler for Route must be callable');
		$this->callback = $handler;
	}

	/// \brief Adds a function to call after the handle is called
	public function after($callable) {
		// inserting into $this->before
		$this->after[] = $callable;
		return $this;
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
};

?>