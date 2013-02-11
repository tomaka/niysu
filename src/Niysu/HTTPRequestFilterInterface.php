<?php
namespace Niysu;
require_once __DIR__.'/HTTPRequestInterface.php';

/**
 * Interface which allows to filter the input of an HTTPRequest.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPRequestFilterInterface extends HTTPRequestInterface {
	/**
	 * Sets the informations source of this filter.
	 *
	 * @param HTTPRequestInterface 		$source 		The request where to take informations from
	 */
	public function __construct(HTTPRequestInterface $source) {
		$this->source = $source;
	}

	/**
	 * Returns the HTTPRequestInterface that was passed to the constructor.
	 * @return HTTPRequestInterface
	 */
	public function getInput() {
		return $this->source;
	}

	public function getURL() {
		return $this->source->getURL();
	}

	public function getMethod() {
		return $this->source->getMethod();
	}
	
	public function getHeader($header, $index = 0) {
		return $this->source->getHeader($header, $index);
	}

	public function getHeadersList() {
		return $this->source->getHeadersList();
	}
	
	public function getRawData() {
		return $this->source->getRawData();
	}

	public function isHTTPS() {
		return $this->source->isHTTPS();
	}
	
	private $source;
};
