<?php
namespace Niysu;
require_once __DIR__.'/HTTPResponseInterface.php';

/**
 * Implementation of HTTPResponse which acts as a filter.
 *
 * It will automatically pass anything to an output response defined in the constructor.
 * You must overload the functions you want to change if you want it to filter anything.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPResponseFilter extends HTTPResponseInterface {
	/**
	 * Sets the output response.
	 *
	 * @param HTTPResponseInterface 	$output 	The output response where everything will be redirected by default
	 */
	public function __construct(HTTPResponseInterface $output) {
		if (!$output)
			throw new \LogicException('Filter output is null');
		$this->output = $output;
	}

	public function flush() {
		$this->output->flush();
	}
	
	public function setStatusCode($statusCode) {
		$this->output->setStatusCode($statusCode);
	}

	public function addHeader($header, $value) {
		$this->output->addHeader($header, $value);
	}

	public function setHeader($header, $value) {
		$this->output->setHeader($header, $value);
	}

	public function removeHeader($header) {
		$this->output->removeHeader($header);
	}

	public function isHeadersListSent() {
		return $this->output->isHeadersListSent();
	}

	public function appendData($data) {
		$this->output->appendData($data);
	}

	/**
	 * Returns the output response
	 *
	 * @see __construct
	 * @return HTTPResponseInterface
	 */
	protected function getOutput() {
		return $this->output;
	}


	private $output = null;
	private $mustFlush = false;
};

?>