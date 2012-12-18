<?php
namespace Niysu;

class HTTPRequestCustomTest extends \PHPUnit_Framework_TestCase {
	public function testConstructor() {
		$request = new HTTPRequestCustom('/', 'GET', [ 'Content-Type' => 'text' ], 'test', true);

		$this->assertEquals($request->getURL(), '/');
		$this->assertEquals($request->getMethod(), 'GET');
		$this->assertEquals($request->getHeader('Content-Type'), 'text');
		$this->assertEquals(count($request->getHeadersList()), 1);
		$this->assertEquals($request->getRawData(), 'test');
		$this->assertEquals($request->isHTTPS(), true);
	}
};

?>