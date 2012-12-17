<?php
namespace Niysu;

/// \brief Filter for an HTTP response
class HTTPResponseFilter extends HTTPResponseInterface {
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

	protected function getOutput() {
		return $this->output;
	}


	private $output = null;
	private $mustFlush = false;
};

?>