<?php
namespace Niysu;
require_once __DIR__.'/HTTPResponseInterface.php';

/**
 * Implementation of HTTPResponseInterface which will store everything in variables.
 *
 * This allows you to retreive the output later. 
 */
class HTTPResponseStorage extends HTTPResponseInterface {
	public function flush() {
	}

	/**
	 * Returns the status code previously set using setStatusCode
	 *
	 * @return integer
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	public function setStatusCode($statusCode) {
		$this->statusCode = $statusCode;
	}
	
	/**
	 * Returns true if the header has been defined.
	 *
	 * @param string 	$header 	Header name to check
	 * @return boolean
	 */
	public function hasHeader($header) {
		return isset($this->headers[$header]);
	}
	
	/**
	 * Returns the value of the header.
	 *
	 * Returns either the raw value, or an array of this header has multiple values.
	 *
	 * @param string 	$header 	Header to read
	 * @return mixed
	 */
	public function getHeader($header) {
		return $this->headers[$header];
	}
	
	/**
	 * Returns an associative array of all headers.
	 *
	 * Beware that some values may be arrays. See getHeader() for format of values.
	 *
	 * @return array
	 * @see getHeader
	 */
	public function getHeadersList() {
		return $this->headers;
	}
	
	public function addHeader($header, $value) {
		if (!isset($this->headers[$header])) {
			$this->headers[$header] = $value;

		} else if (is_array($this->headers[$header])) {
			$this->headers[$header][] = $value;

		} else {
			$this->headers[$header] = [ $this->headers[$header], $value ];
		}
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

	/**
	 * Returns all data that has been appended.
	 *
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Removes all data and replaces it by this data.
	 *
	 * @param string 	$data 		The data to set
	 */
	public function setData($data) {
		$this->data = $data;
	}
	
	public function appendData($data) {
		$this->data .= $data;
	}


	private $statusCode = 200;
	private $headers = [];
	private $data = '';
};

?>