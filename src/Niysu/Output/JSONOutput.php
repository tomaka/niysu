<?php
namespace Niysu\Output;

/**
 * Send CSV data to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class JSONOutput implements \Niysu\OutputInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response) {
		$this->response = $response;
	}

	
	/**
	 * Sets the JSON data to send when the response is sent.
	 *
	 * @param mixed 	$json 		Data representing JSON
	 */
	public function setData($json) {
		$this->data = json_encode($json);
		if ($this->data === null) {
			switch (json_last_error()) {
				case JSON_ERROR_DEPTH:				throw new \RuntimeException('json_encode() returned JSON_ERROR_DEPTH');
				case JSON_ERROR_STATE_MISMATCH:		throw new \RuntimeException('json_encode() returned JSON_ERROR_STATE_MISMATCH');
				case JSON_ERROR_CTRL_CHAR:			throw new \RuntimeException('json_encode() returned JSON_ERROR_CTRL_CHAR');
				case JSON_ERROR_SYNTAX:				throw new \RuntimeException('json_encode() returned JSON_ERROR_SYNTAX');
				case JSON_ERROR_UTF8:				throw new \RuntimeException('json_encode() returned JSON_ERROR_UTF8');
			}
		}
	}

	public function flush() {
		if (!$this->response->isHeadersListSent())
			$this->response->setHeader('Content-Type', 'application/json');
		$this->response->appendData($this->data);
	}


	private $data;
	private $response;
};

?>