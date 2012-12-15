<?php
namespace Niysu;

class Scope {
	public function getVariable($var) {
		if (!isset($this->variables[$var]))
			throw new \LogicException('Unvalid variable');
		return $this->variables[$var];
	}

	public function add($var, $value, $type = null) {
		if ($type == null && is_object($value))
			$type = get_class($value);

		$this->variables[$var] = $value;
		$this->variablesTypes[$var] = $type;
		return $this;
	}

	/// \brief Registers a callback for the given variable
	/// \details The first time a function requests for this variable, this callback will be called to generate ie
	public function addByCallback($var, $callback, $type = null) {
		if (!is_callable($callback))
			throw new \LogicException('The callback must be callable');
		$this->variablesCallback[$var] = $callback;
		$this->variablesTypes[$var] = $type;
		return $this;
	}

	public function setVariablePassByRef($var, $byRef = true) {
		$this->variablesPassByRef[$var] = $byRef;
		return $this;
	}
	
	public function callFunction($function) {
		$f = self::parseCallable($function);
		return $f($this);
	}

	/// \todo 
	public function __toString() {
		ob_start();
		//var_dump($this->variables);
		foreach ($this->variables as $v => $val)
			echo $v.' => '.gettype($val).PHP_EOL;
		//var_dump($this->variablesCallback);
		var_dump($this->variablesTypes);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	public function __construct() {
		$this->add('scope', $this, get_class());
		$this->setVariablePassByRef('scope', false);
	}

	
	
	private static function buildReflection($callable) {
		if (is_string($callable) && ($pos = strpos($callable, '::')) !== false)
			return new \ReflectionMethod(substr($callable, 0, $pos), substr($callable, $pos + 2));
		if (is_string($callable) && function_exists($callable))
			return new \ReflectionFunction($callable);
		if (method_exists($callable, '__invoke'))
			return new \ReflectionMethod($callable, '__invoke');
		if (is_string($callable) && method_exists('HTTPResponseInterface', $callable))
			return new \ReflectionMethod('HTTPResponseInterface', $callable);
		if (is_string($callable) && method_exists('HTTPRequestInterface', $callable))
			return new \ReflectionMethod('HTTPRequestInterface', $callable);
		
		throw new \LogicException('Unvalid callable type in ControllerRegistration');
	}
	
	// returns a closure taking as parameter (Scope $scope)
	// this closure will call the parameter of "parseCallable" and return what the callable returned
	private static function parseCallable($callable) {
		$inputParamsNames = [];					// for each position, the variable name
		$inputParamsTypes = [];					// for each position, the variable type
		$nbParameters = 0;

		// building the values of the variables above
		$reflection = self::buildReflection($callable);
		$nbParameters = $reflection->getNumberOfParameters();
		foreach ($reflection->getParameters() as $param) {
			$inputParamsNames[$param->getPosition()] = $param->getName();
			$inputParamsTypes[$param->getPosition()] = $param->getClass() ? $param->getClass()->getName() : null;
		}

		// building the closure
		return function(Scope $scope) use ($reflection, $inputParamsNames, $inputParamsTypes, $nbParameters, $callable) {
			$parameters = [];

			for ($i = 0; $i < $nbParameters; ++$i) {
				$inputParamName = $inputParamsNames[$i];
				$inputParamType = $inputParamsTypes[$i];
				$passByRef = isset($scope->variablesPassByRef[$inputParamName]) ? $scope->variablesPassByRef[$inputParamName] : true;
				
				// trying to write the given parameter
				if ($inputParamType) {
					foreach ($scope->variablesTypes as $varName => $type) {
						if ($inputParamType == $type || is_subclass_of($type, $inputParamType)) {
							if (isset($scope->variables[$varName])) {
								if ($passByRef)		$parameters[] =& $scope->variables[$varName];
								else 				$parameters[] = $scope->variables[$varName];
								continue 2;

							} else if (isset($scope->variablesCallback[$varName])) {
								$scope->variables[$varName] = $scope->variablesCallback[$varName]($scope);
								if ($passByRef)		$parameters[] =& $scope->variables[$varName];
								else 				$parameters[] = $scope->variables[$varName];
								continue 2;
							}
						}
					}
				}
				
				if (!isset($scope->variables[$inputParamName]) && isset($scope->variablesCallback[$inputParamName])) {
					$scope->variables[$inputParamName] = $scope->variablesCallback[$inputParamName]($scope);
				}
				
				if (isset($scope->variables[$inputParamName])) {
					if ($passByRef)		$parameters[] =& $scope->variables[$inputParamName];
					else 				$parameters[] = $scope->variables[$inputParamName];
					continue;
				}
				
				if (!$passByRef || $reflection->getParameters()[$i]->canBePassedByValue()) {
					$parameters[] = null;
				} else {
					$scope->variables[$inputParamName] = null;
					$parameters[] =& $scope->variables[$inputParamName];
				}
			}

			return call_user_func_array($callable, $parameters);
		};
	}

	private $variables = [];
	private $variablesCallback = [];
	private $variablesTypes = [];
	private $variablesPassByRef = [];
};

?>