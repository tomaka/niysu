<?php
namespace Niysu\Input;

/**
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class XMLInput implements \Niysu\InputInterface {
	public function __construct(\Niysu\HTTPRequestInterface $request) {
		$this->request = $request;
	}
	
	public function isXMLContentType() {
		try {
			$contentType = $this->request->getHeader('Content-Type');

			if (substr($contentType, 0, 8) == 'text/xml' || substr($contentType, 0, 15) == 'application/xml')
				return true;
			if (preg_match('/^(\w+)\\/.+?\\+xml$/i', $contentType))
				return true;

		} catch(\Exception $e) {}
		return false;
	}

	public function isValid() {
		try {
			if (!$this->isXMLContentType())
				return false;
			
			$this->getXMLData();
			return true;

		} catch(\Exception $e) {}

		return false;
	}

	public function getXMLData() {
		$prevVal = libxml_use_internal_errors(true);
		$data = new \SimpleXMLElement($this->request->getRawData());
		libxml_use_internal_errors($prevVal);
		return $data;
	}


	private $request;
}
