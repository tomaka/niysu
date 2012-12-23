<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
interface InputServiceInterface {
	public static function validateInput();
	
	public function isValidContentType($request = null);
	public function isValid($request = null);
	public function getData($request = null);
}

?>