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

	public function getURL() {
		return $this->url;
	}

	public function getMethod() {
		return strtoupper($this->method);
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

	public function isHTTPS() {
		return $this->https;
	}

	public function getCookiesList() {
		throw new \LogicException('Not yet implemented');
	}


	private $url;
	private $method;
	private $headersList = [];
	private $rawData;
	private $https;
}

?>