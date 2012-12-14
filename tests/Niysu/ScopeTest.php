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
	
	public function testClone() {
		$scope1 = new Scope();
		$scope1->add('test', 1);

		$scope2 = clone $scope1;
		$this->assertEquals($scope2->getVariable('test'), 1);
	}

	/**
     * @expectedException PHPUnit_Framework_Warning
     */
	public function testSetVariablePassByRef() {
		$scope = new Scope();
		$scope->add('test', 4);
		$scope->setVariablePassByRef('test', false);
		$scope->callFunction(function(&$test) { $test = 10; });
		$scope->assertEquals($scope->getVariable('test'), 4);
	}

	public function testCallFunction() {
		$scope = new Scope();
		$scope->add('test', 1);
		$scope->callFunction(function($test) { $this->assertEquals($test, 1); });
	}

	public function testCallFunctionScopeAccessible() {
		$scope = new Scope();
		$scope->add('test', 1);
		$scope->callFunction(function($scope) {
			$this->assertEquals($scope->getVariable('test'), 1);
		});
	}
	
	public function testCallFunctionByType() {
		$scope = new Scope();
		$scope->add('e', new \LogicException('test'));
		$scope->callFunction(function(\Exception $x) {
			$this->assertNotNull($x);
			$this->assertEquals($x->getMessage(), 'test');
		});
	}

	public function testCallFunctionMultipleParams() {
		$scope = new Scope();
		$scope->add('a', 1);
		$scope->add('b', 2);
		$scope->callFunction(function($a, $b) {
			$this->assertEquals($a, 1);
			$this->assertEquals($b, 2);
		});
	}

	public function testCallFunctionWithCallback() {
		$scope = new Scope();
		$scope->addByCallback('test', function() { return 3; });
		$scope->callFunction(function($test) { $this->assertEquals($test, 3); });
		$this->assertEquals($scope->getVariable('test'), 3);
	}
	
	public function testCallFunctionWithReference() {
		$scope = new Scope();
		$scope->add('test', 1);
		$scope->callFunction(function(&$test) { $test = 2; });
		$this->assertEquals($scope->getVariable('test'), 2);
	}

	public function testCallFunctionCombo() {
		$scope = new Scope();
		$scope->add('a', 1);
		$scope->add('b', function() { return 2; });
		$scope->add('cTest', new \LogicException('testC'));
		$scope->add('dTest', function() { return new \RuntimeException('testD'); }, 'RuntimeException');
		$scope->add('e', 20);

		$scope->callFunction(function($a, $b, \LogicException $c, \RuntimeException &$d, &$e) {
			$this->assertEquals($a, 1);
			$this->assertEquals($b, 2);
			$this->assertEquals($c->getMessage(), 'testC');
			$this->assertEquals($d->getMessage(), 'testD');
			$d = 'testModified';
			$e *= 2;
		});

		$this->assertEquals($scope->getVariable('e'), 40);
		$this->assertEquals($scope->getVariable('dTest'), 'testModified');
	}
};

?>