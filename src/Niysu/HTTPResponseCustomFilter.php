<?php
namespace Niysu;

class HTTPResponseCustomFilter extends HTTPResponseFilter {	
	public function __construct(HTTPResponseInterface $output, $contentCallback) {
		parent::__construct($output);
		$this->setContentCallback($contentCallback);
	}
	
	public function __destruct() {
		$data = '';
		foreach ($this->headersList as $h => $v)
			$data .= $h.':'.$v."\r\n";
		$data .= "\r\n";
		$data .= $this->dataBuffer;
		$this->dataBuffer = '';

		// calling callback
		$f = $this->contentCallback;
		if ($f)		$out = $f($data);
		else 		$out = $data;
		
		// extracting headers and content
		$dataPos = strpos($out, "\r\n\r\n");
		$headers = explode("\r\n", substr($out, 0, $dataPos));
		foreach ($headers as $h) {
			$pos = strpos($h, ':');
			$this->getOutput()->setHeader(substr($h, 0, $pos), substr($h, $pos + 1));
		}
		$this->getOutput()->appendData(substr($out, $dataPos + 4));

		parent::__destruct();
	}

	public function appendData($data) {
		$this->dataBuffer .= $data;
	}
	
	public function addHeader($header, $value) {
		$this->headersList[$header] = $value;
	}

	public function setHeader($header, $value) {
		$this->headersList[$header] = $value;
	}

	public function setContentCallback($contentCallback) {
		if ($contentCallback != null && !is_callable($contentCallback))
			throw new \LogicException('Content callback must be callable');
		$this->contentCallback = $contentCallback;
	}
	
	private $dataBuffer = '';
	private $alreadyDestroyed = false;
	private $contentCallback;
	private $headersList = [];
};

?>