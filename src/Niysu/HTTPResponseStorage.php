<?php
namespace Niysu;

class HTTPResponseStorage extends HTTPResponseInterface {
	public function getStatusCode() {
		return $this->statusCode;
	}

	public function setStatusCode($statusCode) {
		$this->statusCode = $statusCode;
	}
	
	public function hasHeader($header) {
		return isset($this->headers[$header]);
	}
	
	public function getHeader($header) {
		return $this->headers[$header];
	}
	
	public function getHeadersList() {
		return $this->headers;
	}
	
	public function addHeader($header, $value) {
		// TODO:
		$this->setHeader($header, $value);
	}

	public function setHeader($header, $value) {
		$this->headers[$header] = $value;
	}

	public function removeHeader($header) {
		unset($this->headers[$header]);
	}

	public function isHeadersListSent() {
		return false;
	}

	public function getData() {
		return $this->data;
	}
	
	public function appendData($data) {
		$this->data .= $data;
	}


	private $statusCode = 200;
	private $headers = [];
	private $data = '';
};

?>