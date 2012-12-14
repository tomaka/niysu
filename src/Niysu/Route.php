<?php
namespace Niysu;

class Route {
	public function __construct($url, $method, $callback) {
		$this->setURLPattern($url);
		$this->setCallback($callback);
		$this->method = strtoupper($method);
	}

	/// \ret True if the request was handled
	public function handle(Scope $scope) {
		$request = $scope->getVariable('request');
		if ($request->getMethod() != $this->method)
			return false;
		
		$result = preg_match(implode($this->patternRegex), $request->getURL(), $matches);
		if (!$result)		return false;
		for ($i = 1; $i < count($matches); ++$i) {
			$varName = $this->patternRegexMatches[$i];
			$value = urldecode($matches[$i]);
			$scope->add($varName, $value);
		}
		
		$scope->add('isWrongResource', false);
		$scope->add('ignoreHandler', false);
		$scope->add('isRightResource', true);
		$scope->add('callHandler', true);
		
		// calling "onlyIfCallbacks"
		foreach ($this->onlyIfCallbacks as $onlyIf) {
			if (!$scope->callFunction($onlyIf))
				return false;
		}
		
		// calling befores
		foreach ($this->before as $filter) {
			$scope->callFunction($filter);
			if ($scope->getVariable('isWrongResource') === true)
				return false;
			if ($scope->getVariable('ignoreHandler') === true)
				return true;
			if ($scope->getVariable('isRightResource') === false)
				return false;
			if ($scope->getVariable('callHandler') === false)
				return true;
		}
		
		// calling "validate"
		foreach ($this->validateCallbacks as $vali) {
			if (!$scope->callFunction($vali[0])) {
				$response->setStatusCode($vali[1]);
				return true;
			}
		}
		
		// calling output filter
		if ($this->filterOutput) {
			$cb = $this->filterOutput;
			$response = $cb($response);
		}
		
		// calling the handler
		$scope->callFunction($this->callback);
		
		// calling after
		foreach ($this->after as $filter)
			$scope->callFunction($filter);

		return true;
	}

	/// \brief
	/// \todo Handle if $regex contains ( )
	public function pattern($varName, $regex) {
		foreach ($this->patternRegexMatches as $pos => $match) {
			if ($match != $varName)
				continue;
			$this->patternRegex[$pos*2] = '('.$regex.')';
		}

		return $this;
	}

	/// \brief If $callable doesn't return true, the output contains a 400 Bad Request answer
	public function validate($callable, $statusCode = 500) {
		$this->validateCallbacks[] = [ 0 => $callable, 1 => $statusCode ];
		return $this;
	}

	/// \brief Adds a callback which returns true or false whether the request must be handled
	public function onlyIf($callable) {
		//$this->before(function(&$isWrongResource) use ($callable) { if ($callable()); });
		$this->onlyIfCallbacks[] = $callable;
		return $this;
	}

	/// \brief Adds a function to call before the handle is called
	public function before($callable) {
		// inserting into $this->before
		$this->before[] = $callable;
		return $this;
	}

	/// \brief Adds a function to call after the handle is called
	public function after($callable) {
		// inserting into $this->before
		$this->after[] = $callable;
		return $this;
	}

	/// \brief Adds an output filter
	/// \param $filterClass A string describing a class parent of HTTPResponseFilter
	/// \param ... Other parameters are passed to the constructor after the HTTPResponseInterface
	public function outputFilter($filterClass) {
		if (!class_exists($filterClass))
			throw new LogicException('Unvalid class: '.$filterClass);

		$existingFilter = $this->filterOutput;

		$this->filterOutput = function(HTTPResponseInterface $response) use ($filterClass, $existingFilter) {
			$parameters[0] = $response;
			if ($existingFilter)
				return new $filterClass($existingFilter($response));
			return new $filterClass($response);
		};

		return $this;
	}



	private function setURLPattern($pattern) {
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

	private function setCallback($callback) {
		$this->callback = $callback;
	}

	private $onlyIfCallbacks = [];				// array of callable
	private $validateCallbacks = [];
	private $before = [];						// an array of callable
	private $after = [];						// an array of callable
	private $filterOutput = null;				// filter around the HTTPResponseInterface
	private $patternRegex = [];
	private $patternRegexMatches = [];			// for each match offset, contains the variable name
	private $callback = null;					// the main function that handles the resource
	private $method = null;
};

?>