<?php
namespace Niysu;

class HTTPRequestCustom extends HTTPRequestInterface {
	public function __construct($url, $method = 'GET', $headers = [], $rawData = null, $https = false) {
		$this->url = $url;
		$this->method = $method;
		$this->headersList = $headers;
		$this->rawData = $rawData;
		$this->https = $https;
	}

	public static function copyOf(HTTPRequestInterface $source) {
		$destination = new HTTPRequestCustom($source->getURL(), $source->getMethod(), $source->getHeadersList(), $source->getRawData(), $source->isHTTPS());
	}

	public function getURL() {
		return $this->url;
	}

	public function setURL($url) {
		$this->url = $url;
	}

	public function getMethod() {
		return strtoupper($this->method);
	}

	public function setMethod($method) {
		$this->method = $method;
	}

	public function getHeader($header) {
		foreach ($this->headersList as $key => $val) {
			if (strtolower($key) == strtolower($header))
				return $val;
		}
	}

	public function getHeadersList() {
		return $this->headersList;
	}

	public function getRawData() {
		return $this->rawData;
	}

	public function setRawData($data) {
		$this->rawData = $data;
	}

	public function isHTTPS() {
		return $this->https;
	}

	public function setHTTPS($https) {
		$this->https = $https;
	}


	private $url;
	private $method;
	private $headersList = [];
	private $rawData;
	private $https;
}

?>