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
	public function __construct(\Niysu\Server $server) {
		$this->server = $server;

		$this->filesystemLoader = new \Twig_Loader_Filesystem([]);

		$loader = new \Twig_Loader_Chain([
			$this->filesystemLoader,
			new \Twig_Loader_String()
		]);

		$this->twig = new \Twig_Environment($loader, [ ]);

		$this->twig->addFunction('path', new \Twig_Function_Function(get_class().'::url'));
		$this->twig->addFunction('url', new \Twig_Function_Function(get_class().'::url'));
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

		self::$currentServer = $this->server;
		$result = $template->render($variables);
		self::$currentServer = null;

		return $result;
	}

	/**
	 * @todo Log when something wrong happens
	 */
	public static function url($name, $params = null) {
		$route = self::$currentServer->getRouteByName($name);
		if (!$route)	return '';

		if (!isset($params) || !is_array($params))
			$params = [];

		try {
			return $route->getURL($params);
		} catch(\Exception $e) {
			return '';
		}
	}


	private $twig;
	private $filesystemLoader;
	private $server;

	private static $currentServer = null;
};

?>