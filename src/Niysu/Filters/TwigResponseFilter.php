<?php
namespace Niysu\Filters;

/**
 * Automatically sends back an error page if the website is under maintenance.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class TwigResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response, $twigService) {
		parent::__construct($response);

		$this->twigService = $twigService;
	}

	public function flush() {
		if ($this->template)
			parent::appendData($this->twigService->render($this->template, $this->variables));
		parent::flush();
	}

	public function setTemplate($template) {
		$this->template = $template;
	}

	public function setVariables($variables) {
		$this->variables = $variables;
	}

	public function setStatusCode($code) {
		if (!$this->template)
			parent::setStatusCode($code);
	}

	public function setHeader($header, $value) {
		if (!$this->template)
			parent::setHeader($header, $value);
	}

	public function addHeader($header, $value) {
		if (!$this->template)
			parent::addHeader($header, $value);
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