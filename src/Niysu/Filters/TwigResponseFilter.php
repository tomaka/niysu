<?php
namespace Niysu\Filters;

/**
 * Generates an HTML page using TwigService and sends it to the response.
 *
 * The actual generation is done when flushing the filter, ie. usually after the handler is called.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class TwigResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response, \Niysu\Services\TwigService $twigService) {
		parent::__construct($response);

		$this->twigService = $twigService;
	}

	public function flush() {
		if ($this->template)
			parent::appendData($this->twigService->render($this->template, $this->variables));
		parent::flush();
	}

	/**
	 * Sets the template that will be used when rendering.
	 *
	 * @param string 	$template 		Name of the template
	 */
	public function setTemplate($template) {
		$this->template = $template;
	}

	/**
	 * Sets the array of variables that will be used when rendering.
	 *
	 * @param array 	$variables 		Variables to pass to Twig
	 */
	public function setVariables($variables) {
		$this->variables = $variables;
	}

	public function appendData($data) {
		if (!$this->template)
			parent::appendData($data);
	}

	public function isHeadersListSent() {
		return !$this->template && parent::isHeadersListSent();
	}


	private $template = null;				// if null, then the filter is deactivated
	private $variables = [];
	private $twigService;
}

?>