<?php
namespace Niysu;

class Scope implements \Serializable {
	public function __get($var) {
		return $this->get($var);
	}

	public function __set($var, $value) {
		return $this->set($var, $value);
	}

	public function __unset($var) {
		unset($this->variables[$var]);
	}
	
	public function __isset($var) {
		return $this->has($var);
	}

	public function has($var) {
		if (isset($this->variables[$var]) && $this->variables[$var] !== null)
			return true;
		if (isset($this->variablesCallback[$var]))
			return true;
		if (!$this->parent)
			return false;
		return $this->parent->has($var);
	}

	public function get($var) {
		if (isset($this->variables[$var]) && $this->variables[$var] !== null)
			return $this->variables[$var];
		if (isset($this->variablesCallback[$var])) {
			$val = call_user_func($this->variablesCallback[$var], $this);
			$this->variables[$var] = $val;
			return $val;
		}
		if (!$this->parent)
			return null;
		return $this->parent->get($var);
	}

	public function &getByRef($var) {
		if (!isset($this->variables[$var]) && isset($this->variablesCallback[$var])) {
			$val = call_user_func($this->variablesCallback[$var], $this);
			$this->variables[$var] = $val;
			return $val;
		}
		if (isset($this->variables[$var]) && $this->variables[$var] !== null) {
			if (!isset($this->variablesPassByRef[$var]) || $this->variablesPassByRef[$var])
				return $this->variables[$var];
			throw new \RuntimeException('Forbidden to pass this variable by reference');
		}
		if (!$this->parent || !$this->parent->has($var)) {
			$this->variables[$var] = null;
			return $this->variables[$var];
		}
		return $this->parent->getByRef($var);
	}
	
	public function getByType($requestedType) {
		foreach ($this->variablesTypes as $varName => $type) {
			if (is_a($type, $requestedType, true))
				return $this->get($varName);
		}
		if (!$this->parent)
			return null;
		return $this->parent->getByType();
	}
	
	public function &getByTypeByRef($requestedType) {
		foreach ($this->variablesTypes as $varName => $type) {
			if (is_a($type, $requestedType, true))
				return $this->getByRef($varName);
		}
		if (!$this->parent)
			throw new \RuntimeException('Variable with this type not found');
		return $this->parent->getByType();
	}
	
	public function set($var, $value, $type = null) {
		if ($type == null && is_object($value))
			$type = get_class($value);

		$this->variables[$var] = $value;
		if ($type)	$this->variablesTypes[$var] = $type;
		else 		unset($this->variablesTypes[$var]);
	}

	/// \brief Registers a callback for the given variable
	/// \details The first time a function requests for this variable, this callback will be called to generate ie
	public function callback($var, $callback, $type = null) {
		if (!is_callable($callback))
			throw new \LogicException('The callback must be callable');
		$this->variablesCallback[$var] = $callback;
		$this->variablesTypes[$var] = $type;
	}

	public function passByRef($var, $byRef = true) {
		$this->variablesPassByRef[$var] = $byRef;
	}
	
	public function call($function) {
		$f = self::parseCallable($function);
		return $f($this);
	}

	public function newChild() {
		$c = new Scope();
		$c->parent = $this;
		return $c;
	}

	public function serialize() {
		return serialize([
			'variables' => $this->variables,
			'variablesCallback' => $this->variablesCallback,
			'variablesTypes' => $this->variablesTypes,
			'variablesPassByRef' => $this->variablesPassByRef,
			'parent' => $this->parent
		]);
	}
	
	public function unserialize($serialized) {
		$data = unserialize($serialized);
		$this->variables = $data['variables'];
		$this->variablesCallback = $data['variablesCallback'];
		$this->variablesTypes = $data['variablesTypes'];
		$this->variablesPassByRef = $data['variablesPassByRef'];
		$this->parent = $data['parent'];
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

	public function __construct($variables = []) {
		$this->set('scope', $this, get_class());
		$this->passByRef('scope', false);
		
		foreach ($variables as $var => $value)
			$this->set($var, $value);
	}

	public function __clone() {
		$this->set('scope', $this, get_class());
	}

	
	
	// returns a closure taking as parameter (Scope $scope)
	// this closure will call the parameter of "parseCallable" and return what the callable returned
	private static function parseCallable($callable) {
		// building the reflection in $reflection
		if (is_string($callable) && ($pos = strpos($callable, '::')) !== false) {
			// static function
			$reflection = new \ReflectionMethod(substr($callable, 0, $pos), substr($callable, $pos + 2));

		} else if (is_string($callable) && function_exists($callable)) {
			// function
			$reflection = new \ReflectionFunction($callable);

		} else if (is_callable($callable) && method_exists($callable, '__invoke')) {
			// closure or callable object
			$reflection = new \ReflectionMethod($callable, '__invoke');

		} else if (is_string($callable) && class_exists($callable)) {
			// handling class constructors
			$classReflec = new \ReflectionClass($callable);
			$callable = function() use ($classReflec) {
				$trace = debug_backtrace();
				return $classReflec->newInstanceArgs($trace[1]['args'][1]);
			};
			$reflection = $classReflec->getConstructor();
			if (!$reflection)	$reflection = new \ReflectionMethod(function() {}, '__invoke');
			
		} else {
			throw new \LogicException('Unvalid callable type in ControllerRegistration: '.$callable);
		}

		// building the closure
		return \Closure::bind(function(Scope $scope) use ($reflection, $callable) {
			// the $parameters array will store parameter values to pass to the callable
			$parameters = [];

			foreach ($reflection->getParameters() as $param) {
				$inputParamType = $param->getClass() ? $param->getClass()->getName() : null;
				$passByRef = isset($scope->variablesPassByRef[$param->getName()]) ? $scope->variablesPassByRef[$param->getName()] : true;

				// trying to write the given parameter
				if ($inputParamType) {
					if ($passByRef)		$parameters[] =& $scope->getByTypeByRef($inputParamType);
					else 				$parameters[] = $scope->getByType($inputParamType);

				} else {
					if ($passByRef)		$parameters[] =& $scope->getByRef($param->getName());
					else 				$parameters[] = $scope->get($param->getName());
				}
			}

			return call_user_func_array($callable, $parameters);

		}, null);
	}

	private $parent = null;
	private $variables = [];
	private $variablesCallback = [];
	private $variablesTypes = [];
	private $variablesPassByRef = [];
};

?>