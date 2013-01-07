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
	 *  - @before (both) Name of a function (method or global function) to be called before the handler
	 *  - @disabled (route only) This route is not created
	 *  - @method (route only) Pattern of the method to match
	 *  - @name (route only) Name of the route
	 *  - @pattern (route only) Sets the regex pattern of the part of a URL
	 *  - @prefix (class only) Sets the prefix of the RoutesCollection ; can be overwritten by calling RoutesCollection->prefix()
	 *  - @static (class only) Adds a path of static resources ; path is relative to the class location
	 *  - @url (route only) Pattern of the URL to match, see register()
	 *  - @uri (route only) Alias of @url
	 *
	 * The first "before" function of the new route collection will create an instance of the class and put it in "$scope->this".
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
			foreach ($classDocComment['before'] as $before) {
				if ($reflectionClass->hasMethod($before)) {
					$reflectionMethod = $reflectionClass->getMethod($before);
					$newCollection->before(function(Scope $scope) use ($reflectionMethod) {
						return $scope->call($reflectionMethod->getClosure($scope->this));
					});

				} else {
					$newCollection->before($before);
				}
			}
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
					foreach($parameters['before'] as $before) {
						if ($reflectionClass->hasMethod($before)) {
							$reflectionMethod = $reflectionClass->getMethod($before);
							$route->before(function(Scope $scope) use ($reflectionMethod) {
								return $scope->call($reflectionMethod->getClosure($scope->this));
							});

						} else {
							$route->before($before);
						}
					}
				}

			}
		}

		return $newCollection;
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