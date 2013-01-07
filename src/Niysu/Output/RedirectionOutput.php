<?php
namespace Niysu\Output;

/**
 * Sets the response to a redirection to another resource.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class RedirectionOutput implements \Niysu\OutputInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response, \Niysu\Server $server = null) {
		$this->outputResponse = $response;
		$this->server = $server;
	}

	/**
	 * Sets the location to redirect to.
	 * @param string 	$url 			The URL to redirect to
	 * @param integer 	$statusCode 	(optional) The status code
	 */
	public function setLocation($url, $statusCode = null) {
		$this->location = $url;

		if ($statusCode)
			$this->setStatusCode($statusCode);
	}


	/**
	 * Sets the location to a route of the server.
	 * @param string 	$routeName 		The name of the route
	 * @param array 	$routeParams 	Parameters used to build the route's URL
	 * @param integer 	$statusCode 	(optional) The status code
	 */
	public function setLocationToRoute($routeName, $routeParams = [], $statusCode = null) {
		if (!$this->server)
			throw new \LogicException('No server was passed to constructor');
		
		$route = $this->server->getRouteByName($routeName);
		if (!$route)
			throw new \RuntimeException('This route doesn\'t exist: '.$routeName);
		
		$this->location = $route->getURL($routeParams);
		
		if ($statusCode)
			$this->setStatusCode($statusCode);
	}


	/**
	 * Sets the status code to send to the response.
	 * The status code must be 301, 302, 303 or 307
	 * @param integer 	$statusCode 	The status code
	 * @throws LogicException If the status code is invalid
	 */
	public function setStatusCode($statusCode) {
		if ($statusCode != 301 && $statusCode != 302 && $statusCode != 303 && $statusCode != 307)
			throw new \LogicException('Unvalid redirection status code: '.$statusCode);

		$this->statusCode = $statusCode;
	}


	public function flush() {
		$this->outputResponse->setStatusCode($this->statusCode);
		$this->outputResponse->setHeader('Location', $this->location);
	}


	private $outputResponse;
	private $server;
	private $statusCode = 302;
	private $location = '/';
};

?>