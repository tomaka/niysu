<?php
namespace Niysu;
require_once __DIR__.'/HTTPResponseInterface.php';

/**
 * Implementation of HTTPResponseInterface which doesn't do anything.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPResponseNull extends HTTPResponseInterface {
	public function flush() {
	}

	public function setStatusCode($statusCode) {
	}

	public function addHeader($header, $value) {
	}

	public function setHeader($header, $value) {
	}

	public function removeHeader($header) {
	}

	public function isHeadersListSent() {
		return false;
	}

	public function appendData($data) {
	}
}

?>