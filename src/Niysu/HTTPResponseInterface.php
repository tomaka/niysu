<?php
namespace Niysu;

/// \brief Interface for an HTTP response
abstract class HTTPResponseInterface {
	/// \brief Sets the status code to return with the headers
	/// \pre !isHeadersListSent()
	abstract public function setStatusCode($statusCode);
	/// \brief Adds a header to the headers list
	/// \pre !isHeadersListSent()
	abstract public function addHeader($header, $value);
	/// \brief Removes all headers with the same name and replaces them with the given header:value combo
	/// \pre !isHeadersListSent()
	abstract public function setHeader($header, $value);
	/// \brief Removes all headers with this name
	/// \pre !isHeadersListSent()
	abstract public function removeHeader($header);
	/// \brief Returns true if the headers list was already sent back to the web server
	abstract public function isHeadersListSent();
	/// \brief Adds content
	/// \note Sends the headers list of not already sent
	abstract public function appendData($data);
	
	/// \param $type An array with keys like 'public' or 'max-age', with an optional value setting the value
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
	
	public function setHTMLData($data) {
		$this->setHeader('Content-Type', 'text/html');
		$this->appendData($data);
	}

	/// \param $data A two-dimensionnal array
	public function setCSVData($data) {
		$this->setHeader('Content-Type', 'text/csv; charset=utf8');

		$fp = fopen('php://memory', 'r+');
		foreach ($data as $row)
			fputcsv($fp, $row, ';');
		rewind($fp);
		$this->appendData(stream_get_contents($fp));
		fclose($fp);
	}
	
	public function setJSONData($data) {
		$this->setHeader('Content-Type', 'application/json');
		$this->appendData(json_encode($data));
	}

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

	public function setPlainTextData($data) {
		$this->setHeader('Content-Type', 'text/plain; charset=utf8');
		$this->appendData($data);
	}

	public static function handleXMLInput($request, &$inputXML, $response, &$ignoreHandler) {
		if (!$request->isXMLData()) {
			$response->setStatusCode(400);
			$ignoreHandler = true;
			return;
		}

		$inputXML = $request->getXMLData()->children('http://www.fdmjc67.net');
	}
	
	public static function handleJSONInput($request, &$inputJSON, $response, &$ignoreHandler) {
		if (!$request->isJSONData()) {
			$response->setStatusCode(400);
			$ignoreHandler = true;
			return;
		}

		$inputJSON = $request->getJSONData();
	}
};

?>