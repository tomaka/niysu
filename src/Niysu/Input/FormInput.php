<?php
namespace Niysu\Input;

/**
 * Allows to check whether the POST data matches the form format of this URL.
 *
 * This filter will try to load a form format from FormValidationService, and then match the input POST variables with the format.
 *
 * This filter goes well with the FormAnalyserResponseFilter.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class FormInput implements \Niysu\InputInterface {
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\Services\FormValidationService $formValidationService) {
		$this->postInput = new \Niysu\Input\POSTInput($request);		

		$format = $formValidationService->loadFormat($this->getURL());
		if ($format) {
			$this->validated = true;
			$this->isValid = $formValidationService->validate((array)$this->postInput->getPOSTData(), $format);
		}
	}

	public function isValid() {
		return $this->isValid && $this->validated;
	}

	/**
	 * Returns true if the filter did find a form format for this URL.
	 * @return boolean
	 */
	public function hasBeenValidated() {
		return $this->validated;
	}


	private $isValid = true;
	private $validated = false;
}
