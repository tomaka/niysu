<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class TwigService {
	public function __construct() {
		$this->filesystemLoader = new \Twig_Loader_Filesystem([]);

		$loader = new \Twig_Loader_Chain([
			$this->filesystemLoader,
			new \Twig_Loader_String()
		]);

		$this->twig = new \Twig_Environment($loader, []);
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
		return $template->render($variables);
	}


	private $twig;
	private $filesystemLoader;
};

?>