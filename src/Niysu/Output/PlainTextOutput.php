<?php
namespace Niysu\Output;

/**
 * Send plain text to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class PlainTextOutput implements \Niysu\OutputInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response) {
		$this->outputResponse = $response;
	}

	
	/**
	 * Sets the text to send.
	 *
	 * @param string 	$text 		Plain-text data
	 */
	public function setText($text) {
		$this->data = $text;
	}

	public function flush() {
		if (!$this->data)
			return;

		$this->outputResponse->setHeader('Content-Length', strlen($this->data));
		$this->outputResponse->setHeader('Content-Type', 'text/plain; charset=utf8');
		$this->outputResponse->appendData($this->data);
	}


	private $outputResponse;
	private $data = null;
};
