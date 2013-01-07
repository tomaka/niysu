<?php
namespace Niysu\Input;

/**
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class POSTInput implements \Niysu\InputInterface {
	public function __construct(\Niysu\HTTPRequestInterface $request) {
		$this->request = $request;
	}

	public function __get($varName) {
		return $this->getPOSTData()->$varName;
	}
	
	public function __isset($varName) {
		return isset($this->getPOSTData()->$varName);
	}
	
	public function isPOSTContentType() {
		$contentType = $this->request->getHeader('Content-Type');
		if (substr($contentType, 0, 33) == 'application/x-www-form-urlencoded')
			return true;
		/*if (substr($this->getContentTypeHeader(), 0, 19) == 'multipart/form-data')
			return true;*/
		return false;
	}

	public function isValid() {
		if (!$this->isPOSTContentType())
			return false;
		
		try {
			$this->getPOSTData();
			return true;

		} catch(\Exception $e) {
			return false;
		}
	}

	public function getPOSTData() {
		$array = [];
		parse_str($this->request->getRawData(), $array);
		return (object)$array;
	}


	private $request;
}
