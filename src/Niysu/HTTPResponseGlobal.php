<?php
namespace Niysu;
require_once __DIR__.'/HTTPResponseInterface.php';

/**
 * Implementation of HTTPResponseInterface which will send everything to the output of PHP.
 */
class HTTPResponseGlobal extends HTTPResponseInterface {
	public function __construct() {
		// removing some headers
		if (!$this->isHeadersListSent()) {
			$this->removeHeader('X-Powered-By');
			$this->setHeader('Content-Type', '');
		}
	}

	/**
	 * Calls the PHP function flush()
	 */
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
		header_remove($header);
	}

	public function isHeadersListSent() {
		return headers_sent();
	}

	public function appendData($data) {
		echo $data;
	}
}

?>