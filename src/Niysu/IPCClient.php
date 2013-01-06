<?php
namespace Niysu;

/**
 * Allows you to connect to an IPC server.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class IPCClient {
	/**
	 * Constructor.
	 * Does nothing except configure the IPC server.
	 * 
	 * The address parameter must be a socket transport. See http://php.net/manual/en/transports.php
	 * It must be the same as the value configured in the IPC server.
	 *
	 * @param string 	$address 	An address to pass to stream_socket_client
	 */
	public function __construct($address) {
		$this->bindAddress = $address;
	}

	/**
	 * Handles the request by calling the IPC server.
	 *
	 * This function will connect to the IPC server, send the request, and copy what the server sends back to the response.
	 *
	 * @param HTTPRequestInterface 		$httpRequest		The request to handle (if null, an instance of HTTPRequestGlobal)
	 * @param HTTPResponseInterface 	$httpResponse		The response where to write the output (if null, an instance of HTTPResponseGlobal)
	 */
	public function handle(HTTPRequestInterface $httpRequest = null, HTTPResponseInterface $httpResponse = null) {
		if (!$httpRequest)		$httpRequest = new HTTPRequestGlobal();
		if (!$httpResponse)		$httpResponse = new HTTPResponseGlobal();

		if (($this->socket = stream_socket_client($this->bindAddress, $errNo, $errString)) === false)
			throw new \RuntimeException('Could not create client socket: '.$errString);

		// writing request line
		$toWrite = $httpRequest->getMethod().' '.$httpRequest->getURL().' HTTP/1.1'."\r\n";		// TODO: HTTP version
		fwrite($this->socket, $toWrite);

		// writing headers
		foreach ($httpRequest->getHeadersList() as $header => $value) {
			$toWrite = $header.': '.$value."\r\n";
			fwrite($this->socket, $toWrite);
		}

		// writing data
		$toWrite = "\r\n".$httpRequest->getRawData();
		fwrite($this->socket, $toWrite);
		stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
		fflush($this->socket);

		// reading response
		$response = HTTPResponseStream::build($httpResponse, true);
		$response->fwrite(stream_get_contents($this->socket));
		$response->fflush();
		unset($response);

		stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);

		$httpResponse->flush();
	}


	private $socket;			// socket
}

?>