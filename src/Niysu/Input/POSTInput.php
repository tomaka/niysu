<?php
namespace Niysu\Input;

/**
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class POSTInput implements \Niysu\InputInterface {
	public function __construct(\Niysu\HTTPRequestInterface $request) {
		$this->request = $request;
	}

	public function __get($varName) {
		$data = $this->getPOSTData();
		if (isset($data->$varName))
			return $data->$varName;

		if ($f = $this->getFile($varName))
			return $f;

		return null;
	}
	
	public function __isset($varName) {
		return isset($this->getPOSTData()->$varName);
	}

	public function getFile($fileID) {
		if (!self::isGlobalRequest($this->request))
			return null;
		if (!isset($_FILES[$fileID]))
			return null;

		$result = [];
		if (is_array($_FILES[$fileID]['type'])) {
			foreach ($_FILES[$fileID]['type'] as $key => $v) {

				if ($_FILES[$fileID]['error'][$key] != UPLOAD_ERR_OK) {
					$result[] = (object)[ 'errorCode' => $_FILES[$fileID]['error'][$key], 'errorString' => $_FILES[$fileID]['error'][$key] ];

				} else {
					$result[] = (object)[
						'mime' => $_FILES[$fileID]['type'][$key],
						'name' => $_FILES[$fileID]['name'][$key],
						'size' => $_FILES[$fileID]['size'][$key],
						'file' => $_FILES[$fileID]['tmp_name'][$key],
						'stream' => new \SplFileObject($_FILES[$fileID]['tmp_name'][$key], 'r')
					];
				}
			}

		} else {
			if ($_FILES[$fileID]['error'] != UPLOAD_ERR_OK) {
				$result = (object)[ 'errorCode' => $_FILES[$fileID]['error'], 'errorString' => $_FILES[$fileID]['error'] ];

			} else {
				$result = (object)[
					'mime' => $_FILES[$fileID]['type'],
					'name' => $_FILES[$fileID]['name'],
					'size' => $_FILES[$fileID]['size'],
					'file' => $_FILES[$fileID]['tmp_name'],
					'stream' => new \SplFileObject($_FILES[$fileID]['tmp_name'], 'r')
				];
			}
		}

		return $result;
	}
	
	public function isPOSTContentType() {
		$contentType = $this->request->getHeader('Content-Type');
		if (substr($contentType, 0, 33) == 'application/x-www-form-urlencoded')
			return true;
		/*if (substr($this->getContentTypeHeader(), 0, 19) == 'multipart/form-data')
			return true;*/
		return false;
	}

	public function isValid() {
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
		if (self::isGlobalRequest($this->request))
			return (object)$_POST;

		$array = [];
		parse_str($this->request->getRawData(), $array);
		return (object)$array;
	}



	private static function isGlobalRequest(\Niysu\HTTPRequestInterface $rq) {
		if ($rq instanceof \Niysu\HTTPRequestGlobal)
			return true;
		if ($rq instanceof \Niysu\HTTPRequestFilterInterface)
			return $rq->getInput();
		return false;
	}


	private $request;
}
