<?php
namespace Niysu\Services;

class TwigService {
	public function __construct(&$response) {
		$this->response =& $response;
		$this->filesystemLoader = new \Twig_Loader_Filesystem([]);
	}

	public function setCachePath($directory) {
		if (!is_dir($directory))
			throw new \LogicException('Cache directory for TwigService doesn\'t exist');
		$this->cachePath = $directory;
		$this->twig = null;
	}

	public function addPath($templateDir, $namespace = null) {
		if ($namespace)		$this->filesystemLoader->addPath($templateDir, $namespace);
		else				$this->filesystemLoader->addPath($templateDir);
		$this->twig = null;
	}

	public function prependPath($templateDir, $namespace = null) {
		if ($namespace)		$this->filesystemLoader->prependPath($templateDir, $namespace);
		else				$this->filesystemLoader->prependPath($templateDir);
		$this->twig = null;
	}

	public function addGlobal($variable, $value) {
		$this->globals[$variable] = $value;
		if ($this->twig)
			$this->twig->addGlobal($variable, $value);
	}

	public function render($template, $variables = []) {
		$this->buildTwig();
		$template = $this->twig->loadTemplate($template);
		return $template->render($variables);
	}

	public function output($template, $variables = []) {
		if (!$this->response)
			throw new \LogicException('Response must be set to use the output function');

		$this->buildTwig();
		$template = $this->twig->loadTemplate($template);
		$output = $template->render($variables);
		
		$this->response->setHeader('Content-Type', 'text/html; charset=utf8');
		$this->response->appendData($output);
	}



	private function buildTwig() {
		if ($this->twig)
			return;

		$loader = new \Twig_Loader_Chain([
			$this->filesystemLoader,
			new \Twig_Loader_String()
		]);

		$this->twig = new \Twig_Environment($loader, [
			'cache' => $this->cachePath
		]);

		foreach($this->globals as $k => $v)
			$this->twig->addGlobal($k, $v);
	}

	private $twig = null;
	private $response = null;
	private $filesystemLoader;
	private $globals = [];
	private $cachePath = false;
};

?>