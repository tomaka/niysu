<?php
namespace Niysu\Filters;

/**
 * Send plain text to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class PlainTextResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;

	public function __construct(\Niysu\HTTPResponseInterface $next) {
		$this->outputResponse = $next;
		$this->setHeader('Content-Type', 'text/plain; charset=utf8');
	}

	
	/**
	 * Sets the text to send.
	 *
	 * @param string 	$text 		Plain-text data
	 */
	public function setText($text) {
		$this->outputResponse->appendData($text);
	}

	public function appendData($data) {
	}
};

?>