<?php
namespace Niysu;

/**
 */
class HTTPResponseStreamTest extends \PHPUnit_Framework_TestCase {
	/**
	 */
	public function testWriteOnlyData() {
		$response = new HTTPResponseStorage();

		$binaryData = pack("nvc*", 0x1234, 0x0201, 65, 66);

		$stream = HTTPResponseStream::build($response, false);
		$stream->fwrite('AB');
		$stream->fwrite($binaryData);
		$stream->fwrite('C');

		$this->assertEquals('AB'.$binaryData.'C', $response->getData());
	}

	/**
	 */
	public function testWriteHeaders() {
		$response = new HTTPResponseStorage();
		$stream = HTTPResponseStream::build($response, true);

		$stream->fwrite('Content-Type: text/html'."\r\n");
		$stream->fwrite('ETag: test'."\r\n");
		$stream->fwrite("\r\n");
		$stream->fwrite('ABC');

		$this->assertTrue($response->hasHeader('Content-Type'));
		$this->assertEquals('text/html', $response->getHeader('Content-Type'));
		$this->assertTrue($response->hasHeader('ETag'));
		$this->assertEquals('test', $response->getHeader('ETag'));
		$this->assertEquals('ABC', $response->getData());
	}

	/**
	 */
	public function testWriteStatusCode() {
		$response = new HTTPResponseStorage();
		$stream = HTTPResponseStream::build($response, true);

		$stream->fwrite('HTTP/1.1 404 Not Found'."\r\n");
		$stream->fwrite('ETag: test'."\r\n");
		$stream->fwrite("\r\n");
		$stream->fwrite('ABC');

		$this->assertEquals(404, $response->getStatusCode());

		$this->assertTrue($response->hasHeader('ETag'));
		$this->assertEquals('test', $response->getHeader('ETag'));
		$this->assertEquals('ABC', $response->getData());
	}

	/**
	 * @depends testWriteStatusCode
	 */
	public function testWriteStatusHeader() {
		$response = new HTTPResponseStorage();
		$stream = HTTPResponseStream::build($response, true);

		$stream->fwrite('HTTP/1.1 404 Not Found'."\r\n");
		$stream->fwrite('ETag: test'."\r\n");
		$stream->fwrite('Status: 301'."\r\n");
		$stream->fwrite("\r\n");
		$stream->fwrite('ABC');

		$this->assertEquals(301, $response->getStatusCode());

		$this->assertTrue($response->hasHeader('ETag'));
		$this->assertEquals('test', $response->getHeader('ETag'));
		$this->assertEquals('ABC', $response->getData());
	}

	/**
	 */
	public function testEmptyHeaders() {
		$response = new HTTPResponseStorage();
		$stream = HTTPResponseStream::build($response, true);
		
		$stream->fwrite("\r\n");
		$stream->fwrite('ABC');
		
		$this->assertEquals('ABC', $response->getData());
	}

	/**
	 * @depends testWriteHeaders
	 * @depends testWriteStatusHeader
	 */
	public function testWriteAllAtOnce() {
		$response = new HTTPResponseStorage();
		$stream = HTTPResponseStream::build($response, true);

		$stream->fwrite('HTTP/1.1 302 See Other'."\r\n".'Content-Type: text/html'."\r\n".'ETag: test'."\r\n\r\n".'ABC');

		$this->assertEquals(302, $response->getStatusCode());
		$this->assertTrue($response->hasHeader('Content-Type'));
		$this->assertEquals('text/html', $response->getHeader('Content-Type'));
		$this->assertTrue($response->hasHeader('ETag'));
		$this->assertEquals('test', $response->getHeader('ETag'));
		$this->assertEquals('ABC', $response->getData());
	}
};

?>