<?php
namespace Niysu;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class IPCClient {
	public function handle(HTTPRequestInterface $httpRequest = null, HTTPResponseInterface $httpResponse = null) {
		if (!$httpRequest)		$httpRequest = new HTTPRequestGlobal();
		if (!$httpResponse)		$httpResponse = new HTTPResponseGlobal();

		if (($this->socket = stream_socket_client(IPCServer::getTransport(), $errNo, $errString)) === false)
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
		stream_socket_shutdown($this->socket, STREAM_SHUT_RD);
		fflush($this->socket);

		// reading response
		$response = HTTPResponseStream::build($httpResponse, true);
		$response->fwrite(stream_get_contents($this->socket));
		unset($response);

		stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);

		$httpResponse->flush();
	}




	private $niysuServer;		// Niysu\Server
	private $socket;		// socket
}

?>