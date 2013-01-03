<?php
namespace Niysu\Filters;

/**
 * Allows you to customize error pages.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 * @warning 	Not working yet
 * @todo 		Not working yet
 */
class ErrorPagesResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;
	
	public function __construct(\Niysu\HTTPResponseInterface $response, \Niysu\Server $server = null, \Niysu\HTTPRequestInterface $request, \Monolog\Logger $log = null) {
		$this->outputResponse = $response;
		$this->server = $server;
		$this->request = $request;
		$this->log = $log;
	}

	/**
	 * Sets the route to call as a replacement if a response with this status code is detected.
	 *
	 * If the response receives the given error code, then the route will be called and erase everything except the status code.
	 * The errorCode parameter can be either a number or an array of numbers.
	 *
	 * @param mixed 	$errorCode 		The error code or codes that will trigger the replacement
	 * @param string 	$routeName 		Name of the route to be called
	 */
	public function setErrorRoute($errorCode, $routeName) {
		$this->errorReplacements[$errorCode] = $routeName;
	}

	public function setStatusCode($statusCode) {
		$this->currentStatusCode = $statusCode;
		if ($this->server && isset($this->errorReplacements[$statusCode]))
			$this->currentReplacement = $this->errorReplacements[$statusCode];

		$this->outputResponse->setStatusCode($statusCode);
	}

	public function flush() {
		if (!$this->currentReplacement) {
			$this->outputResponse->flush();
			return;
		}

		if ($this->log)
			$this->log->debug('ErrorPagesResponseFilter replaces resource by the route: '.$this->currentReplacement);

		$route = $this->server->getRouteByName($this->currentReplacement);

		if ($route) {
			$response = new StatusCodeOverwriteResponseFilter($this->outputResponse, $this->currentStatusCode);
			$route->handleNoURLCheck($this->request, $response, $this->server->generateQueryScope());
			$response->flush();

		} else {
			if ($this->log)
				$this->log->warn('Route specified in ErrorPagesResponseFilter does not exist: '.$this->currentReplacement);
			$this->outputResponse->flush();
		}
	}

	public function appendData($data) {
		$this->headersSent = true;
		if (!$this->currentReplacement)
			$this->outputResponse->appendData($data);
	}

	public function isHeadersListSent() {
		return $this->headersSent;
	}


	private $server;
	private $request;
	private $log;
	private $errorReplacements = [];
	private $currentStatusCode;
	private $currentReplacement = null;
	private $headersSent = false;
}
