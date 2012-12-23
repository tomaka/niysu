<?php
namespace Niysu\Filters;

/**
 * Handles everything related to ETag.
 *
 * You can call the "setETag" function before the headers are sent to set the etag of the current resource.
 * If you don't call this function, the etag will automatically be calculated by hashing the data.
 *
 * If the HTTP request gives an If-None-Match with the same etag, this filter returns a 403 and remove all the content.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 * @warning 	Not working yet
 * @todo 		Not working yet
 */
class ETagResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response, \Niysu\HTTPRequestInterface $request) {
		parent::__construct($response);
		$this->requestETag = $request->getHeader('If-None-Match');
	}

	/**
	 * Sets the ETag of the resource.
	 *
	 * If this function is not called, then the ETag will be computed automatically by hashing the content.
	 *
	 * @param string 	$etag 		ETag of the resource
	 */
	public function setETag($etag) {
		if ($this->requestETag == $etag) {
			if (parent::isHeadersListSent())
				throw new \LogicException('Headers list is already sent');

			parent::setHeader('ETag', $etag);
			parent::setStatusCode(304);
		}

		$this->etag = $etag;
		parent::setHeader('ETag', $etag);

		if (!empty($this->dataBuffer))
			parent::appendData($this->dataBuffer);
	}

	public function flush() {
		if (!parent::isHeadersListSent() && !isset($this->etag)) {
			$etag = md5($this->dataBuffer);
			parent::setHeader('ETag', $etag);
			if ($this->requestETag == $etag) {
				parent::setStatusCode(304);
				return;
			}
		}

		parent::appendData($this->dataBuffer);
		$this->dataBuffer = '';
	}

	public function appendData($data) {
		if (empty($data))
			return;

		if (isset($this->etag))		parent::appendData($data);
		else						$this->dataBuffer .= $data;
	}

	public function setStatusCode($code) {
		$this->statusCode = $code;
	}


	private function checkETag() {
		$etag = $this->etag;
		if (!$etag) {
			$etag = md5($this->dataBuffer);
			$this->etag = $etag;
		}

		if ($etag == $this->requestETag) {
			parent::setStatusCode(304);
			return true;
		}

		return false;
	}


	private $requestETag = null;
	private $etag = null;
	private $dataBuffer = '';
	private $headersSent = false;
}
