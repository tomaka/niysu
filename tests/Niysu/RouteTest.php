<?php
namespace Niysu;

class RouteTest extends \PHPUnit_Framework_TestCase {
	/**
	 *
	 */
	public function testPatternMatchSingle() {
		$route = new Route('/{var}', 'get', function() {});
		$response = new HTTPResponseStorage();
		
		$request = new HTTPRequestCustom('/test');
		$this->assertTrue($route->handle($request, $response, new Scope()));

		$request = new HTTPRequestCustom('/1');
		$this->assertTrue($route->handle($request, $response, new Scope()));

		$request = new HTTPRequestCustom('/');
		$this->assertFalse($route->handle($request, $response, new Scope()));

		$request = new HTTPRequestCustom('/test-test');
		$this->assertFalse($route->handle($request, $response, new Scope()));

		$request = new HTTPRequestCustom('/test/test');
		$this->assertFalse($route->handle($request, $response, new Scope()));
	}

	/**
     * @depends testPatternMatchSingle
     */
	public function testPatternMatchMultiple() {
		$route = new Route('/{var1}/{var2}', 'get', function() {});
		$response = new HTTPResponseStorage();
		
		$request = new HTTPRequestCustom('/test');
		$this->assertFalse($route->handle($request, $response, new Scope()));
		
		$request = new HTTPRequestCustom('/1');
		$this->assertFalse($route->handle($request, $response, new Scope()));
		
		$request = new HTTPRequestCustom('//');
		$this->assertFalse($route->handle($request, $response, new Scope()));
		
		$request = new HTTPRequestCustom('/test/test');
		$this->assertTrue($route->handle($request, $response, new Scope()));
		
		$request = new HTTPRequestCustom('/test/test/x');
		$this->assertFalse($route->handle($request, $response, new Scope()));
	}

	/**
	 *
	 */
	public function testMethodMatch() {
		$response = new HTTPResponseStorage();
		$scope = new Scope();
		$route = new Route('/', 'test', function() {});

		$request = new HTTPRequestCustom('/', 'get');
		$this->assertFalse($route->handle($request, $response, $scope));

		$request = new HTTPRequestCustom('/', 'post');
		$this->assertFalse($route->handle($request, $response, $scope));

		$request = new HTTPRequestCustom('/', 'test');
		$this->assertTrue($route->handle($request, $response, $scope));
	}

	/**
	 *
	 */
	public function testAnyMethodMatch() {
		$response = new HTTPResponseStorage();
		$scope = new Scope();
		$route = new Route('/', '.*', function() {});

		$request = new HTTPRequestCustom('/', 'get');
		$this->assertTrue($route->handle($request, $response, $scope));

		$request = new HTTPRequestCustom('/', 'post');
		$this->assertTrue($route->handle($request, $response, $scope));

		$request = new HTTPRequestCustom('/', 'test-test/test@test');
		$this->assertTrue($route->handle($request, $response, $scope));
	}

	/**
	 *
	 */
	public function testPatternFunction() {
		$response = new HTTPResponseStorage();
		$scope = new Scope();
		$route = new Route('/{var}', 'get', function() {});
		$route->pattern('var', '\\d+');

		$request = new HTTPRequestCustom('/', 'post');
		$this->assertFalse($route->handle($request, $response, $scope));

		$request = new HTTPRequestCustom('/3', 'get');
		$this->assertTrue($route->handle($request, $response, $scope));

		$request = new HTTPRequestCustom('/3x', 'post');
		$this->assertFalse($route->handle($request, $response, $scope));

		$request = new HTTPRequestCustom('/x', 'test');
		$this->assertFalse($route->handle($request, $response, $scope));
	}

	/**
	 *
	 */
	public function testParenthesisInPattern() {
		$response = new HTTPResponseStorage();
		$route = new Route('/{var}', 'get');
		$route->pattern('var', '(\\d)+');

		$route->handler(function($var) { $this->assertEquals(3, $var); });

		$request = new HTTPRequestCustom('/3', 'get');
		$this->assertTrue($route->handle($request, $response, new Scope()));
	}

	/**
     * @depends testPatternFunction
     */
	public function testParametersPassing() {
		$response = new HTTPResponseStorage();
		$scope = new Scope([ 'externalVariable' => 'externalVariableValue' ]);

		$route = new Route('/{var1}{var2}/{var3}', 'get', function($var1, $var2, $var3, $externalVariableValue) {
			$valueByRef = 18;
			$this->assertEquals($var1, 2);
			$this->assertEquals($var2, 'hello');
			$this->assertEquals($var3, 'goodbye');
		});
		$route->pattern('var1', '\\d+');
		
		$request = new HTTPRequestCustom('/2hello/goodbye');
		$this->assertTrue($route->handle($request, $response, $scope));
	}
};

?>