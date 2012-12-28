<?php
namespace Niysu\Filters;

/**
 * Handles content-encoding as request by the client.
 *
 * Supported encoding: gzip, deflate, bzip2 (if extension is installed).
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class ContentEncodingResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $next, \Niysu\HTTPRequestInterface $request) {
		parent::__construct($next);

		// computing the list of encodings supported by the server
		$availableEncodings = [ 'gzip', 'deflate', 'x-gzip', 'identity' ];
		if (function_exists('bzcompress'))
			$availableEncodings = array_splice($availableEncodings, 2, 0, 'bzip2');

		// determining the encoding to use
		$encodingUsed = call_user_func_array([ $request, 'getHighestPriorityForEncoding' ], $availableEncodings);
		if (empty($encodingUsed))
			$encodingUsed = 'identity';
		$this->setEncodingToUse($encodingUsed);
	}

	public function appendData($data) {
		$this->data .= $data;
	}

	public function flush() {
		if ($this->data) 	parent::appendData(call_user_func($this->compressFunction, $this->data));
		else 				parent::removeHeader('Content-Encoding');
		parent::flush();
	}

	public function setHeader($header, $value) {
		if ($header == 'Content-Encoding')
			$this->compressFunction = function($data) { return $data; };
		parent::setHeader($header, $value);
	}

	public function addHeader($header, $value) {
		if ($header == 'Content-Encoding')
			$this->compressFunction = function($data) { return $data; };
		parent::addHeader($header, $value);
	}

	public function removeHeader($header) {
		if ($header == 'Content-Encoding')
			$this->compressFunction = function($data) { return $data; };
		parent::removeHeader($header);
	}

	private function setEncodingToUse($encodingUsed) {
		switch ($encodingUsed) {
			case 'identity':
				$this->compressFunction = function($data) { return $data; };
				parent::removeHeader('Content-Encoding');
				break;

			case 'x-gzip':
			case 'gzip':
				$this->compressFunction = function($data) { return gzencode($data); };
				parent::setHeader('Content-Encoding', 'gzip');
				break;

			case 'deflate':
				$this->compressFunction = function($data) { return zlib_encode($data, 15); };
				parent::setHeader('Content-Encoding', 'deflate');
				break;

			case 'bzip2':
				$this->compressFunction = function($data) { return bzcompress($data); };
				parent::setHeader('Content-Encoding', 'bzip2');
				break;

			default:
				throw new \LogicException('Unknown encoding');
		}
	}

	private $data;
	private $compressFunction;
};

?>