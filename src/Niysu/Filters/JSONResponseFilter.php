<?php
namespace Niysu\Filters;

/**
 * Send CSV data to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class JSONResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $next) {
		parent::__construct($next);
		$this->setHeader('Content-Type', 'application/json');
	}

	
	/**
	 * Sets the JSON data to send when the response is sent.
	 *
	 * @param mixed 	$json 		Data representing JSON
	 */
	public function setData($json) {
		$this->jsonData = $json;
	}

	public function flush() {
		$data = json_encode($this->jsonData);
		if ($data == null) {
			switch (json_last_error()) {
				case JSON_ERROR_NONE:				break;		// the input data is the null value	; this is legitimate
				case JSON_ERROR_DEPTH:				throw new \RuntimeException('json_encode() returned JSON_ERROR_DEPTH');
				case JSON_ERROR_STATE_MISMATCH:		throw new \RuntimeException('json_encode() returned JSON_ERROR_STATE_MISMATCH');
				case JSON_ERROR_CTRL_CHAR:			throw new \RuntimeException('json_encode() returned JSON_ERROR_CTRL_CHAR');
				case JSON_ERROR_SYNTAX:				throw new \RuntimeException('json_encode() returned JSON_ERROR_SYNTAX');
				case JSON_ERROR_UTF8:				throw new \RuntimeException('json_encode() returned JSON_ERROR_UTF8');
			}
		}

		parent::appendData($data);
		parent::flush();
	}

	public function appendData($data) {
	}


	private $jsonData;
};

?>