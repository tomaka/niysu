<?php
namespace Niysu\Services;

/**
 * Allows to check whether POST data is in the right format.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class FormValidationService {
	/**
	 * Validates the input data.
	 *
	 * Returns true if the data matches the format.
	 *
	 * InputData is an array with varName=>value, just like $_GET or $_POST.
	 *
	 * Format is an array where keys are varNames, and values are also arrays.
	 * There sub-arrays are a list of attributes that you would put in an HTML5 form.
	 *
	 * @param array 	$inputData 		Array similar to $_GET or $_POST
	 * @param array 	$format 		See above
	 * @return boolean
	 */
	public function validate($inputData, $format) {
		// looping through all values
		foreach ($format as $varName => $localFormat) {
			if (!isset($inputData[$varName])) {
				if (isset($localFormat['required']))
					return false;
				continue;
			}

			$value = $inputData[$varName];

			if (isset($localFormat['maxlength'])) {
				if (strlen($value) > $localFormat['maxlength'])
					return false;
			}

			if (isset($localFormat['min'])) {
				if (intval($value) < intval($localFormat['min']))
					return false;
			}

			if (isset($localFormat['max'])) {
				if (intval($value) > intval($localFormat['max']))
					return false;
			}

			if (isset($localFormat['step'])) {
				if ((intval($value) % intval($localFormat['step'])) != 0)
					return false;
			}

			if (isset($localFormat['pattern'])) {
				if (!preg_match($localFormat['pattern'], $value))
					return false;
			}

			if (isset($localFormat['type'])) {
				switch($localFormat['type']) {
					case 'text':
					case 'search':
					case 'tel':
					case 'password':
						if (strpos("\r", $value) !== false || strpos("\n", $value) !== false)
							return false;
						break;

					case 'url':
						if (!preg_match('/\\w+:\\/\\/.*/', $value))
							return false;
						break;

					case 'week':
						if (!preg_match('/\\d{4,}-W\\d{2}/', $value))
							return false;
						break;

					case 'color':
						if (!preg_match('/\\#[[:xdigit:]]{6}/', $value))
							return false;
						break;

					default:
						// not implemented
				}
			}

		}

		return true;
	}

	/**
	 * Generates a format array from HTML.
	 *
	 * @param string 	$html 		String containing the HTML
	 * @return array
	 */
	public function generateFormatFromHTML($html) {
		$doc = new \DOMDocument();
		$doc->strictErrorChecking = false;
		$doc->loadHTML($html);

		$format = [];

		$xml = \simplexml_import_dom($doc);
		foreach ($xml->xpath('//form') as $form) {
			foreach ($form->xpath('//input') as $input) {
				if (!$input['name'])
					continue;

				$values = [];
				foreach ($input->attributes() as $attr => $val)
					$values[$attr] = $val;
				$format[(string)$input['name']] = $values;
			}
		}

		return $format;
	}
};

?>