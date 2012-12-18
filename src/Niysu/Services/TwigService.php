<?php
namespace Niysu\Services;

class TwigService {
	public function __construct(&$response) {
		$this->response =& $response;
		$this->filesystemLoader = new \Twig_Loader_Filesystem([]);
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

	public function output($template, $variables = []) {
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

		$this->twig = new \Twig_Environment($loader);
	}

	private $twig = null;
	private $response = null;
	private $filesystemLoader;
};

?>