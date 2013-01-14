<?php
namespace Niysu\Contexts;

class HTTPBasicAuthContextTest extends \PHPUnit_Framework_TestCase {
	public function testNoAuthorizationHeader() {
		$authService = $this->getMock('Niysu\\Services\\AuthService');
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', []);

		$basicAuthContext = new HTTPBasicAuthContext($request, $authService);
		$this->assertFalse($basicAuthContext->login());
	}

	public function testWrongAuthorizationHeaderFormat() {
		$authService = $this->getMock('Niysu\\Services\\AuthService');
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [
			'Authorization' => 'This format is wrong'					// wrong format
		]);

		$basicAuthContext = new HTTPBasicAuthContext($request, $authService);
		$this->assertFalse($basicAuthContext->login());
	}
	
	public function testAuthFunctionCalled() {
		$authService = $this->getMock('Niysu\\Services\\AuthService');
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [
			'Authorization' => 'Basic bG9naW46cGFzc3dvcmQ='				// base64('login:password')
		]);

		$authService
			->expects($this->once())
			->method('login')
			->with($this->equalTo([ 'login' => 'login', 'password' => 'password' ]))
			->will($this->returnValue(12));

		$basicAuthContext = new HTTPBasicAuthContext($request, $authService);
		$this->assertEquals(12, $basicAuthContext->login());
	}

	/**
	 * @depends testAuthFunctionCalled
	 */
	public function testAccessFunctionCalled() {
		$authService = $this->getMock('Niysu\\Services\\AuthService');
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [
			'Authorization' => 'Basic bG9naW46cGFzc3dvcmQ='				// base64('login:password')
		]);

		$authService
			->expects($this->atLeastOnce())
			->method('login')
			->with($this->equalTo([ 'login' => 'login', 'password' => 'password' ]))
			->will($this->returnValue(12));

		$authService
			->expects($this->once())
			->method('hasAccess')
			->with($this->equalTo(12), $this->equalTo('accessName'));

		$basicAuthContext = new HTTPBasicAuthContext($request, $authService);
		$basicAuthContext->hasAccess('accessName');
	}
};

?>