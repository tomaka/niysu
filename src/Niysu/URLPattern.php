<?php
namespace Niysu;

/**
 * Allows to build patterns for URLs and check if a URL matches it.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class URLPattern {
	/**
	 * @param string 	$pattern 		The URL pattern
	 */
	public function __construct($pattern) {
		$this->originalPattern = $pattern;

		$currentOffset = 0;
		while (preg_match('/\{(\w+)\}/', $pattern, $match, PREG_OFFSET_CAPTURE, $currentOffset)) {
			$matchBeginOffset = $match[0][1];
			$varName = $match[1][0];

			$val = substr($pattern, $currentOffset, $matchBeginOffset - $currentOffset);
			$this->patternRegexOriginal[count($this->patternRegex)] = $val;

			$this->patternRegex[] = str_replace('/', '\/', preg_quote($val));
			$this->patternRegex[] = '(\w+)';

			$this->variablesList[] = $varName;

			$currentOffset = $matchBeginOffset + strlen($match[0][0]);
		}
		$this->patternRegex[] = str_replace('/', '\/', preg_quote(substr($pattern, $currentOffset)));

		$this->patternRegex[0] = '/^'.$this->patternRegex[0];

		$this->patternRegexOriginal[count($this->patternRegex)] = '';
		$this->patternRegex[] = '$/';
	}

	/**
	 * Checks whether the URL matches the pattern.
	 *
	 * Returns null if the URL doesn't match.
	 * Otherwise, returns an array with variables => values.
	 *
	 * @param string 	$url 	The URL to test
	 * @return mixed
	 */
	public function testURL($url) {
		// checking whether the URL matches
		if (!preg_match($this->getURLRegex(), $url, $matches))
			return null;
		
		// extracting parts of the URL
		$parameters = [];
		for ($matchNum = 1, $varNum = 0; $matchNum < count($matches); ++$varNum) {
			$varName = $this->variablesList[$varNum];
			$parameters[$varName] = urldecode($matches[$matchNum]);

			// adding to matchNum the number of '(' in the regex part
			$matchNum += count(explode('(', $this->patternRegex[$varNum * 2 + 1])) - 1;
		}
		return $parameters;
	}

	/**
	 * Changes the pattern of a variable.
	 *
	 * @param string 	$varName 		The variable name
	 * @param string 	$regex 			The regular expression (without / /)
	 * @throws LogicException If the variable doesn't exist in the pattern
	 */
	public function pattern($varName, $regex) {
		$pos = array_search($varName, $this->variablesList);
		if ($pos === false)
			throw new \LogicException('Variable doesn\'t exist in the pattern: '.$varName);

		$this->patternRegex[$pos * 2 + 1] = '('.$regex.')';
	}

	/**
	 * Returns the original pattern.
	 *
	 * @return string
	 */
	public function getOriginalPattern() {
		return $this->originalPattern;
	}

	/**
	 * Returns the regular expression to match with an URL.
	 *
	 * Includes / and / around the regex.
	 *
	 * @return string
	 */
	public function getURLRegex() {
		return implode($this->patternRegex);
	}

	/**
	 * Returns the URL of the route.
	 *
	 * @param array 	$parameters 	An associative array of parameter => value
	 * @return string
	 * @throws RuntimeException If some parameters are missing in the array
	 * @throws RuntimeException If a parameter does not match the corresponding regex
	 */
	public function getURL($parameters = []) {
		// cloning the pattern
		$patternRegex = $this->patternRegex;

		foreach ($this->variablesList as $offset => $varName) {
			if (!isset($parameters[$varName]))
				throw new \RuntimeException('Parameter missing in the array: '.$varName);

			$val = $parameters[$varName];
			if (!preg_match_all('/'.$patternRegex[$offset * 2 + 1].'/', $val))
				throw new \RuntimeException('Parameter does not match its regex: '.$varName.' doesn\'t match '.$patternRegex[$offset * 2]);

			$patternRegex[$offset * 2 + 1] = $val;
		}

		foreach ($this->patternRegexOriginal as $offset => $val)
			$patternRegex[$offset] = $val;
		
		return implode($patternRegex);
	}



	private $originalPattern;				// the raw URL passed to __construct
	private $patternRegex = [];				// the different parts of the regex ; even offsets are constant parts ; uneven offsets are variable names
	private $patternRegexOriginal = [];		// constant parts of $patternRegex but unparsed
	private $variablesList = [];			// the list of variables in the right order
}

?>