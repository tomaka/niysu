<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class InputXMLService implements InputServiceInterface {
	public static function validateInput() {
		return function($inputXMLService) {
			return $inputXMLService->isValid();
		};
	}
	
	public function __construct($request) {
		$this->request = $request;
	}
	
	/// \brief Returns true if the data is in XML according to the Content-Type of the request
	public function isValidContentType($request = null) {
		$request = $this->getRequest($request);
		$ctntType = $request->getContentTypeHeader();

		if (substr($ctntType, 0, 8) == 'text/xml' || substr($ctntType, 0, 15) == 'application/xml')
			return true;
		if (preg_match('/^(\w+)\\/.+?\\+xml$/i', $ctntType))
			return true;
		return false;
	}
	
	/// \brief Returns true if the data is in XML according to the Content-Type of the request
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