<?php
namespace Niysu;

class InputXMLService {
	public static function validate() {
		return function($inputXMLService) {
			return $inputXMLService->isXMLData();
		}
	}
	
	public function __construct(HTTPRequestInterface $request) {
		$this->request = $request;
	}
	
	/// \brief Returns true if the data is in XML according to the Content-Type of the request
	public function isXMLData() {
		$ctntType = $this->request->getContentTypeHeader();
		if (substr($ctntType, 0, 8) == 'text/xml' || substr($ctntType, 0, 15) == 'application/xml')
			return true;
		if (preg_match('/^(application|text)\\/.+?\\+xml$/i', $ctntType))
			return true;
		return false;
	}
	
	public function getXMLData() {
		return new SimpleXMLElement($this->request->getRawData());
	}
	
	private $request;
};

?>