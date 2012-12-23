<?php
namespace Niysu\Filters;

/**
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class JSONRequestFilter extends \Niysu\HTTPRequestFilterInterface {
	public function __construct(\Niysu\HTTPRequestInterface $request) {
		parent::__construct($request);
	}

	public function __get($varName) {
		return $this->getJSONData()->$varName;
	}
	
	public function __isset($varName) {
		return isset($this->getJSONData()->$varName);
	}
	
	public function isJSONContentType() {
		$contentType = $this->getContentTypeHeader();

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

	public function isValidJSON() {
		if (!$this->isJSONContentType($request))
			return false;
		
		try {
			$this->getJSONData($request);
			return true;

		} catch(\Exception $e) {
			return false;
		}
	}

	public function getJSONData() {
		if (!$this->dataCacheStale)
			return $this->dataCache;

		$this->dataCache = json_decode($this->getRawData());
		if ($this->dataCache == null) {
			switch (json_last_error()) {
				case JSON_ERROR_NONE:				break;		// the input data is the null value	; this is legitimate
				case JSON_ERROR_DEPTH:				throw new \RuntimeException('json_decode() returned JSON_ERROR_DEPTH');
				case JSON_ERROR_STATE_MISMATCH:		throw new \RuntimeException('json_decode() returned JSON_ERROR_STATE_MISMATCH');
				case JSON_ERROR_CTRL_CHAR:			throw new \RuntimeException('json_decode() returned JSON_ERROR_CTRL_CHAR');
				case JSON_ERROR_SYNTAX:				throw new \RuntimeException('json_decode() returned JSON_ERROR_SYNTAX');
				case JSON_ERROR_UTF8:				throw new \RuntimeException('json_decode() returned JSON_ERROR_UTF8');
			}
		}

		$this->dataCacheStale = false;
		return $this->dataCache;
	}


	private $dataCache;
	private $dataCacheStale = true;
}
