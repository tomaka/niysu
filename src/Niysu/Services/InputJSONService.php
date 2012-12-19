<?php
namespace Niysu\Services;

class InputJSONService {
	public static function validateJSONInput() {
		return function($inputJSONService) {
			return $inputJSONService->isJSONData();
		};
	}
	
	public function __construct($request) {
		$this->request = $request;
	}
	
	public function isJSONData($request = null) {
		if (!$request)
			$request = $this->request;
		if (!$request || !$request instanceof \Niysu\HTTPRequestInterface)
			throw new \LogicException('You need to specify a request');

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

	public function getJSONData($request = null) {
		if (!$request)
			$request = $this->request;
		if (!$request || !$request instanceof \Niysu\HTTPRequestInterface)
			throw new \LogicException('You need to specify a request');

		return json_decode($request->getRawData());
	}

	private $request;
};

?>