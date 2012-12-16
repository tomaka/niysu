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

	public function testAnyMethodMatch() {
		$this->markTestIncomplete();

		$response = new HTTPResponseStorage();
		$scope = new Scope([ 'response' => $response ]);
		$route = new Route('/', '*', function() {});

		$scope->request = new HTTPRequestCustom('/', 'get');
		$this->assertTrue($route->handle($scope));
		$scope->request = new HTTPRequestCustom('/', 'post');
		$this->assertTrue($route->handle($scope));
		$scope->request = new HTTPRequestCustom('/', 'test-test/test@test');
		$this->assertTrue($route->handle($scope));
	}

	public function testPatternFunction() {
		$response = new HTTPResponseStorage();
		$scope = new Scope([ 'response' => $response ]);
		$route = new Route('/{var}', 'get', function() {});
		$route->pattern('var', '\\d+');

		$scope->request = new HTTPRequestCustom('/', 'post');
		$this->assertFalse($route->handle($scope));
		$scope->request = new HTTPRequestCustom('/3', 'get');
		$this->assertTrue($route->handle($scope));
		$scope->request = new HTTPRequestCustom('/3x', 'post');
		$this->assertFalse($route->handle($scope));
		$scope->request = new HTTPRequestCustom('/x', 'test');
		$this->assertFalse($route->handle($scope));
	}

	/**
     * @depends testPatternFunction
     */
	public function testParametersPassing() {
		$response = new HTTPResponseStorage();
		$scope = new Scope([ 'response' => $response, 'externalVariable' => 'externalVariableValue' ]);

		$route = new Route('/{var1}{var2}/{var3}', 'get', function($var1, $var2, $var3, $externalVariableValue, &$valueByRef) {
			$valueByRef = 18;
			$this->assertEquals($var1, 2);
			$this->assertEquals($var2, 'hello');
			$this->assertEquals($var3, 'goodbye');
		});
		$route->pattern('var1', '\\d+');
		
		$scope->request = new HTTPRequestCustom('/2hello/goodbye');
		$this->assertTrue($route->handle($scope));

		$this->assertEquals($scope->var1, 2);
		$this->assertEquals($scope->var2, 'hello');
		$this->assertEquals($scope->var3, 'goodbye');
		$this->assertEquals($scope->valueByRef, 18);
	}
};

?>