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
class ContentEncodingResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;

	public function __construct(\Niysu\HTTPResponseInterface $next, \Niysu\HTTPRequestInterface $request) {
		$this->outputResponse = $next;

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
		if ($this->data) 	$this->outputResponse->appendData(call_user_func($this->compressFunction, $this->data));
		else 				$this->outputResponse->removeHeader('Content-Encoding');
		$this->outputResponse->flush();
	}

	public function setHeader($header, $value) {
		if ($header == 'Content-Encoding')
			$this->compressFunction = function($data) { return $data; };
		$this->outputResponse->setHeader($header, $value);
	}

	public function addHeader($header, $value) {
		if ($header == 'Content-Encoding')
			$this->compressFunction = function($data) { return $data; };
		$this->outputResponse->addHeader($header, $value);
	}

	public function removeHeader($header) {
		if ($header == 'Content-Encoding')
			$this->compressFunction = function($data) { return $data; };
		$this->outputResponse->removeHeader($header);
	}

	private function setEncodingToUse($encodingUsed) {
		switch ($encodingUsed) {
			case 'identity':
				$this->compressFunction = function($data) { return $data; };
				$this->outputResponse->removeHeader('Content-Encoding');
				break;

			case 'x-gzip':
			case 'gzip':
				$this->compressFunction = function($data) { return gzencode($data); };
				$this->outputResponse->setHeader('Content-Encoding', 'gzip');
				break;

			case 'deflate':
				$this->compressFunction = function($data) { return zlib_encode($data, 15); };
				$this->outputResponse->setHeader('Content-Encoding', 'deflate');
				break;

			case 'bzip2':
				$this->compressFunction = function($data) { return bzcompress($data); };
				$this->outputResponse->setHeader('Content-Encoding', 'bzip2');
				break;

			default:
				throw new \LogicException('Unknown encoding');
		}
	}

	private $data;
	private $compressFunction;
};
