<?php
namespace Niysu;

class HTTPResponseStorageTest extends \PHPUnit_Framework_TestCase {
	public function testAppendData() {
		$response = new HTTPResponseStorage();
		$this->assertEquals($response->getData(), '');

		$response->appendData('ABC');
		$this->assertEquals($response->getData(), 'ABC');

		$response->appendData('DEF');
		$this->assertEquals($response->getData(), 'ABCDEF');
	}

	public function testSetStatusCode() {
		$response = new HTTPResponseStorage();
		$this->assertGreaterThanOrEqual($response->getStatusCode(), 200);
		$this->assertLessThan(300, $response->getStatusCode());
		
		$response->setStatusCode(301);
		$this->assertEquals($response->getStatusCode(), 301);
		
		$response->setStatusCode(404);
		$this->assertEquals($response->getStatusCode(), 404);
		
		$response->setStatusCode(500);
		$this->assertEquals($response->getStatusCode(), 500);
	}

	public function testSetHeader() {
		$response = new HTTPResponseStorage();
		$this->assertFalse($response->hasHeader('Content-Type'));
		$response->setHeader('Content-Type', 'text/html');
		$this->assertTrue($response->hasHeader('Content-Type'));
		$this->assertEquals($response->getHeader('Content-Type'), 'text/html');
	}

	public function testRemoveHeader() {
		$response = new HTTPResponseStorage();
		$this->assertFalse($response->hasHeader('Content-Type'));

		$response->setHeader('Content-Type', 'text/html');
		$this->assertTrue($response->hasHeader('Content-Type'));

		$response->removeHeader('Content-Type');
		$this->assertFalse($response->hasHeader('Content-Type'));
	}
};

?>