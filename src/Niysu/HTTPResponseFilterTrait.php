<?php
namespace Niysu;
require_once __DIR__.'/HTTPResponseInterface.php';

/**
 * Trait that can be used by implementation of ResponseInterface that only want to filter some things.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
trait HTTPResponseFilterTrait {
	public function flush() {
		$this->outputResponse->flush();
	}
	
	public function setStatusCode($statusCode) {
		$this->outputResponse->setStatusCode($statusCode);
	}

	public function addHeader($header, $value) {
		$this->outputResponse->addHeader($header, $value);
	}

	public function setHeader($header, $value) {
		$this->outputResponse->setHeader($header, $value);
	}

	public function removeHeader($header) {
		$this->outputResponse->removeHeader($header);
	}

	public function isHeadersListSent() {
		return $this->outputResponse->isHeadersListSent();
	}

	public function appendData($data) {
		$this->outputResponse->appendData($data);
	}


	private $outputResponse = null;
};

?>