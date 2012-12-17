<?php
namespace Niysu;

require_once __DIR__.'/HTTPResponseInterface.php';

class HTTPResponseGlobal extends HTTPResponseInterface {
	public function __construct() {
		// removing some headers
		$this->removeHeader('Server');
		$this->removeHeader('X-Powered-By');
	}

	public function flush() {
		flush();
	}

	public function setStatusCode($statusCode) {
		http_response_code($statusCode);
	}

	public function addHeader($header, $value) {
		$code = http_response_code();
		header($header.':'.$value, false);
		http_response_code($code);
	}

	public function setHeader($header, $value) {
		$code = http_response_code();
		header($header.':'.$value, true);
		http_response_code($code);
	}

	public function removeHeader($header) {
		header($header.':');
	}

	public function isHeadersListSent() {
		return headers_sent();
	}

	public function appendData($data) {
		echo $data;
	}
}

?>