<?php
namespace Niysu\Services;

class InputURLEncodedService implements InputServiceInterface {
	public static function validateInput() {
		return function($inputURLEncodedService) {
			return $inputURLEncodedService->isValid();
		};
	}
	
	public function __construct($request) {
		$this->request = $request;
	}

	public function __get($varName) {
		$this->getData()->$varName;
	}
	
	public function __isset($varName) {
		return isset($this->getData()->$varName);
	}
	
	public function isValidContentType($request = null) {
		$request = $this->getRequest($request);
		$contentType = $request->getContentTypeHeader();

		if (substr($contentType, 0, 33) == 'application/x-www-form-urlencoded')
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
		
		$array = [];
		parse_str($request->getRawData(), $array);
		return (object)$array;
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