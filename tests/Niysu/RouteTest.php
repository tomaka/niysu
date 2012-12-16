<?php
namespace Niysu;

class RouteTest extends \PHPUnit_Framework_TestCase {
	public function testPatternMatchSingle() {
		$route = new Route('/{var}', 'get', function() {});
		$response = new HTTPResponseStorage();
		
		$this->assertTrue($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/test') ])));
		$this->assertTrue($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/1') ])));
		$this->assertFalse($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/') ])));
		$this->assertFalse($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/test-test') ])));
		$this->assertFalse($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/test/test') ])));
	}

	/**
     * @depends testPatternMatchSingle
     */
	public function testPatternMatchMultiple() {
		$route = new Route('/{var1}/{var2}', 'get', function() {});
		$response = new HTTPResponseStorage();
		
		$this->assertFalse($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/test') ])));
		$this->assertFalse($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/1') ])));
		$this->assertFalse($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('//') ])));
		$this->assertTrue($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/test/test') ])));
		$this->assertFalse($route->handle(new Scope([ 'response' => $response, 'request' => new HTTPRequestCustom('/test/test/x') ])));
	}

	public function testMethodMatch() {
		$response = new HTTPResponseStorage();
		$scope = new Scope([ 'response' => $response ]);

		$route = new Route('/', 'test', function() {});

		$scope->request = new HTTPRequestCustom('/', 'get');
		$this->assertFalse($route->handle($scope));

		$scope->request = new HTTPRequestCustom('/', 'post');
		$this->assertFalse($route->handle($scope));

		$scope->request = new HTTPRequestCustom('/', 'test');
		$this->assertTrue($route->handle($scope));
	}
};

?>