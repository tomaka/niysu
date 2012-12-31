<?php
namespace Niysu\Filters;

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
class FormValidatorRequestFilter extends \Niysu\HTTPRequestFilterInterface {
	public function __construct(\Niysu\Filters\POSTRequestFilter $request, \Niysu\Services\FormValidationService $formValidationService) {
		parent::__construct($request);

		$format = $formValidationService->loadFormat($this->getURL());
		if ($format) {
			$this->validated = true;
			$this->isValid = $formValidationService->validate((array)$request->getPOSTData(), $format);
		}
	}

	/**
	 * Returns true if the data is valid according to the format, or if the format has not been found.
	 * @return boolean
	 */
	public function isValid() {
		return $this->isValid;
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
