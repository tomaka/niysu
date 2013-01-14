<?php
namespace Niysu;

/**
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class RoutesBuilder {
	/**
	 * Parses a class and registers all resources defined in it.
	 *
	 * This function will analyse the comments of each method of the class and create the appropriate routes.
	 * The routes are created in a child RoutesCollection that is returned by this function.
	 *
	 * Recognized tokens are:
	 *  - @before (both) See description below
	 *  - @disabled (route only) This route is not created
	 *  - @method (route only) Pattern of the method to match
	 *  - @name (route only) Name of the route
	 *  - @pattern (route only) Sets the regex pattern of the part of a URL
	 *  - @prefix (class only) Sets the prefix of the RoutesCollection ; can be overwritten by calling RoutesCollection->prefix()
	 *  - @static (class only) Adds a path of static resources ; path is relative to the class location
	 *  - @url (route only) Pattern of the URL to match, see register()
	 *  - @uri (route only) Alias of @url
	 *
	 * The first before function of the new route collection will create an instance of the class and put it in $scope->this.
	 * 
	 * The @before token has several possible syntaxes:
	 *  - @before {global_function} {params}
	 *		where {global_function} is the name of a global function to be called before the handler, and {params} is a JSON array
	 *  - @before {method} {params}
	 *		where {method} is the name of a method of the current class to be called before the handler, and {params} is a JSON array
	 *  - @before {class}
	 *		where {class} is a class name
	 *		the before function will simply invoke the given class and do nothing
	 *  - @before {class}::{method} {params}
	 *		where {class} is a class name, {method} is the name of a method of the class, and {params} is a JSON array
	 *		the before function will try to find an object whose type is the class, and call the method on it
	 *  - @before onlyif {anything}
	 *		where {anything} can be any of the other syntaxes above, and {code} is a status code
	 *		if the "anything part" returns false, then the route is considered not to match and another route will be tried (isRightResource is set to false)
	 *  - @before validate {code} {anything}
	 *		where {anything} can be any of the other syntaxes above, and {code} is a status code
	 *		if the "anything part" returns false, then the route is stopped and the status code is set to the response (stopRoute is set to true)
	 *  - @before ${varName} = {anything}
	 *		where {anything} can be any of the other syntaxes above, and {varName} is a status code
	 *		the variable $varName of the scope will be set to the return value of the "anything part"
	 *
	 * @param ReflectionClass 	$reflectionClass 		The class to parse
	 * @param RoutesCollection 	$parent 				The parent of the collection that will be created
	 * @return RoutesCollection
	 */
	public function parseClass(\ReflectionClass $reflectionClass, RoutesCollection $parent) {
		// building the new collection
		$newCollection = $parent->newChild();
		$newCollection->before(function(Scope $scope) use ($reflectionClass) { $scope->this = $scope->call($reflectionClass); });

		// analyzing the doccomment of the class
		$classDocComment = self::parseDocComment($reflectionClass->getDocComment());

		// handling @before
		if (isset($classDocComment['before'])) {
			foreach ($classDocComment['before'] as $before)
				$newCollection->before(self::buildBeforeFunction($reflectionClass, $before));
		}

		// handling @prefix
		if (isset($classDocComment['prefix'])) {
			$newCollection->prefix(implode($classDocComment['prefix']));
		}

		// handling @static
		if (isset($classDocComment['static'])) {
			foreach ($classDocComment['static'] as $path)
				$newCollection->registerStaticDirectory(dirname($reflectionClass->getFileName()).DIRECTORY_SEPARATOR.$path)->name($reflectionClass->getName());
		}

		// looping through each method of the class
		foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
			if (!($comment = $methodReflection->getDocComment()))
				continue;
			$parameters = self::parseDocComment($comment);
			
			// now analyzing parameters
			if (isset($parameters['url']) || isset($parameters['name'])) {
				if (isset($parameters['disabled']))
					continue;

				$route = $newCollection->register($parameters['url']);

				// setting name of the route
				if (isset($parameters['name'])) {
					if (count($parameters['name']) > 1)
						throw new \LogicException('A route cannot have multiple names');
					$route->name($parameters['name'][0]);

				} else {
					$route->name($reflectionClass->getName().'::'.$methodReflection->getName());
				}
				
				// setting the method
				if (isset($parameters['method']))
					$route->method($parameters['method'][0]);
				
				// setting the pattern of the URL parts
				if (isset($parameters['pattern'])) {
					foreach ($parameters['pattern'] as $p => $value) {
						list($part, $val) = explode(' ', $value, 2);
						$route->pattern($part, $val);
					}
				}
				
				// setting the handler
				$route->handler(function(Scope $scope) use ($methodReflection, $reflectionClass) {
					return $scope->call($methodReflection->getClosure($scope->this));
				});

				// setting the before functions
				if (isset($parameters['before'])) {
					foreach($parameters['before'] as $before)
						$route->before(self::buildBeforeFunction($reflectionClass, $before));
				}

			}
		}

		return $newCollection;
	}



	/**
	 */
	private static function buildBeforeFunction($reflectionClass, $beforeText) {
		$parts = preg_split('/\\s+/', $beforeText);

		// handling the "onlyif" syntax
		if ($parts[0] == 'onlyif') {
			$subFunc = self::buildBeforeFunction($reflectionClass, implode(' ', array_splice($parts, 1)));
			return function(Scope $scope, &$isRightResource) use ($subFunc) {
				$val = $scope->call($subFunc);
				if (!$val)
					$isRightResource = false;
				return $val;
			};
		}

		// handling the "validate" syntax
		if ($parts[0] == 'validate') {
			$code = $parts[1];
			if (!is_numeric($code) || $code < 100 || $code >= 1000)
				throw new \RuntimeException('Invalid status code after "validate"');

			$subFunc = self::buildBeforeFunction($reflectionClass, implode(' ', array_splice($parts, 2)));
			return function(Scope $scope, &$stopRoute, &$response) use ($subFunc, $code) {
				$val = $scope->call($subFunc);
				if (!$val) {
					$response->setStatusCode($code);
					$stopRoute = true;
				}
				return $val;
			};
		}

		// handling the "varName = " syntax
		if (count($parts) >= 2 && $parts[1] == '=' && substr($parts[0], 0, 1) == '$') {
			$varName = substr($parts[0], 1);
			$subFunc = self::buildBeforeFunction($reflectionClass, implode(' ', array_splice($parts, 2)));
			return function(Scope $scope) use ($subFunc, $varName) {
				$val = $scope->call($subFunc);
				$scope->$varName = $val;
				return $val;
			};
		}

		// handling parameters
		$parameters = [];
		if (count($parts) >= 2)
			$parameters = json_decode(implode(' ', array_splice($parts, 1)));

		// now we are sure that parts[0] is a function name
		if ($reflectionClass->hasMethod($parts[0])) {
			// handling method of the class
			$reflectionMethod = $reflectionClass->getMethod($beforeText);
			return function(Scope $scope) use ($reflectionMethod) {
				return $scope->call($reflectionMethod->getClosure($scope->this));
			};

		} else if (function_exists($parts[0])) {
			// handling global function case
			return $beforeText;

		} else if (class_exists($parts[0])) {
			// handling single class case
			$class = $parts[0];
			return function(Scope $scope) use ($class) {
				$obj = $scope->getByType($class);
			};

		} else if (preg_match('/^(\\S+)::(\\w+).*$/', $parts[0], $matches)) {
			// handling class::method syntax
			if (!class_exists($matches[1]))
				throw new \LogicException('Wrong class name in @before parameter: '.$matches[1]);

			return function(Scope $scope) use ($matches, $parameters) {
				$obj = $scope->getByType($matches[1]);
				return call_user_func_array([ $obj, $matches[2] ], $parameters);
			};

		} else {
			return $beforeText;
		}
	}


	/**
	 * Parses a DocComment
	 *
	 * Returns an array where each key is a parameter (without @), and value is an array of all the values for this parameter in the right order.
	 *
	 * @param string 	$docComment 	The docComment string
	 * @return array
	 */
	private static function parseDocComment($docComment) {
		if (!$docComment)
			return [];

		$parameters = [];		// will contain the result

		foreach (preg_split('/[\\r\\n]+/', $docComment, -1, PREG_SPLIT_NO_EMPTY) as $line) {
			if (!preg_match('/\\s*\\*?\\s*@(\\w+)(.*)/', $line, $matches))
				continue;

			list(, $parameter, $value) = $matches;
			$parameter = strtolower($parameter);
			$value = trim($value);

			// aliases go here
			if ($parameter == 'uri')		$parameter = 'url';

			if (isset($parameters[$parameter]))		$parameters[$parameter][] = $value;
			else									$parameters[$parameter] = [ $value ];
		}

		return $parameters;
	}
};

?>