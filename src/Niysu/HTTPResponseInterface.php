<?php
namespace Niysu;

/**
 * Interface for the response of an HTTP request
 */
abstract class HTTPResponseInterface {
	/**
	 * Sets the status code to return with the headers.
	 *
	 * @pre !isHeadersListSent()
	 * @param integer 	$statusCode 	The status code
	 */
	abstract public function setStatusCode($statusCode);

	/**
	 * Adds a header to the headers list.
	 *
	 * @pre !isHeadersListSent()
	 * @param string 	$header 	Header to add
	 * @param string 	$value 		Value of the header
	 */
	abstract public function addHeader($header, $value);

	/**
	 * Removes all headers of this name and adds a new one.
	 *
	 * Equivalent to "removeHeader($header); addHeader($header, $value);"
	 *
	 * @pre !isHeadersListSent()
	 * @param string 	$header 	Header to add
	 * @param string 	$value 		Value of the header
	 */
	abstract public function setHeader($header, $value);

	/**
	 * Removes all headers with this name.
	 *
	 * @pre !isHeadersListSent()
	 * @param string 	$header 	Name of the headers to remove
	 */
	abstract public function removeHeader($header);

	/**
	 * Returns true if the response has already sent its headers and is now sending data.
	 *
	 * If true, then you can't modify headers anymore.
	 *
	 * @return boolean
	 */
	abstract public function isHeadersListSent();

	/**
	 * Appends data to the end of the response.
	 *
	 * Note that this doesn't trigger a headers sending.
	 *
	 * @param string 	$data 		Data to append
	 */
	abstract public function appendData($data);

	/**
	 * Flushes the response.
	 *
	 * If some data has already been appended, then this will send all headers and the data already added.
	 */
	abstract public function flush();


	/**
	 * Deprecated.
	 * @deprecated
	 */
	public function redirect($target, $statusCode = 302) {
		$this->setStatusCode($statusCode);
		$this->setHeader('Location', $target);
	}

	/**
	 * Deprecated.
	 * @deprecated
	 */
	public function setCache($value) {
		if (!is_array($value))
			throw new \LogicException('$type must be an array');

		$str = '';
		foreach ($value as $key => $value) {
			if ($str)	$str .= ', ';
			if (is_numeric($key)) {
				$str .= $value;
			} else {
				$str .= $key;
				if ($value) $str .= '='.$value;
			}
		}

		$this->removeHeader('Pragma');
		$this->removeHeader('Expires');//.gmdate('D, d M Y H:i:s', time() + $expires).' GMT');
		$this->setHeader('Cache-Control', $str);
	}

	/**
	 * Deprecated.
	 * @deprecated
	 */
	public function setHTMLData($data) {
		$this->setHeader('Content-Type', 'text/html');
		$this->appendData($data);
	}

	/**
	 * Deprecated.
	 * @deprecated
	 */
	public function setCSVData($data) {
		$this->setHeader('Content-Type', 'text/csv; charset=utf8');

		$fp = fopen('php://memory', 'r+');
		foreach ($data as $row)
			fputcsv($fp, $row, ';');
		rewind($fp);
		$this->appendData(stream_get_contents($fp));
		fclose($fp);
	}

	/**
	 * Deprecated.
	 * @deprecated
	 */
	public function setJSONData($data) {
		$this->setHeader('Content-Type', 'application/json');
		$this->appendData(json_encode($data));
	}

	/**
	 * Deprecated.
	 * @deprecated
	 */
	public function setXMLData($data) {
		$this->setHeader('Content-Type', 'text/xml');

		if (is_array($data)) {
			$this->appendData(XMLOutput::writeXML($data));
			
		} else if ($data === null) {
			throw new \LogicException('Null data in setXMLData');
		} else {
			throw new \LogicException('Wrong variable type in setXMLData');
		}
	}

	/**
	 * Deprecated.
	 * @deprecated
	 */
	public function setPlainTextData($data) {
		$this->setHeader('Content-Type', 'text/plain; charset=utf8');
		$this->appendData($data);
	}
};

?>