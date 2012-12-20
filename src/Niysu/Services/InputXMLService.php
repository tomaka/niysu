<?php
namespace Niysu\Services;

class InputXMLService {
	public static function validateXMLInput() {
		return function($inputXMLService) {
			return $inputXMLService->isXMLData();
		};
	}
	
	public function __construct($request) {
		$this->request = $request;
	}
	
	/// \brief Returns true if the data is in XML according to the Content-Type of the request
	public function isXMLContentType($request = null) {
		$request = $this->getRequest($request);
		$ctntType = $request->getContentTypeHeader();

		if (substr($ctntType, 0, 8) == 'text/xml' || substr($ctntType, 0, 15) == 'application/xml')
			return true;
		if (preg_match('/^(\w+)\\/.+?\\+xml$/i', $ctntType))
			return true;
		return false;
	}
	
	/// \brief Returns true if the data is in XML according to the Content-Type of the request
	public function isXMLData($request = null) {
		$request = $this->getRequest($request);

		if (!$this->isXMLContentType($request))
			return false;
		
		try {
			$this->getXMLData($request);
			return true;
		} catch(\Exception $e) {
			return false;
		}
	}
	
	public function getXMLData($request = null) {
		$request = $this->getRequest($request);

		return new \SimpleXMLElement($request->getRawData());
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