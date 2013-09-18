<?php
namespace Niysu;
require_once __DIR__.'/HTTPRequestInterface.php';

/**
 * Implementation of HTTPRequestInterface that reads request data from a stream.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class HTTPRequestFromStream extends HTTPRequestInterface {
	public function __construct($stream, $isHTTPS = false) {
		$this->isHTTPS = $isHTTPS;
		$this->stream = $stream;
	}

	public function getURL() {
		if (isset($this->url))
			return $this->url;

		$this->readFirstLine();
		return $this->url;
	}

	public function getMethod() {
		if (isset($this->method))
			return $this->method;

		$this->readFirstLine();
		return $this->method;
	}

	public function getHeader($header, $index = 0) {
		if (!$this->headersList)
			$this->readHeaders();

		foreach ($this->headersList as $key => $val) {
			if (strtolower($key) == strtolower($header)) {
				if ($index-- == 0)
					return $val;
			}
		}

		return null;
	}

	public function getHeadersList() {
		if (!$this->headersList)
			$this->readHeaders();

		return $this->headersList;
	}

	public function getRawData() {
		if (!$this->headersList)
			$this->readHeaders();

		$length = $this->getHeader('Content-Length');
		if ($length == null)
			throw new \RuntimeException('Could not determine input length');

		do {
			$lengthRemaining = $length - strlen($data);
			if (!$lengthRemaining)
				break;

			$this->data .= fread($this->stream, $lengthRemaining);
			if (strlen($this->data) == $length)
				break;

		} while (true);

		return $this->data;
	}

	public function isHTTPS() {
		return $this->isHTTPS;
	}


	private function readFirstLine() {
		$firstLine = stream_get_line($this->stream, 1024, "\r\n");
		if (preg_match('/^(\\S+)\\s+(\\S+)\\s+(.+)$/', $firstLine, $matches)) {
			$this->method = $matches[1];
			$this->url = $matches[2];

		} else {
			throw new \RuntimeException('Wrong HTTP format');
		}
	}

	private function readHeaders() {
		if (!$this->url)
			$this->readFirstLine();
		$this->headersList = [];

		$nextHeader = stream_get_line($this->stream, 1024, "\r\n");

		if (empty($nextHeader)) {
			// finished reading
			return;

		} else if (preg_match('/^(\\w+)\\s*:\\s*(.+)$/', $nextHeader, $matches)) {
			$this->headersList[$matches[1]] = trim($matches[2]);

		} else {
			throw new \RuntimeException('Wrong HTTP format');
		}
	}


	private $stream;
	private $isHTTPS;
	private $headersList = null;
	private $method = null;
	private $url = null;
	private $data = '';
}
