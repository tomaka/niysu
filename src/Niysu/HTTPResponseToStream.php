<?php
namespace Niysu;
require_once __DIR__.'/HTTPResponseInterface.php';

/**
 * Implementation of HTTPResponseInterface which will store everything in a stream.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPResponseToStream implements HTTPResponseInterface {
	public function __construct($stream) {
		$this->stream = $stream;
	}

	public function flush() {
		fflush($this->stream);
	}

	public function setStatusCode($statusCode) {
		$this->statusCode = $statusCode;
	}

	public function addHeader($header, $value) {
		$this->headers[] = $header.': '.$value;
	}

	public function setHeader($header, $value) {
		$this->removeHeader($header);
		$this->addHeader($header, $value);
	}

	public function removeHeader($header) {
		foreach ($this->headers as $k => $h)
			if (strpos($header, $h) === 0)
				unset($this->headers[$k]);
	}

	public function isHeadersListSent() {
		return $this->headersListSent;
	}
	
	public function appendData($data) {
		if (!$this->headersListSent)
			$this->sendHeadersList();
		fwrite($this->stream, $data);
	}



	private function sendHeadersList() {
		fwrite($this->stream, 'HTTP/1.1 '.$this->statusCode.' Blabla'."\r\n");		// TODO: 
		fwrite($this->stream, implode("\r\n", $this->headers)."\r\n\r\n");
		$this->headersListSent = true;
	}


	private $stream;
	private $statusCode = 200;
	private $headers = [];
	private $headersListSent = false;
};
