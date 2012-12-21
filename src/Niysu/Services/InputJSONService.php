<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class InputJSONService implements InputServiceInterface {
	public static function validateInput() {
		return function($inputJSONService) {
			return $inputJSONService->isValid();
		};
	}
	
	public function __construct($request) {
		$this->request = $request;
	}

	public function __get($varName) {
		return $this->getData()->$varName;
	}
	
	public function __isset($varName) {
		return isset($this->getData()->$varName);
	}
	
	public function isValidContentType($request = null) {
		$request = $this->getRequest($request);
		$contentType = $request->getContentTypeHeader();

		if (substr($contentType, 0, 16) == 'application/json')
			return true;
		if (substr($contentType, 0, 22) == 'application/javascript')
			return true;
		if (substr($contentType, 0, 15) == 'text/javascript')
			return true;
		if (substr($contentType, 0, 17) == 'text/x-javascript')
			return true;
		if (substr($contentType, 0, 11) == 'text/x-json')
			return true;
		return false;
	}

	public function isValid($request = null) {
		$request = $this->getRequest($request);

		if (!$this->isValidContentType($request))
			return false;
		
		try {
			$this->getData($request);
			return true;
		} catch(\Exception $e) {
			return false;
		}
	}

	public function getData($request = null) {
		$request = $this->getRequest($request);

		$data = json_decode($request->getRawData());
		if (!$data)		throw new \RuntimeException('Unvalid JSON data');
		return $data;
	}



	private function getRequest($request = null) {
		if (!$request)
			$request = $this->request;
		if (!$request || !$request instanceof \Niysu\HTTPRequestInterface)
			throw new \LogicException('You need to specify a request');
		return $request;
	}

	private $request;
};

?>