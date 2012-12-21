<?php
namespace Niysu;

/**
 * Utility class for dependency injection (or whatever this pattern is called).
 *
 * This class acts a bit like an associative array but with more features:
 *  - possibility to a callback that will be called the first time a value is retreived
 *  - inheritance between scopes, where child scopes have access to parent scopes
 *  - possibility to call a function and pass values of the scope to it depending on its parameter name
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class Scope implements \Serializable {
	/**
	 * Alias for get()
	 * @see get
	 * @param string 	$var 	Name of the variable to retreive
	 * @return mixed
	 */
	public function __get($var) {
		return $this->get($var);
	}

	/**
	 * Alias for set()
	 * @see set
	 * @param string 	$var 	Name of the variable to set
	 * @param mixed 	$value 	Value to set
	 */
	public function __set($var, $value) {
		return $this->set($var, $value);
	}

	/**
	 * Alias for set($var, null)
	 * @see set
	 * @param string 	$var 	Name of the variable to destroy
	 */
	public function __unset($var) {
		unset($this->variables[$var]);
	}
	
	/**
	 * Alias for has()
	 * @see has
	 * @param string 	$var 	Name of the variable to check
	 * @return boolean
	 */
	public function __isset($var) {
		return $this->has($var);
	}

	/**
	 * Checks whether a variable exists.
	 *
	 * Variables defined as callback DO exist.
	 *
	 * @param string 	$var 	Name of the variable to check
	 * @return boolean
	 */
	public function has($var) {
		if (isset($this->variables[$var]) && $this->variables[$var] !== null)
			return true;
		if (isset($this->variablesCallback[$var]))
			return true;
		if (!$this->parent)
			return false;
		return $this->parent->has($var);
	}

	/**
	 * Returns the value of a variable.
	 *
	 * If the variable was defined by callback, then the callback is called and its return value returned.
	 * If the variable doesn't exist, null is returned.
	 *
	 * The "scope" name is reserved and will always return the scope itself.
	 *
	 * May throw an exception when variable doesn't exist in a future version.
	 *
	 * @param string 	$var 	Name of the variable to retreive
	 * @return mixed
	 */
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

	/**
	 * Returns a reference to a variable.
	 *
	 * If the variable was defined by callback, then the callback is called and its return value returned.
	 * If the variable doesn't exist, then it is created, set to null, and a reference returned.
	 *
	 * @param string 	$var 	Name of the variable to retreive
	 * @return mixed
	 * @throws RuntimeException If the variable is forbidden to be passed by reference
	 */
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
		if (!$this->parent || (!$this->passNewVarsToParent && !$this->parent->has($var))) {
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
		return $this->parent->getByType($requestedType);
	}
	
	public function &getByTypeByRef($requestedType) {
		foreach ($this->variablesTypes as $varName => $type) {
			if (is_a($type, $requestedType, true))
				return $this->getByRef($varName);
		}
		if (!$this->parent)
			throw new \RuntimeException('Variable with this type not found');
		return $this->parent->getByType($requestedType);
	}
	
	/**
	 * Changes the value of a variable.
	 *
	 * If a variable already exists with this name, it is overwritten.
	 * Settings a variable to null is equivalent to destroying it.
	 *
	 * @param string 	$var 	Name of the variable to set
	 * @param mixed 	$value 	Value to set ; the null value means that the variable will be deleted
	 * @param string 	$type 	(optional) Class name of the variable, or automatically detected from $value
	 * @throws LogicException If trying to set the value of the reserved "scope" variable
	 */
	public function set($var, $value, $type = null) {
		if ($type == null && is_object($value))
			$type = get_class($value);

		if ($var == 'scope')
			throw new \LogicException('The variable name "scope" is reserved');

		$this->variables[$var] = $value;
		if ($type)	$this->variablesTypes[$var] = $type;
		else 		unset($this->variablesTypes[$var]);
	}

	/**
	 * Register a callback to be called when retreiving the value of a variable.
	 *
	 * The first time this variable is retreived, the callback will be called and the value of the variable set to what the callback returned.
	 *
	 * @param string 	$var 		Name of the variable to set
	 * @param callable 	$callback 	Callback to be called ; takes as parameter the Scope object
	 * @param string 	$type 		(optional) Class name of the value returned by the callback
	 */
	public function callback($var, $callback, $type = null) {
		if (!is_callable($callback))
			throw new \LogicException('The callback must be callable');
		$this->set($var, null);
		$this->variablesCallback[$var] = $callback;
		$this->variablesTypes[$var] = $type;
	}

	/**
	 * Sets whether a variable can be accessed by reference.
	 *
	 * If trying to get by reference a variable where it is denied, a RuntimeException will be thrown.
	 * The default value is true. If variable created is allowed to be accessed by reference unless changed using this function.
	 *
	 * @param string 	$var 		Name of the variable to set
	 * @param boolean 	$byRef 		True if the variable is authorized to be passed by reference
	 * @throws LogicException If var is the reserved name "scope"
	 */
	public function passByRef($var, $byRef = true) {
		if ($var == 'scope')
			throw new \LogicException('The "scope" variable name is reserved');

		$this->variablesPassByRef[$var] = $byRef;
	}
	
	/**
	 * Calls the given function.
	 *
	 * The function can be:
	 *  - A string of a function name
	 *  - A closure/anonoymous function
	 *  - A string of a static function in the format Class::staticFunction
	 *  - Any object which defines an __invoke method
	 *  - A string of a class name ; if so a new object will be created, its constructor called with access to the scope, and the new object returned
	 *  - An instance of \ReflectionClass
	 *  - An instance of \ReflectionFunction
	 *
	 * The scope will study the function to call and pass values of the scope to it.
	 * Parameters without any class and without pass-by-reference will receive their value from the scope's get function.
	 * Parameters without any class and with pass-by-reference will receive their value from the scope's getByRef function.
	 * Parameters with a class and without pass-by-reference will receive their value from the scope's getByType function.
	 * Parameters with a class and with pass-by-reference will receive their value from the scope's getByTypeByRef function.
	 *
	 * Returns what the called function returns.
	 *
	 * @param mixed 	$function 	See description
	 * @return mixed
	 * @example $scope->call(function($a) { ... });  is the same as  call_user_func(function($a) { ... }, $scope->a);
	 */
	public function call($function) {
		$f = self::parseCallable($function);
		return $f($this);
	}

	/**
	 * Creates a new scope child of this one.
	 *
	 * - get will try to return the value from the child scope, or the value of its parent if no such variable exists
	 * - getByRef will try to return a reference to the value from the child scope, or a reference to the value from its parent ; if the parent doesn't have this variable then the variable is created in the child scope
	 * - set will modify the child scope only, never the parent
	 *
	 * You can only modify the parent through "getByRef" and "getByRefByType".
	 * 
	 * @return Scope
	 */
	public function newChild() {
		$c = new Scope();
		$c->parent = $this;
		return $c;
	}

	/**
	 * Creates a new scope child of this one.
	 *
	 * This function is the same as "newChild", except that "getByRef" will create a new variable in the parent if no such variable exists.
	 * The "set" function still writes the child scope, or it would be kind of useless.
	 *
	 * This is useful when you want to use "call" with just an additional value. Just create a small child, set the additional value in it, and you won't lose newly created variables from the call.
	 * 
	 * @return Scope
	 */
	public function newSmallChild() {
		$c = new Scope();
		$c->parent = $this;
		$c->passNewVarsToParent = true;
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

	/**
	 * Initializes the scope with variables.
	 *
	 * array 	$variables 		A key => value array with the variables to set as keys and their values as value
	 */
	public function __construct($variables = []) {
		$this->variables['scope'] = $this;
		$this->variablesTypes['scope'] = get_class();
		$this->variablesPassByRef['scope'] = false;
		
		foreach ($variables as $var => $value)
			$this->set($var, $value);
	}

	public function __clone() {
		$this->variables['scope'] = $this;
		$this->variablesTypes['scope'] = get_class();
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

		} else if ($callable instanceof \Closure) {
			// if I use "new \ReflectionMethod($callable, '__invoke')", then a PHP bug shows wrong default parameters
			$reflection = new \ReflectionFunction($callable);

		} else if (is_callable($callable) && method_exists($callable, '__invoke')) {
			// closure or callable object
			$reflection = new \ReflectionMethod($callable, '__invoke');

		} else if (is_string($callable) && class_exists($callable)) {
			// handling class constructors
			$classReflec = new \ReflectionClass($callable);
			$callable = function() use ($classReflec) { $trace = debug_backtrace(); return $classReflec->newInstanceArgs($trace[1]['args'][1]); };
			$reflection = $classReflec->getConstructor();
			if (!$reflection)	$reflection = new \ReflectionMethod(function() {}, '__invoke');

		} else if ($callable instanceof \ReflectionClass) {
			// handling class constructors
			$classReflec = $callable;
			$callable = function() use ($classReflec) { $trace = debug_backtrace(); return $classReflec->newInstanceArgs($trace[1]['args'][1]); };
			$reflection = $classReflec->getConstructor();
			if (!$reflection)	$reflection = new \ReflectionMethod(function() {}, '__invoke');

		} else if ($callable instanceof \ReflectionFunction) {
			// handling class constructors
			$reflection = $callable;
			$callable = function() use ($reflection) { $trace = debug_backtrace(); return $reflection->invokeArgs($trace[1]['args'][1]); };
			
		} else {
			throw new \LogicException('Unvalid callable type: '.(is_string($callable) ? $callable : gettype($callable)));
		}

		// building the closure
		return \Closure::bind(function(Scope $scope) use ($reflection, $callable) {
			// the $parameters array will store parameter values to pass to the callable
			$parameters = [];

			foreach ($reflection->getParameters() as $param) {
				$inputParamType = $param->getClass() ? $param->getClass()->getName() : null;

				// trying to write the given parameter
				if ($inputParamType) {
					if (!$param->canBePassedByValue())		$parameters[] =& $scope->getByTypeByRef($inputParamType);
					else 									$parameters[] = $scope->getByType($inputParamType);

				} else {
					if (!$param->canBePassedByValue())		$parameters[] =& $scope->getByRef($param->getName());
					else {
						$val = $scope->get($param->getName());
						if ($val === null && $param->isDefaultValueAvailable())
							$val = $param->getDefaultValue();
						$parameters[] = $val;
					}
				}
			}

			return call_user_func_array($callable, $parameters);

		}, null);
	}

	private $parent = null;
	private $passNewVarsToParent = false;
	private $variables = [];
	private $variablesCallback = [];
	private $variablesTypes = [];
	private $variablesPassByRef = [];
};

?>