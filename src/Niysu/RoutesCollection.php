<?php
namespace Niysu;

/**
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class RoutesCollection {
	/**
	 * Parses a class and registers all resources defined in it.
	 *
	 * This function will analyse the comments of each method of the class and create the appropriate routes.
	 * The routes are created in a child RoutesCollection that is returned by this function.
	 *
	 * Recognized tokens are:
	 *  - @before PHP string of something to be called before the handler (will be passed to eval)
	 *  - @method Pattern of the method to match
	 *  - @name Name of the route
	 *  - @prefix (class only) Adds a prefix to all URLs of this class
	 *  - @static (class only) Adds a path of static resources ; path is relative to the class location
	 *  - @url Pattern of the URL to match, see register()
	 *  - @uri Alias of @url
	 *
	 * @param string 	$className 		Name of the class to parse
	 * @param string 	$prefix 		(optional) Prefix to add to all URLs (the @prefix token will still be added too)
	 * @return RoutesCollection
	 */
	public function parseClass($className, $prefix = '') {
		$reflectionClass = new \ReflectionClass($className);
		$newCollection = $this->newChild($prefix);

		// analyzing the doccomment of the class
		$classDocComment = self::parseDocComment($reflectionClass->getDocComment());

		// handling @before
		if (isset($classDocComment['before'])) {
			foreach ($classDocComment['before'] as $before)
				;//$newCollection->before($before);
		}

		// handling @prefix
		if (isset($classDocComment['prefix'])) {
			$newCollection->prefix($prefix.$classDocComment['prefix'][0]);
		}

		// handling @static
		if (isset($classDocComment['static'])) {
			foreach ($classDocComment['static'] as $path)
				$newCollection->registerStaticDirectory(dirname($reflectionClass->getFileName()).DIRECTORY_SEPARATOR.$path);
		}

		// looping through each method of the class
		foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodReflection) {
			if (!($comment = $methodReflection->getDocComment()))
				continue;
			$parameters = self::parseDocComment($comment);
			
			// now analyzing parameters
			if (isset($parameters['url'])) {
				if (count($parameters['url']) > 1)
					throw new \LogicException('Multiple URLs not supported');
				
				$route = $newCollection->register($parameters['url'][0]);
				
				$route->handler(function($scope) use ($methodReflection, $reflectionClass) {
					$obj = null;
					if (!$methodReflection->isStatic())
						$obj = $scope->call($reflectionClass);
					$closure = $methodReflection->getClosure($obj);
					return $scope->call($closure);
				});
				
				if (isset($parameters['method']))
					$route->method($parameters['method'][0]);
				if (isset($parameters['name']))
					$route->name($parameters['name'][0]);
				if (isset($parameters['before']))
					foreach($parameters['before'] as $b)
						$route->before($b);
			}
		}

		return $newCollection;
	}

	/**
	 * Creates a new route in this collection.
	 *
	 * @param string 	$url 		The url to register
	 * @param string 	$method 	A regular expression to match the request method with
	 * @param callable 	$callback 	The handler of the route (has access to the scope)
	 * @return Route
	 * @see Route::__construct
	 */
	public function register($url = null, $method = '.*', $callback = null) {
		$registration = new Route($url, $method, $callback);
		$registration->before(function($scope) {
			foreach ($this->globalBefores as $b) {
				$scope->call($b);

				if ($scope->isRightResource === false)
					return;
				if ($scope->callHandler === false)
					return;
				if ($scope->stopRoute === true)
					return;
			}
		});

		$this->routes[] = $registration;
		return $registration;
	}

	/**
	 * Registers all the files of a static directory as resources.
	 *
	 * @param string 	$path 		The absolute path on the server which contains the files
	 * @param string 	$prefix 	A prefix to add to the name of the static files
	 */
	public function registerStaticDirectory($path) {		
		$this
			->register('/{file}', 'get')
			->pattern('file', '([^\\.]{2,}.*|.)')
			->before(function(&$file, &$isRightResource) use ($path) {
				$file = $path.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file);
				if (file_exists($file)) {
					if (is_dir($file))
						$isRightResource = false;
					return;
				}

				$checkDir = dirname($file);
				if (!file_exists($checkDir) || !is_dir($checkDir)) {
					$isRightResource = false;
					return;
				}

				$dirToCheck = dir($checkDir);
				while ($entry = $dirToCheck->read()) {
					$entryLong = $dirToCheck->path.'/'.$entry;
					$pathinfo = pathinfo($entryLong);
					if ($pathinfo['dirname'].DIRECTORY_SEPARATOR.$pathinfo['filename'] == $file) {
						$file = $entryLong;
						return;
					}
				}

				$isRightResource = false;
			})
			->handler(function($file, $response, $elapsedTime) {
				if (!extension_loaded('fileinfo'))
					throw new \LogicException('The "fileinfo" extension must be activated');

				$finfo = finfo_open(FILEINFO_MIME);
				$mime = finfo_file($finfo, $file);
				finfo_close($finfo);
				$pathinfo = pathinfo($file);
				if ($pathinfo['extension'] == 'css')
					$mime = 'text/css';
				if ($pathinfo['extension'] == 'js')
					$mime = 'application/javascript';
				if ($pathinfo['extension'] == 'svg')
					$mime = 'image/svg+xml';
				if (substr($mime, 0, 15) == 'application/xml' && ($pathinfo['extension'] == 'htm' || $pathinfo['extension'] == 'html'))
					$mime = 'application/xhtml+xml';
				$response->setHeader('Content-Type', $mime);
				$response->appendData(file_get_contents($file));
			});
	}

	public function redirect($url, $method, $target, $statusCode = 301) {
		$registration = new Route($url, $method, function($response) use ($target, $statusCode) { $response->setStatusCode($statusCode); $response->setHeader('Location', $target); });
		$this->routes[] = $registration;
		return $registration;
	}

	public function __construct($prefix = '') {
		$this->prefix($prefix);
	}

	/**
	 * Sets the prefix to add to all routes in this collection.
	 *
	 * Returns $this.
	 *
	 * @param string 	$prefix 	The prefix
	 * @return RoutesCollection
	 */
	public function prefix($prefix) {
		$this->prefix = $prefix;
		return $this;
	}

	/**
	 * Returns the local prefix that was defined on construction or by using prefix().
	 *
	 * @return string
	 */
	public function getLocalPrefix() {
		return $this->prefix;
	}

	/**
	 * Returns the full prefix, ie. including the parent's full prefix.
	 *
	 * @return string
	 */
	public function getFullPrefix() {
		$prefix = $this->prefix;
		if ($this->parent)
			$prefix = $this->parent->getFullPrefix().$prefix;
		return $prefix;
	}

	/**
	 * Adds a before function to all routes in this collection.
	 *
	 * Returns $this;
	 *
	 * @param callable 	$f 			The function
	 * @return RoutesCollection
	 */
	public function before($f) {
		$this->globalBefores[] = $f;
		return $this;
	}

	/**
	 * Returns a list of all routes from this collection.
	 *
	 * @return array
	 */
	public function getRoutesList() {
		$result = $this->routes;
		foreach ($this->children as $c)
			$result = array_merge($result, $c->getRoutesList());
		return $result;
	}

	/**
	 * Builds a new child and returns it.
	 *
	 * The child will have all the before functions from its parent.
	 * It will also inherit of the prefix from its parent.
	 *
	 * @return RoutesCollection
	 */
	public function newChild($prefix = '') {
		$c = new RoutesCollection($prefix);
		$c->before(function($scope) {
			foreach ($this->globalBefores as $b) {
				$scope->call($b);

				if ($scope->isRightResource === false)
					return;
				if ($scope->callHandler === false)
					return;
				if ($scope->stopRoute === true)
					return;
			}
		});
		$this->children[] = $c;
		$c->parent = $this;
		return $c;
	}

	/**
	 * Tries to handle an HTTP request through a route of this collection or its children collections.
	 *
	 * Returns true if the request was handled, false if the route didn't match this request.
	 * See Route::handle for details.
	 *
	 * @param Scope 	$scope 		Scope that will contain the variables accessible to the route
	 * @return boolean
	 */
	public function handle(HTTPRequestInterface &$request, HTTPResponseInterface &$response, Scope $scope = null) {
		$fullPrefix = $this->getFullPrefix();
		if ($fullPrefix && strpos($request->getURL(), $fullPrefix) !== 0)
			return false;

		foreach ($this->routes as $route) {
			if ($route->handle($request, $response, $scope, $fullPrefix))
				return true;
		}

		foreach ($this->children as $child) {
			if ($child->handle($request, $response, $scope))
				return true;
		}

		return false;
	}

	/**
	 * Search for a route with this name in this collection and its children.
	 *
	 * Returns null if no route is found.
	 *
	 * @param string 	$name 		Name of the route to look for
	 * @return Route
	 */
	public function getRouteByName($name) {
		foreach ($this->routes as $route)
			if ($route->getName() == $name)				return $route;

		foreach ($this->children as $child)
			if ($r = $child->getRouteByName($name))		return $r;

		return null;
	}

	/**
	 * Returns the list of all before handlers that have been registered, including the parent's ones.
	 *
	 * @return array
	 */
	public function getBeforeFunctions() {
		return $this->globalBefores;
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

		foreach (preg_split('/\\r\\n/', $docComment, -1, PREG_SPLIT_NO_EMPTY) as $line) {
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


	private $routes = [];					// array of instances of Route
	private $prefix = '';					// prefix to add to all URLs
	private $globalBefores = [];			// array of functions that are automatically added as ->before
	private $parent = null;
	private $children = [];					// 
};

?>