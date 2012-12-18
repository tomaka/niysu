<?php
namespace Niysu\Services;

class HTTPBasicAuthServiceTest extends \PHPUnit_Framework_TestCase {
	public function testNoAuthorizationHeader() {
		$scope = new \Niysu\Scope([
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [			// no authorization header
			])
		]);

		$authService = $scope->call(__NAMESPACE__.'\\HTTPBasicAuthService');
		$this->assertFalse($authService->login());
	}

	public function testWrongAuthorizationHeaderFormat() {
		$scope = new \Niysu\Scope([
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [
				'Authorization' => 'This format is wrong'					// wrong format
			])
		]);

		$authService = $scope->call(__NAMESPACE__.'\\HTTPBasicAuthService');
		$this->assertFalse($authService->login());
	}

	/**
     * @expectedException LogicException
     */
	public function testNoAuthFunctionDefined() {
		$scope = new \Niysu\Scope([
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [
				'Authorization' => 'Basic bG9naW46cGFzc3dvcmQ='				// base64('login:password')
			])
		]);

		$authService = $scope->call(__NAMESPACE__.'\\HTTPBasicAuthService');
		$authService->login();
	}
	
	public function testAuthFunctionCalled() {
		$scope = new \Niysu\Scope([
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [
				'Authorization' => 'Basic bG9naW46cGFzc3dvcmQ='				// base64('login:password')
			])
		]);

		$authService = $scope->call(__NAMESPACE__.'\\HTTPBasicAuthService');
		$authService->setAuthFunction(function($login, $password) {
			$this->assertEquals($login, 'login');
			$this->assertEquals($password, 'password');
		});

		$authService->login();
	}
	
	/**
	 * @depends testAuthFunctionCalled
	 */
	public function testAuthFunctionReturnValue() {
		$scope = new \Niysu\Scope([
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [
				'Authorization' => 'Basic bG9naW46cGFzc3dvcmQ='				// base64('login:password')
			])
		]);

		$authService = $scope->call(__NAMESPACE__.'\\HTTPBasicAuthService');
		$authService->setAuthFunction(function() {
			return 12;
		});

		$this->assertEquals($authService->login(), 12);
	}
	
	/**
	 * @depends testAuthFunctionCalled
	 */
	public function testAuthFunctionOutsideScopeAccessible() {
		$scope = new \Niysu\Scope([
			'request' => new \Niysu\HTTPRequestCustom('/', 'GET', [
				'Authorization' => 'Basic bG9naW46cGFzc3dvcmQ='				// base64('login:password')
			])
		]);

		$authService = $scope->call(__NAMESPACE__.'\\HTTPBasicAuthService');
		$authService->setAuthFunction(function(&$test) {
			$test = 5;
			return true;
		});

		$authService->login();
		$this->assertEquals($scope->test, 5);
	}
};

?>