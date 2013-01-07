<?php
namespace Niysu;

/**
 */
class HTTPResponseGlobalTest extends \PHPUnit_Framework_TestCase {
	protected function setUp() {
        parent::setUp();
        ob_start();
    }

    protected function tearDown() {
        header_remove();
        parent::tearDown();
    }

	/**
 	 * @runInSeparateProcess
	 */
	public function testAppendDataString() {
		$response = new HTTPResponseGlobal();
		$this->expectOutputString('ABC');
		$response->appendData('ABC');
		$response->flush();
	}

	/**
 	 * @runInSeparateProcess
	 */
	public function testAppendDataBinary() {
		$binaryData = pack("nvc*", 0x1234, 0x0201, 65, 66);

		$response = new HTTPResponseGlobal();
		$this->expectOutputString($binaryData);

		$response->appendData($binaryData);
		$response->flush();
	}

	/**
 	 * @runInSeparateProcess
 	 */
	public function testSetStatusCode() {
		$response = new HTTPResponseGlobal();

		$response->setStatusCode(404);
		$this->assertEquals(404, http_response_code());
		
		$response->setStatusCode(402);
		$this->assertEquals(402, http_response_code());
		
		$response->setStatusCode(302);
		$this->assertEquals(302, http_response_code());

		$response->flush();
	}

	/**
 	 * @runInSeparateProcess
 	 */
	public function testSetHeader() {
		$this->markTestIncomplete();
		// this test fails but I have no idea why
		// when I reproduce it on a custom script, it works perfectly
		// maybe headers_list() is not working with runInSeparateProcess?

		$response = new HTTPResponseGlobal();

		$response->setHeader('Content-Type', 'image/png');
		$response->setHeader('Location', '/');
		$response->setHeader('ETag', 'test');
		$response->flush();

		$this->assertContains('Content-Type: image/png', headers_list());
		$this->assertContains('Location: /', headers_list());
		$this->assertContains('ETag: test', headers_list());
	}

	/**
 	 * @runInSeparateProcess
 	 */
	public function testAddHeader() {
		$this->markTestIncomplete();
		// this test fails but I have no idea why
		// when I reproduce it on a custom script, it works perfectly
		// maybe headers_list() is not working with runInSeparateProcess?

		$response = new HTTPResponseGlobal();

		$response->addHeader('X-Test', 'value1');
		$response->addHeader('X-Test', 'value2');
		$response->addHeader('X-Test', 'value3');
		$response->flush();

		$this->assertContains('X-Test: value1', headers_list());
		$this->assertContains('X-Test: value2', headers_list());
		$this->assertContains('X-Test: value3', headers_list());
	}

	/**
 	 * @runInSeparateProcess
 	 * @depends testSetHeader
 	 */
	public function testRemoveHeaderWithSingleHeader() {
		$response = new HTTPResponseGlobal();

		$response->setHeader('Content-Type', 'image/png');
		$response->removeHeader('Content-Type');
		$response->flush();

		$this->assertNotContains('Content-Type: image/png', headers_list());
	}

	/**
 	 * @runInSeparateProcess
 	 * @depends testAddHeader
 	 */
	public function testRemoveHeaderWithMultipleHeaders() {
		$response = new HTTPResponseGlobal();

		$response->addHeader('X-Test', 'value1');
		$response->addHeader('X-Test', 'value2');
		$response->addHeader('X-Test', 'value3');
		$response->removeHeader('X-Test');
		$response->flush();

		$this->assertNotContains('X-Test: value1', headers_list());
		$this->assertNotContains('X-Test: value2', headers_list());
		$this->assertNotContains('X-Test: value3', headers_list());
	}
};

?>