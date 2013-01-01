<?php
namespace Niysu\Filters;

/**
 * Automatically parses the output HTML and saves the forms using the FormValidationService.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class FormAnalyserResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response, \Niysu\Services\FormValidationService $formValidationService) {
		parent::__construct($response);
		$this->formValidationService = $formValidationService;
	}

	public function flush() {
		if ($this->activated) {
			try {
				foreach ($this->formValidationService->generateFormatFromHTML($this->dataBuffer) as $dest => $format) {
					$this->formValidationService->storeFormat($dest, $format);
				}
			} catch(\RuntimeException $e) {}
		}

		parent::flush();
	}

	public function addHeader($header, $value) {
		if (strtolower($header) == 'content-type' && (strpos('text/html', $value) === 0 || strpos('application/xhtml+xml', $value) === 0))
			$this->activated = true;
		parent::addHeader($header, $value);
	}

	public function setHeader($header, $value) {
		if (strtolower($header) == 'content-type' && (strpos('text/html', $value) === 0 || strpos('application/xhtml+xml', $value) === 0))
			$this->activated = true;
		parent::setHeader($header, $value);
	}

	public function appendData($data) {
		$this->dataBuffer .= $data;
		parent::appendData($data);
	}


	private $formValidationService;
	private $activated = false;
	private $dataBuffer = '';
}
