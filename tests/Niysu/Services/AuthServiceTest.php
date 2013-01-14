<?php
namespace Niysu\Services;

class AuthServiceTest extends \PHPUnit_Framework_TestCase {
	public function testLoginFunction() {
		$service = new AuthService();

		$service->setLoginCallback(function($login, $password) {
			$this->assertEquals('login', $login);
			$this->assertEquals('pass', $password);
			return 12;
		});

		$this->assertEquals(12, $service->login([ 'login' => 'login', 'password' => 'pass' ]));
	}

	/**
	 * @depends testLoginFunction
	 */
	public function testScopePassedToLoginFunction() {
		$scope = new \Niysu\Scope([ 'test' => 5 ]);
		$service = new AuthService($scope);

		$service->setLoginCallback(function($login, $test, $password) {
			$this->assertEquals(5, $test);
			$this->assertEquals('login', $login);
			$this->assertEquals('pass', $password);
			return 12;
		});

		$this->assertEquals(12, $service->login([ 'login' => 'login', 'password' => 'pass' ]));
	}

	/**
	 * @expectedException Exception
	 */
	public function testLoginFunctionHasNotBeenSet() {
		$service = new AuthService($scope);
		$service->login([]);
	}

	public function testAccessTestFunction() {
		$service = new AuthService();

		$service->setAccessTestCallback(function($userID, $access) {
			$this->assertEquals(5, $userID);
			$this->assertEquals('access', $access);
			return true;
		});

		$this->assertTrue($service->hasAccess(5, 'access'));
	}

	/**
	 * @depends testAccessTestFunction
	 */
	public function testScopePassedToAccessFunction() {
		$scope = new \Niysu\Scope([ 'test' => 15 ]);
		$service = new AuthService($scope);

		$service->setAccessTestCallback(function($userID, $test, $access) {
			$this->assertEquals(15, $test);
			$this->assertEquals(5, $userID);
			$this->assertEquals('access', $access);
			return false;
		});

		$this->assertFalse($service->hasAccess(5, 'access'));
	}

	/**
	 * @expectedException Exception
	 */
	public function testAccessFunctionHasNotBeenSet() {
		$service = new AuthService($scope);
		$service->hasAccess(5, 'test');
	}
};

?>