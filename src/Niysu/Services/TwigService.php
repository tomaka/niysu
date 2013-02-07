<?php
namespace Niysu\Services;

/**
 * Service which allows easy usage of Twig with Niysu.
 *
 * The service automatically creates the following:
 *  - the path() function, alias of url()
 *  - the url(route, { params => value, ... }) function which calls $server->getRouteByName(route)->getURL([ params => value, ... ])
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class TwigService {
	public function __construct(\Niysu\Server $server, \Monolog\Logger $log = null) {
		// creating the loader
		$this->filesystemLoader = new \Twig_Loader_Filesystem([]);
		$loader = new \Twig_Loader_Chain([
			$this->filesystemLoader,
			new \Twig_Loader_String()
		]);

		// creating twig
		$this->twig = new \Twig_Environment($loader, [ ]);

		// the "path" function
	 	// TODO: log when something wrong happens
	 	$pathFunction = function($name, $params = []) use ($server, $log) {
			$route = $server->getRouteByName($name);
			if (!$route) {
				$log->err('Unable to find route named '.$name.' in Twig template');
				return '';
			}

			if (!isset($params) || !is_array($params))
				$params = [];

			try {
				return $route->getURL($params);

			} catch(\Exception $e) {
				$log->err('Unable to build route URL for '.$name.' in Twig template', [ 'params' => $params ]);
				return '';
			}
		};

		// registering functions
		$this->twig->addFunction(new \Twig_SimpleFunction('path', $pathFunction));
		$this->twig->addFunction(new \Twig_SimpleFunction('url', $pathFunction));
	}

	public function setCachePath($directory) {
		if (!is_dir($directory))
			throw new \LogicException('Cache directory for TwigService doesn\'t exist');
		$this->twig->setCache($directory);
	}

	public function addPath($templateDir, $namespace = null) {
		if ($namespace)		$this->filesystemLoader->addPath($templateDir, $namespace);
		else				$this->filesystemLoader->addPath($templateDir);
	}

	public function prependPath($templateDir, $namespace = null) {
		if ($namespace)		$this->filesystemLoader->prependPath($templateDir, $namespace);
		else				$this->filesystemLoader->prependPath($templateDir);
	}

	public function addGlobal($variable, $value) {
		$this->twig->addGlobal($variable, $value);
	}

	public function render($template, $variables = []) {
		$template = $this->twig->loadTemplate($template);
		$result = $template->render($variables);
		return $result;
	}


	private $twig;
	private $filesystemLoader;
};
