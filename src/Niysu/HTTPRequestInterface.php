<?php
namespace Niysu;

/// \brief Interface for an HTTP response
abstract class HTTPRequestInterface {
	abstract public function getURL();
	abstract public function getMethod();
	abstract public function getHeader($header);
	abstract public function getHeadersList();
	abstract public function getRawData();
	
	/// \brief Pass this to the ->before of a route
	public static function requireInputXML(HTTPRequestInterface $request, &$inputXML, HTTPResponseInterface $response, &$ignoreHandler) {
		if (!$request->isXMLData()) {
			$response->setStatusCode(400);
			$ignoreHandler = true;
			return;
		}
		
		$inputXML = $request->getXMLData();
	}
	
	/// \brief Pass this to the ->before of a route
	public static function requireInputJSON(HTTPRequestInterface $request, &$inputJSON, HTTPResponseInterface $response, &$ignoreHandler) {
		if (!$request->isJSONData()) {
			$response->setStatusCode(400);
			$ignoreHandler = true;
			return;
		}

		$inputJSON = $request->getJSONData();
	}
	
	/// \brief Returns the list of cookies
	public function getCookiesList() {
		throw new \Exception('Not yet implemented');
	}
	
	/// \brief Returns the value of the Content-Type header, or 'application/octet-stream' if no such header
	public function getContentTypeHeader() {
		$val = $this->getHeader('Content-Type');
		return $val ? $val : 'application/octet-stream';
	}
	
	/// \brief Returns true if the data is x-www-url-encoded
	public function isWWWUrlEncodedData() {
		return $this->getContentTypeHeader() == 'x-www-url-encoded';
	}
	
	/// \brief Returns true if the data is in JSON according to the Content-Type
	public function isJSONData() {
		if (substr($this->getContentTypeHeader(), 0, 16) == 'application/json')
			return true;
		if (substr($this->getContentTypeHeader(), 0, 22) == 'application/javascript')
			return true;
		if (substr($this->getContentTypeHeader(), 0, 15) == 'text/javascript')
			return true;
		if (substr($this->getContentTypeHeader(), 0, 17) == 'text/x-javascript')
			return true;
		if (substr($this->getContentTypeHeader(), 0, 11) == 'text/x-json')
			return true;
		return false;
	}

	/// \brief Returns true if the data is in XML according to the Content-Type
	public function isXMLData() {
		$ctntType = $this->getContentTypeHeader();
		if (substr($ctntType, 0, 8) == 'text/xml' || substr($ctntType, 0, 15) == 'application/xml')
			return true;
		if (preg_match('/^(application|text)\\/.+?\\+xml$/i', $ctntType))
			return true;
		return false;
	}

	/// \brief Returns an array with the raw data decoded as x-www-url-encoded
	public function getWWWUrlEncodedData() {
		$array = [];
		parse_str($this->getRawData(), $array);
		return $array;
	}

	/// \brief Returns an array with the raw data decoded as if it was JSON
	public function getJSONData() {
		return json_decode($this->getRawData());
	}

	/// \brief Returns a SimpleXMLElement with the packet content
	public function getXMLData() {
		return new SimpleXMLElement($this->getRawData());
	}

	/// \brief Returns true if the method is GET or HEAD
	public function isMethodGETOrHEAD() {
		$method = strtoupper($this->getMethod());
		return $method == 'GET' || $method == 'HEAD';
	}

	/// \brief Returns true if the method is PUT
	public function isMethodPUT() {
		return strtoupper($this->getMethod()) == 'PUT';
	}

	/// \brief Returns true if the method is POST
	public function isMethodPOST() {
		return strtoupper($this->getMethod()) == 'POST';
	}
	
	/// \brief Returns true if the method is DELETE
	public function isMethodDELETE() {
		return strtoupper($this->getMethod()) == 'DELETE';
	}
	
	/// \brief Returns true if the method is OPTIONS
	public function isMethodOPTIONS() {
		return strtoupper($this->getMethod()) == 'OPTIONS';
	}
	
