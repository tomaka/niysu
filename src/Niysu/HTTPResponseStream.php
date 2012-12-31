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
	 * @todo Function is very messy ; I should clean it up
	 * @todo Bug when empty headers are given (ie. writeHeaders is true and data starts with "\r\nbody")
	 */
	public function stream_write($data) {
		// computing $length, that's the value that we will return
		$length = strlen($data);

		// adding $this->prependOnNextWrite
		$data = $this->prependOnNextWrite.$data;
		$this->prependOnNextWrite = '';

		// parsing headers
		if ($this->writeHeaders) {
			list($headersRaw, $data) = explode("\r\n\r\n", $data, 2);

			// if we have found the delimiter '\r\n\r\n', then it's the last time that we write headers
			if ($data !== null) {
				$headersRaw .= "\r\n";
				$this->writeHeaders = false;

			} else {
				// we rtrim $headersRaw
				// the string that has been trimmed is added to $prependOnNextWrite
				// so for example if you write "Content-Type: text/html\r\n", prependOnNextWrite will have the value "\r\n"
				//    and if you write "\r\ndata start", then it will in fact write "\r\n\r\ndata start" and detect the delimiter
				$newHeadersRaw = rtrim($headersRaw);
				if ($headersRaw != $newHeadersRaw) {
					$trimmedPart = substr($headersRaw, strlen($newHeadersRaw) - strlen($headersRaw));
					$this->prependOnNextWrite = $trimmedPart;
				}
				$headersRaw = $newHeadersRaw;
			}

			// looping through each header
			$headersList = explode("\r\n", $headersRaw);
			foreach ($headersList as $key => $header) {
				// we ignore the last header because it didn't end with \r\n and add its value to prependOnNextWrite
				if ($key == count($headersList) - 1) {
					$this->prependOnNextWrite = $header.$this->prependOnNextWrite;
					continue;
				}

				// if we have "HTTP/1.1 200 OK", then we setStatusCode instead of addHeader
				if (preg_match('/^HTTP.* (\d{3}) .*$/', $header, $matches)) {
					$this->response->setStatusCode(intval($matches[1]));

				} else {
					// writing header
					list($name, $value) = explode(':', $header, 2);
					if ($value) {
						if ($name == 'Status')		$this->response->setStatusCode(intval($value));
						else						$this->response->addHeader($name, $value);
					}
				}
			}
		}

		// appending data
		// if $this->writeHeaders was true, then data may be null
		if ($data)
			$this->response->appendData($data);

		return $length;
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

?>