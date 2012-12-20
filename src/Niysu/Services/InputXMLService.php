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
	public function isXMLData($request = null) {
		if (!$request)
			$request = $this->request;
		if (!$request || !$request instanceof \Niysu\HTTPRequestInterface)
			throw new \LogicException('You need to specify a request');

		$ctntType = $request->getContentTypeHeader();
		if (substr($ctntType, 0, 8) == 'text/xml' || substr($ctntType, 0, 15) == 'application/xml')
			return true;
		if (preg_match('/^(application|text)\\/.+?\\+xml$/i', $ctntType))
			return true;
		return false;
	}
	
	public function getXMLData($request = null) {
		if (!$request)
			$request = $this->request;
		if (!$request || !$request instanceof \Niysu\HTTPRequestInterface)
			throw new \LogicException('You need to specify a request');

		return new SimpleXMLElement($request->getRawData());
	}
	
	private $request;
};

?>