	/// \brief Returns true if the method is TRACE
	public function isMethodTRACE() {
		return strtoupper($this->getMethod()) == 'TRACE';
	}
	
	/// \brief Returns true if the method is CONNECT
	public function isMethodCONNECT() {
		return strtoupper($this->getMethod()) == 'CONNECT';
	}
	
	/// \brief Returns true if the method is PATCH
	public function isMethodPATCH() {
		return strtoupper($this->getMethod()) == 'PATCH';
	}

	/// \brief Returns the priority in float of the given mime, or null if the mime doesn't match anything from the client
	public function getPriorityForMIME($myMime) {
		return self::getPriorityFor(self::buildData($this->getHeader('Accept')), $myMime);
	}

	/// \brief Returns the priority in float of the given language, or null if the language doesn't match anything from the client
	public function getPriorityForLanguage($myLanguage) {
		return self::getPriorityFor(self::buildData($this->getHeader('Accept-Language')), $myLanguage);
	}

	/// \brief Returns the priority in float of the given encoding, or null if the encoding doesn't match anything from the client
	public function getPriorityForEncoding($myEncoding) {
		return self::getPriorityFor(self::buildData($this->getHeader('Accept-Encoding')), $myEncoding);
	}

	/// \brief This function takes any number of arguments and returns the one with the highest priority
	/// \note Returns null if none matches
	public function getHighestPriorityForMIME() {
		return self::getHighestPriorityFor(self::buildData($this->getHeader('Accept')), func_get_args());
	}

	/// \brief This function takes any number of arguments and returns the one with the highest priority
	/// \note Returns null if none matches
	public function getHighestPriorityForLanguage() {
		return self::getHighestPriorityFor(self::buildData($this->getHeader('Accept-Language')), func_get_args());
	}

	/// \brief This function takes any number of arguments and returns the one with the highest priority
	/// \note Returns null if none matches
	public function getHighestPriorityForEncoding() {
		return self::getHighestPriorityFor(self::buildData($this->getHeader('Accept-Encoding')), func_get_args());
	}




	private static function buildData($headerValue) {
		$data = [];
		if (!$headerValue)
			return null;
		foreach (explode(',', $headerValue) as $v) {
			$u = explode(';', $v);
			if (count($u) == 0 || strlen(trim($v)) == 0)
				continue;
			$priority = isset($u[1]) ? trim($u[1]) : '';
			$priority = preg_replace('/q\\s*=\\s*(.*)$/', '$1', $priority);
			$data[] = ['data' => trim($u[0]), 'priority' => floatval($priority == '' ? 1.0 : $priority)];
		}
		/*usort($data, function($a, $b) {
			if ($a['priority'] < $b['priority'])	return 1;
			if ($a['priority'] > $b['priority'])	return -1;
			return 0;
		});*/
		return $data;
	}

	private static function getPriorityFor($data, $myData) {
		foreach($data as $element) {
			if ($element['data'] == $myData)
				return $element['priority'];
		}

		foreach($data as $element) {
			$regex = preg_quote($element['data']);
			$regex = str_replace('/', '\\/', $regex);
			$regex = str_replace('\\*', '.*', $regex);
			if (preg_match('/^'.$regex.'$/i', $myData))
				return $element['priority'];
		}
		
		return null;
	}
	
	private static function getHighestPriorityFor($data, $args) {
		if (empty($args))
			throw new \LogicException('getHighestPriority needs at least one parameter');
		$vals = [];
		foreach ($args as $arg) {
			$prio = self::getPriorityFor($data, $arg);
			if (!isset($vals[$prio]))
				$vals[$prio] = [];
			$vals[$prio][] = $arg;
		}
		krsort($vals);
		reset($vals);
		
		$tab = current($vals);
		if (count($tab) == 1)
			return $tab[0];
		
		// we have multiple elements with the same priority
		// returning the first one given by the header
		foreach ($data as $elem) {
			if (in_array($elem['data'], $args))
				return $elem['data'];
		}
		
		return null;
	}
};

?>