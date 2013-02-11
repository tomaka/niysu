<?php
namespace Niysu;
require_once __DIR__.'/HTTPResponseFilterInterface.php';

/**
 * Implementation of HTTPResponseFilterInterface which will store everything in a HTTPResponseStorage.
 *
 * When you flush, a callback will be called and can change the content of the HTTPResponseStorage.
 * Then the content of the HTTPResponseStorage is output to the output response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPResponseCustomFilter implements HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;

	/**
	 * 
	 * @param HTTPResponseInterface 	$output 			The output response, where everything will be sent after filtering
	 * @param callable 					$contentCallback 	Callback which takes as parameter a HTTPResponseStorage
	 */
	public function __construct(HTTPResponseInterface $output, $contentCallback) {
		$this->outputResponse->__construct($output);
		$this->httpStorage = new HTTPResponseStorage();
		$this->setContentCallback($contentCallback);
	}

	/**
	 * Calls the callback defined in the constructor and flushes.
	 *
	 * This function will call the callback defined in the constructor with the HTTPResponseStorage as parameter.
	 * Then it will read the HTTPResponseStorage and output it to the output response.
	 */
	public function flush() {
		// calling callback
		if ($this->contentCallback)
			call_user_func($this->contentCallback, $this->httpStorage);

		// sending everything to next filter
		if (!$this->getOutput()->isHeadersListSent())
			$this->getOutput()->setStatusCode($this->httpStorage->getStatusCode());
		foreach ($this->httpStorage->getHeadersList() as $h => $v)
			$this->getOutput()->setHeader($h, $v);

		$this->getOutput()->appendData($this->httpStorage->getData());
		$this->httpStorage = new HTTPResponseStorage();

		// 
		$this->outputResponse->flush();
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

	/**
	 * Changes the content callback.
	 *
	 * @param callable 		$contentCallback 		See __construct
	 */
	public function setContentCallback($contentCallback) {
		if ($contentCallback != null && !is_callable($contentCallback))
			throw new \LogicException('Content callback must be callable');
		$this->contentCallback = $contentCallback;
	}
	
	private $httpStorage;
	private $contentCallback;
};
