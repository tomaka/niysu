<?php
namespace Niysu\Services;

interface InputServiceInterface {
	public static function validateInput();
	
	public function isValidContentType($request = null);
	public function isValid($request = null);
	public function getData($request = null);
}

?>