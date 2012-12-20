<?php
namespace Niysu\Services;

class InputURLEncodedService {
	public static function validateURLEncodedInput() {
		return function($inputURLEncodedService) {
			return $inputURLEncodedService->isJSONData();
		};
	}
	
	public function __construct($request) {
		$this->request = $request;
	}
	
	public function isURLEncodedContentType($request = null) {
		$request = $this->getRequest($request);
		$contentType = $request->getContentTypeHeader();

		if (substr($contentType, 0, 33) == 'application/x-www-form-urlencoded')
			return true;
		return false;
	}

	public function isURLEncodedData($request = null) {
		$request = $this->getRequest($request);

		if (!$this->isURLEncodedContentType($request))
			return false;
		
		try {
			$this->getURLEncodedData($request);
			return true;
		} catch(\Exception $e) {
			return false;
		}
	}

	public function getURLEncodedData($request = null) {
		$request = $this->getRequest($request);
		
		$array = [];
		parse_str($request->getRawData(), $array);
		return $array;
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