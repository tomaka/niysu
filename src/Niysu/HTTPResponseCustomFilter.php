<?php
namespace Niysu;

class HTTPResponseCustomFilter extends HTTPResponseFilter {	
	public function __construct(HTTPResponseInterface $output, $contentCallback) {
		parent::__construct($output);
		$this->httpStorage = new HTTPResponseStorage();
		$this->setContentCallback($contentCallback);
	}
	
	public function __destruct() {
		// calling callback
		call_user_func($this->contentCallback, $this->httpStorage);

		// 
		if ($this->getOutput()->isHeadersListSent())
			throw new \LogicException('Problem while flushing HTTPResponseCustomFilter: the next output has already sent headers list');
		
		// sending everything to next filter
		$this->getOutput()->setStatusCode($this->httpStorage->getStatusCode());
		foreach ($this->httpStorage->getHeadersList() as $h => $v)
			$this->getOutput()->setHeader($h, $v);
		$this->getOutput()->appendData($this->httpStorage->getData());

		// 
		parent::__destruct();
	}

	public function appendData($data) {
		$this->httpStorage->appendData($data);
	}
	
	public function addHeader($header, $value) {
		$this->httpStorage->addHeader($header, $value);
	}

	public function setHeader($header, $value) {
		$this->httpStorage->setHeader($header, $value);
	}

	public function removeHeader($header) {
		$this->httpStorage->removeHeader($header);
	}

	public function setStatusCode($code) {
		$this->httpStorage->setStatusCode($code);
	}

	public function setContentCallback($contentCallback) {
		if ($contentCallback != null && !is_callable($contentCallback))
			throw new \LogicException('Content callback must be callable');
		$this->contentCallback = $contentCallback;
	}
	
	private $httpStorage;
	private $contentCallback;
};

?>