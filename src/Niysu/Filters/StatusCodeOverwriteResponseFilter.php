<?php
namespace Niysu\Filters;

/**
 * Doesn't do anything except replace the status code by a predefined code.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class StatusCodeOverwriteResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;

	public function __construct(\Niysu\HTTPResponseInterface $response, $code) {
		$this->outputResponse = $response;
		$response->setStatusCode($code);
	}
	
	public function setStatusCode($statusCode) {
	}
}
