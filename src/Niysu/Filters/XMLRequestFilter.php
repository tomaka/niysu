<?php
namespace Niysu\Filters;

/**
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class XMLRequestFilter extends \Niysu\HTTPRequestFilterInterface {
	public function __construct(\Niysu\HTTPRequestInterface $request) {
		parent::__construct($request);
	}
	
	public function isXMLContentType() {
		$ctntType = $this->getContentTypeHeader();

		if (substr($ctntType, 0, 8) == 'text/xml' || substr($ctntType, 0, 15) == 'application/xml')
			return true;
		if (preg_match('/^(\w+)\\/.+?\\+xml$/i', $ctntType))
			return true;
		return false;
	}

	public function isValidXML() {
		if (!$this->isXMLContentType())
			return false;
		
		try {
			$this->getXMLData();
			return true;

		} catch(\Exception $e) {
			return false;
		}
	}

	public function getXMLData() {
		if (!$this->dataCacheStale)
			return $this->dataCache;

		$this->dataCache = new \SimpleXMLElement($this->getRawData());
		$this->dataCacheStale = false;
		return $this->dataCache;
	}


	private $dataCache;
	private $dataCacheStale = true;
}
