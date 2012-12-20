<?php
namespace Niysu;
require_once __DIR__.'/HTTPRequestInterface.php';

/**
 * Implementation of HTTPRequestInterface which allows you to define the values of the request.
 */
class HTTPRequestCustom extends HTTPRequestInterface {
	public function __construct($url, $method = 'GET', $headers = [], $rawData = null, $https = false) {
		$this->url = $url;
		$this->method = $method;
		$this->headersList = $headers;
		$this->rawData = $rawData;
		$this->https = $https;
	}

	/**
	 * Builds a copy of another HTTPRequestInterface.
	 *
	 * @param HTTPRequestInterface 	$source 	The request to clone
	 * @return HTTPRequestCustom
	 */
	public static function copyOf(HTTPRequestInterface $source) {
		return new HTTPRequestCustom($source->getURL(), $source->getMethod(), $source->getHeadersList(), $source->getRawData(), $source->isHTTPS());
	}

	public function getURL() {
		return $this->url;
	}

	/**
	 * Sets the URL of the request.
	 *
	 * @param string 	$url 	URL of the request
	 */
	public function setURL($url) {
		$this->url = $url;
	}

	public function getMethod() {
		return strtoupper($this->method);
	}

	/**
	 * Sets the method of the request.
	 *
	 * @param string 	$method 	Method of the request
	 */
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

	/**
	 * Sets the data of the request.
	 *
	 * @param string 	$data 	Data of the request
	 */
	public function setRawData($data) {
		$this->rawData = $data;
	}

	public function isHTTPS() {
		return $this->https;
	}

	/**
	 * Sets whether the request has been made through HTTPS.
	 *
	 * @param boolean 	$https 	True if request made through HTTPS
	 */
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