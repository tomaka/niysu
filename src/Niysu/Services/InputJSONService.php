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
	
	public function isJSONData() {
		$contentType = $this->request->getContentTypeHeader();

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

	public function getJSONData() {
		return json_decode($this->request->getRawData());
	}

	private $request;
};

?>