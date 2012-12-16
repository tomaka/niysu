<?php
namespace Niysu\Services;

class TwigServiceProvider {
	public function __construct() {
		$this->loader = new \Twig_Loader_Filesystem([]);
	}

	public function addPath($templateDir, $namespace = null) {
		if ($namespace)		$this->loader->addPath($templateDir, $namespace);
		else				$this->loader->addPath($templateDir);
		$this->twig = null;
	}

	public function prependPath($templateDir, $namespace = null) {
		if ($namespace)		$this->loader->prependPath($templateDir, $namespace);
		else				$this->loader->prependPath($templateDir);
		$this->twig = null;
	}

	public function addGlobal($variable, $value) {
		$this->globals[$variable] = $value;
		if ($this->twig)
			$this->twig->addGlobal($variable, $value);
	}

	public function __invoke(\Niysu\HTTPResponseInterface &$response) {
		if (!$this->twig) {
			$this->twig = new \Twig_Environment($this->loader);
			foreach ($this->globals as $n => $v)
				$this->twig->addGlobal($n, $v);
		}
		return new TwigService($response, $this->twig);
	}


	private $loader;
	private $twig;
	private $globals = [];
};

?>