<?php
namespace Niysu\Output;

/**
 * Generates an HTML page using TwigService and sends it to the response.
 *
 * The actual generation is done when flushing the filter, ie. usually after the handler is called.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class TwigOutput implements \Niysu\OutputInterface {	
	public function __construct(\Niysu\HTTPResponseInterface $response, \Niysu\Services\TwigService $twigService) {
		$this->outputResponse = $response;

		$this->twigService = $twigService;
	}

	public function flush() {
		if (!$this->active)
			return;
		if (!$this->template)
			throw new \LogicException('The Twig template to use has not been set');

		$this->outputResponse->setHeader('Content-Type', 'text/html; charset=utf8');
		$this->outputResponse->appendData($this->twigService->render($this->template, $this->variables));
	}

	/**
	 * Sets the template that will be used when rendering.
	 *
	 * @param string 	$template 		Name of the template
	 */
	public function setTemplate($template) {
		$this->template = $template;
		$this->active = true;
	}

	/**
	 * Sets the array of variables that will be used when rendering.
	 *
	 * @param array 	$variables 		Variables to pass to Twig
	 */
	public function setVariables($variables) {
		$this->variables = $variables;
		$this->active = true;
	}


	private $outputResponse;
	private $active = false;
	private $template = null;
	private $variables = [];
	private $twigService;
}
