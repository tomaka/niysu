<?php
namespace Niysu\Services;

class TwigService {
	public function __construct(HTTPResponseInterface &$response, \Twig_Environment $twig) {
		$this->response = &$response;
		$this->twig = $twig;
	}

	public function output($template, $variables = []) {
		$template = $this->twig->loadTemplate($template);
		$output = $template->render($variables);
		
		//$this->response->setHeader('Content-Type', 'text/html; charset=utf8');
		$this->response->appendData($output);
	}

	private $twig;
	private $response = null;
};

?>