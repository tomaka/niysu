<?php
namespace Niysu;

/**
 * Interface which allows to read the infos about an HTTPRequest.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
abstract class HTTPRequestInterface {
	/**
	 * Returns the URL requested by the query, like: /users/5-john-doe
	 *
	 * @return string
	 */
	abstract public function getURL();
	
	/**
	 * Returns the method of the request: GET, POST, PUT, etc.
	 *
	 * @return string
	 */
	abstract public function getMethod();
	
	/**
	 * Returns the value of this header.
	 *
	 * Returns null if the header has not been set.
	 *
	 * If there are multiple headers with the same name, the $index parameter can specify the index of the value to retreive.
	 *
	 * @param string 	$header 	Header to read
	 * @param integer 	$index 		Index of the header
	 * @return string
	 */
	abstract public function getHeader($header, $index = 0);
	
	/**
	 * Returns an associative array of header => value
	 *
	 * @return array
	 */
	abstract public function getHeadersList();
	
	/**
	 * Returns the raw data given with the query.
	 *
	 * @return string
	 */
	abstract public function getRawData();
	
	/**
	 * Returns true if the request is made through HTTPS.
	 *
	 * @return boolean
	 */
	abstract public function isHTTPS();
	

	/**
	 * Returns the value of the Content-Type header, or 'application/octet-stream' if no such header
	 *
	 * @return string
	 */
	public function getContentTypeHeader() {
		$val = $this->getHeader('Content-Type');
		return $val ? $val : 'application/octet-stream';
	}
	
	/**
	 * Returns true if the method is either GET or HEAD
	 *
	 * @return boolean
	 */
	public function isMethodGETOrHEAD() {
		$method = strtoupper($this->getMethod());
		return $method == 'GET' || $method == 'HEAD';
	}

	/**
	 * Returns true if the method is PUT
	 *
	 * @return boolean
	 */
	public function isMethodPUT() {
		return strtoupper($this->getMethod()) == 'PUT';
	}

	/**
	 * Returns true if the method is POST
	 *
	 * @return boolean
	 */
	public function isMethodPOST() {
		return strtoupper($this->getMethod()) == 'POST';
	}
	
	/**
	 * Returns true if the method is DELETE
	 *
	 * @return boolean
	 */
	public function isMethodDELETE() {
		return strtoupper($this->getMethod()) == 'DELETE';
	}
	
	/**
	 * Returns true if the method is OPTIONS
	 *
	 * @return boolean
	 */
	public function isMethodOPTIONS() {
		return strtoupper($this->getMethod()) == 'OPTIONS';
	}
	
	/**
	 * Returns true if the method is TRACE
	 *
	 * @return boolean
	 */
	public function isMethodTRACE() {
		return strtoupper($this->getMethod()) == 'TRACE';
	}
	
	/**
	 * Returns true if the method is CONNECTION
	 *
	 * @return boolean
	 */
	public function isMethodCONNECT() {
		return strtoupper($this->getMethod()) == 'CONNECT';
	}
	
	/**
	 * Returns true if the method is PATCH
	 *
	 * @return boolean
	 */
	public function isMethodPATCH() {
		return strtoupper($this->getMethod()) == 'PATCH';
	}

	/**
	 * Reads the Accept header and gets the priority of a MIME.
	 *
	 * For example, if the Accept header is: text/html;q=1,image/*;0.8
	 * Then calling this function with "text/html" will return 1, with "image/png" will return 0.8, and with anything else null.
	 *
	 * @param string 	$myMime 	The MIME to check.
	 * @return number
	 */
	public function getPriorityForMIME($myMime) {
		return self::getPriorityFor(self::buildData($this->getHeader('Accept')), $myMime);
	}

	/**
	 * Reads the Accept-Language header and gets the priority of a language.
	 *
	 * Works the same way as getPriorityForMIME() but for languages.
	 *
	 * @param string 	$myLanguage 	The language to check.
	 * @return number
	 * @see getPriortyForMIME
	 */
	public function getPriorityForLanguage($myLanguage) {
		return self::getPriorityFor(self::buildData($this->getHeader('Accept-Language')), $myLanguage);
	}

	/**
	 * Reads the Accept-Encoding header and gets the priority of an encoding.
	 *
	 * Works the same way as getPriorityForMIME() but for encodings.
	 *
	 * @param string 	$myEncoding 	The encoding to check.
	 * @return number
	 * @see getPriortyForMIME
	 */
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
		if (empty($data))
			return null;

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

		if (empty($data))
			return null;

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
