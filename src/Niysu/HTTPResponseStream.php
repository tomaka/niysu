<?php
namespace Niysu;

/**
 * This class can build a PHP filename that will act as a stream to write in a HTTPResponse.
 *
 * Usage:
 *	stream_wrapper_register('httpResponseWriter', 'Niysu\\HTTPResponseStream');
 *	$fp = fopen('httpResponseWriter://response', 'w', false, stream_context_create([ 'httpResponseWriter' => [ 'response' => $response ] ]));
 *	fputs($fp, 'body');
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPResponseStream {
	/**
	 * Returns a SplFileObject that will write in this response.
	 *
	 * @param HTTPResponseInterface 	$response 			The response that will act as output
	 * @param boolean 					$writeHeaders 		True if the stream will write headers too
	 * @return SplFileObject
	 */
	public static function build(HTTPResponseInterface $response, $writeHeaders = false) {
		if (!self::$wrapperRegistered) {
			stream_wrapper_register('httpResponseWriter', get_class());
			self::$wrapperRegistered = true;
		}

		return new \SplFileObject('httpResponseWriter://response', 'w', false, stream_context_create([ 'httpResponseWriter' => [ 'response' => $response, 'headers' => $writeHeaders ] ]));
	}

	/**
	 *
	 */
	public function stream_open($path, $mode, $options, &$opened_path) {
		if (!preg_match('/^(w|a|x|c)b?$/', $mode))
			return false;

		$params = stream_context_get_options($this->context)[parse_url($path)['scheme']];
		if (!$params)
			return false;
		if (!$params['response'])
			return false;

		$this->response = $params['response'];
		$this->writeHeaders = $params['headers'];
		return true;
	}

	/**
	 */
	public function stream_write($data) {
		// computing $length, that's the value that we will return
		$initialLength = strlen($data);

		// adding $this->prependOnNextWrite
		$data = $this->prependOnNextWrite.$data;
		$this->prependOnNextWrite = '';

		// if we are not writing headers anymore, just appending
		if (!$this->writeHeaders) {
			if ($data)
				$this->response->appendData($data);
			return $initialLength;
		}

		// getting first header of the data
		$currentHeader = explode("\r\n", $data, 2);
		if (!isset($currentHeader[1])) {
			$this->prependOnNextWrite = $data;
			return $initialLength;
		}

		// if the header to process is empty, this means that we are in fact at the start of the data
		if (empty($currentHeader[0])) {
			$this->writeHeaders = false;
			$this->response->appendData($currentHeader[1]);
			return $initialLength;
		}

		// if we have "HTTP/1.1 200 OK", then we setStatusCode instead of addHeader
		if (preg_match('/^HTTP.* (\d{3}) .*$/', $currentHeader[0], $matches)) {
			$this->response->setStatusCode(intval($matches[1]));

		} else {
			// writing header
			$split = explode(':', $currentHeader[0], 2);
			if (isset($split[1])) {
				if ($split[0] == 'Status')		$this->response->setStatusCode(intval($split[1]));
				else							$this->response->addHeader($split[0], $split[1]);
			}
		}

		// writing the rest of the data
		$this->stream_write($currentHeader[1]);
		return $initialLength;
	}

	public function stream_close() {
		// flushes $prependOnNextWrite
		$this->stream_write('');
	}

	public function url_stat($path, $flags) {
		return [
			0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
		];
	}


	private $prependOnNextWrite = '';
	private $response = null;
	private $writeHeaders = false;
	static private $wrapperRegistered = false;
}
