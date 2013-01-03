<?php
namespace Niysu\Filters;

/**
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class POSTRequestFilter extends \Niysu\HTTPRequestFilterInterface {
	public function __construct(\Niysu\HTTPRequestInterface $request) {
		parent::__construct($request);
	}

	public function __get($varName) {
		return $this->getPOSTData()->$varName;
	}
	
	public function __isset($varName) {
		return isset($this->getPOSTData()->$varName);
	}

	public function getFile($fileID) {
		$g = self::isGlobalRequest($this);
		if (!$g)
			return null;
		if (!isset($_FILES[$fileID]))
			return null;

		return (object)[
			'mime' => $_FILES[$fileID]['type'],
			'name' => $_FILES[$fileID]['name'],
			'size' => $_FILES[$fileID]['size'],
			'stream' => new \SplFileObject($_FILES[$fileID]['tmp_name'], 'r')
		];
	}
	
	public function isPOSTContentType() {
		if (substr($this->getContentTypeHeader(), 0, 33) == 'application/x-www-form-urlencoded')
			return true;
		/*if (substr($this->getContentTypeHeader(), 0, 19) == 'multipart/form-data')
			return true;*/
		return false;
	}

	public function isValidPOSTData() {
		if (!$this->isPOSTContentType())
			return false;
		
		try {
			$this->getPOSTData();
			return true;

		} catch(\Exception $e) {
			return false;
		}
	}

	public function getPOSTData() {
		if (!$this->dataCacheStale)
			return $this->dataCache;

		$array = [];
		parse_str($this->getRawData(), $array);
		return (object)$array;

		$this->dataCacheStale = false;
		return $this->dataCache;
	}



	private static function isGlobalRequest(\Niysu\HTTPRequestInterface $rq) {
		if ($rq instanceof \Niysu\HTTPRequestGlobal)
			return true;
		if ($rq instanceof \Niysu\HTTPRequestFilterInterface)
			return $rq->getInput();
		return false;
	}


	private $dataCache;
	private $dataCacheStale = true;
}

?>