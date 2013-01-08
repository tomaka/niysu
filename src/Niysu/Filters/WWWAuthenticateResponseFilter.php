<?php
namespace Niysu\Filters;

/**
 * If the response contains a 401 status code, this filter will send along a WWW-Authenticate header
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class WWWAuthenticateResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;

	public function __construct(\Niysu\HTTPResponseInterface $response) {
		$this->outputResponse = $response;
	}
	
	public function setStatusCode($statusCode) {
		if ($statusCode == 401)		$this->outputResponse->setHeader('WWW-Authenticate', 'Basic realm="realm"');
		else 						$this->outputResponse->removeHeader('WWW-Authenticate');
		$this->outputResponse->setStatusCode($statusCode);
	}
}
