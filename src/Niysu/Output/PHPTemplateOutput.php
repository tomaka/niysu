<?php
namespace Niysu\Output;

/**
 * Generates an HTML page using PHPTemplateService and sends it to the response.
 *
 * The actual generation is done when flushing the filter, ie. usually after the handler is called.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class PHPTemplateOutput implements \Niysu\OutputInterface {	
	public function __construct(\Niysu\HTTPResponseInterface $response, \Niysu\Scope $scope, \Niysu\Services\PHPTemplateService $phpTemplateService) {
		$this->outputResponse = $response;
		$this->scope = $scope;
		$this->phpTemplateService = $phpTemplateService;
	}

	public function flush() {
		if (!$this->template)
			throw new \LogicException('The PHP template to use has not been set');

		$this->outputResponse->setHeader('Content-Type', $contentType);
		$this->phpTemplateService->render($this->template, $this->scope, function($data) { $this->outputResponse->appendData($data); });
	}

	/**
	 * Sets the content type to use on output.
	 *
	 * @param string $contentType 	MIME type of the output
	 */
	public function setContentType($contentType) {
		$this->contentType = $contentType;
	}

	/**
	 * Sets the template that will be used when rendering.
	 *
	 * @param string 	$template 		Content of the template
	 */
	public function setTemplate($template) {
		$this->template = $template;
	}

	/**
	 * Sets the file to load the template from.
	 *
	 * @param string 	$file 		Path to the file which contains the template
	 */
	public function setTemplateFile($file) {
		if (!file_exists($file))
			throw new \LogicException('Template file doesn\'t exist: '.$file);
		$this->template = file_get_contents($file);
	}


	private $outputResponse;
	private $scope;
	private $template;
	private $contentType = 'text/html; charset=utf8';
	private $phpTemplateService;
}
