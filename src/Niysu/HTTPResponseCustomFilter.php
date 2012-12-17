<?php
namespace Niysu;

class HTTPResponseCustomFilter extends HTTPResponseFilter {	
	public function __construct(HTTPResponseInterface $output, $contentCallback) {
		parent::__construct($output);
		$this->httpStorage = new HTTPResponseStorage();
		$this->setContentCallback($contentCallback);
	}

	public function flush() {
		// calling callback
		if ($this->contentCallback)
			call_user_func($this->contentCallback, $this->httpStorage);

		// sending everything to next filter
		if (!$this->getOutput()->isHeadersListSent()) {
			$this->getOutput()->setStatusCode($this->httpStorage->getStatusCode());
			foreach ($this->httpStorage->getHeadersList() as $h => $v)
				$this->getOutput()->setHeader($h, $v);
		}

		$this->getOutput()->appendData($this->httpStorage->getData());
		$this->httpStorage = new HTTPResponseStorage();

		// 
		parent::flush();
	}

	public function appendData($data) {
		/*if (empty($this->httpStorage->getData()))
			$this->flush();*/

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