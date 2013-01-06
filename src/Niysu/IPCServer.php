<?php
namespace Niysu;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class IPCServer {
	public function __construct(Server $server, $address) {
		$this->niysuServer = $server;
		$this->bindAddress = $address;
	}

	public function run() {
		if (ini_get('max_execution_time') != 0)
			throw new \LogicException('PHP must have no max_execution_time in order to start a server');

		if (($this->socket = stream_socket_server(self::getTransport(), $errNo, $errString)) === false)
			throw new \RuntimeException('Could not create listening socket: '.$errString);

		do {
			$readableSockets = [ $this->socket ];
			$write = null;
			$except = null;
			stream_select($readableSockets, $write, $except, 5);

			foreach ($readableSockets as $socket) {
				if ($socket === $this->socket) {
					$newSocket = stream_socket_accept($this->socket);

					try {
						$request = new HTTPRequestFromStream($newSocket);
						$response = new HTTPResponseToStream($newSocket);
						$response->setHeader('Server', 'Niysu IPC server');
						$response->setHeader('Connection', 'close');
						$this->niysuServer->handle($request, $response);
						stream_socket_shutdown($newSocket, STREAM_SHUT_RDWR);

					} catch(\Exception $e) {
						fwrite($newSocket, 'HTTP/1.1 500 Internal Server Error'."\r\n");
						fwrite($newSocket, 'Server: Niysu IPC server'."\r\n\r\n");
						fflush($newSocket);
						stream_socket_shutdown($newSocket, STREAM_SHUT_RDWR);
					}
				}
			}
		} while(true);

		stream_socket_shutdown($this->socket, STREAM_SHUT_RDWR);
	}


	public static function getTransport() {
		$transport = 'unix://'.__DIR__.'/socket';
		if (array_search('unix', stream_get_transports()) === false)
			$transport = 'tcp://127.0.0.1:80';
		return $transport;
	}


	private $niysuServer;			// Niysu\Server
	private $socket;				// socket
	private $bindAddress = null;
}

?>