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

	public function testCallFunction() {
		$scope = new Scope();
		$scope->add('test', 1);
		$scope->callFunction(function($test) { $this->assertEquals($test, 3); });
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