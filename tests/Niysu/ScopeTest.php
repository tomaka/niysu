<?php
namespace Niysu;

class ScopeTest extends \PHPUnit_Framework_TestCase {
	public function testGetVariable() {
		$scope = new Scope();
		$scope->add('testA', 1);
		$scope->add('testB', 2);
		$scope->add('testC', 3);
		$this->assertEquals($scope->getVariable('testA'), 1);
		$this->assertEquals($scope->getVariable('testB'), 2);
		$this->assertEquals($scope->getVariable('testC'), 3);
	}

	public function testScopeAccessible() {
		$scope = new Scope();
		$scope->add('test', 1);
		$scope->callFunction(function($scope) { $this->assertEquals($scope->getVariable('test', 1)); });
	}

	public function testClone() {
		$scope1 = new Scope();
		$scope1->add('test', 1);

		$scope2 = clone $scope1;
		$this->assertEquals($scope2->getVariable('test'), 1);
	}
	
	public function testCallFunctionByType() {
		$scope = new Scope();
		$scope->add('e', new \LogicException('test'));
		$scope->callFunction(function(\Exception $x) {
			$this->assertNotNull($x);
			$this->assertEquals($x->getMessage(), 'test');
		});
	}

	public function testCallFunction() {
		$scope = new Scope();
		$scope->add('test', 1);
		$scope->callFunction(function($test) { $this->assertEquals($test, 1); });
	}

	public function testAddByCallback() {
		$scope = new Scope();
		$scope->addByCallback('test', function() { return 3; });
		$scope->callFunction(function($test) { $this->assertEquals($test, 3); });
		$this->assertEquals($scope->getVariable('test'), 3);
	}
	
	public function testPassByReference() {
		$scope = new Scope();
		$scope->add('test', 1);
		$scope->callFunction(function(&$test) { $test = 2; });
		$this->assertEquals($scope->getVariable('test'), 2);
	}
};

?>