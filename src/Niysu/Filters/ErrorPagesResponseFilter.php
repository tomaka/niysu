<?php
namespace Niysu\Filters;

/**
 * Allows you to customize error pages.
 *
 * By default this filter will replace any response whose status code is 400 or more by a route whose name is the status code.
 * For example if your server returns a 404 response and there exists a route named "404", then this route will be called and will replace the response.
 *
 * You can overwrite this behavior by calling "setErrorRoute".
 *
 * Note that the status code of the final response will always be the same that triggered the filter.
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
		$route = null;

		if (!$this->currentReplacement) {
			if ($this->currentStatusCode >= 400)
				$route = $this->server->getRouteByName($this->currentStatusCode);

		} else {
			$route = $this->server->getRouteByName($this->currentReplacement);

			if (!$route) {
				if ($this->log)
					$this->log->warn('Route specified in ErrorPagesResponseFilter does not exist: '.$this->currentReplacement);
			}
		}

		if (isset($route)) {
			if ($this->log)
				$this->log->debug('ErrorPagesResponseFilter will now replace the response by the route: '.$route->getName());

			$request = $this->request;
			$response = new StatusCodeOverwriteResponseFilter($this->outputResponse, $this->currentStatusCode);
			$scope = $this->server->generateQueryScope();

			$route->handleNoURLCheck($request, $response, $scope);
			if (isset($scope->output) && $scope->output instanceof \Niysu\OutputInterface) {
				$this->log->debug('Flushing the OutputInterface object from within ErrorPagesResponseFilter');
				$scope->output->flush();
			} else {
				$this->log->debug('No OutputInterface object has been found within ErrorPagesResponseFilter');
			}
			$this->log->debug('Flushing the updated HTTPResponseInterface (with filters) from within ErrorPagesResponseFilter');
			$response->flush();

		} else {
			$this->outputResponse->flush();
			return;
		}
	}

	public function appendData($data) {
		$this->headersSent = true;
		if (!$this->currentReplacement)
			$this->outputResponse->appendData($data);
	}

	public function isHeadersListSent() {
		return $this->headersSent || $this->outputResponse->isHeadersListSent();
	}


	private $server;
	private $request;
	private $log;
	private $errorReplacements = [];
	private $currentStatusCode;
	private $currentReplacement = null;
	private $headersSent = false;
}
