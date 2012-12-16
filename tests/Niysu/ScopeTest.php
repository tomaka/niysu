<?php
namespace Niysu;

class ScopeTest extends \PHPUnit_Framework_TestCase {
	public function testGetSet() {
		$scope = new Scope([ 'testA' => 1 ]);
		$scope->testB = 2;

		$this->assertEquals($scope->get('testA'), 1);
		$this->assertEquals($scope->testB, 2);
		$this->assertNull($scope->testC);
	}

	public function testGetByRef() {
		$scope = new Scope();

		$scope->set('testA', 3);
		$a =& $scope->getByRef('testA');
		$a = 12;
		$this->assertEquals($scope->testA, 12);

		$b =& $scope->getByRef('testB');
		$scope->testB = 40;
		$this->assertEquals($b, 40);
	}

	public function testGetByType() {
		$scope = new Scope();
		
		$scope->set('testA', new \Exception());
		$this->assertNotNull($scope->getByType('\Exception'));
	}

	public function testGetByTypeByRef() {
		$scope = new Scope();
		
		$scope->set('testA', new \DateTime('2012-01-01'));
		$e =& $scope->getByTypeByRef('\DateTime');
		$this->assertNotNull($e);

		$e->setDate(2013, 05, 05);
		$this->assertEquals($scope->testA->format('Y'), 2013);
	}
	
	public function testClone() {
		$scope1 = new Scope();
		$scope1->set('test', 1);

		$scope2 = clone $scope1;
		$this->assertEquals($scope2->get('test'), 1);
	}
	
	public function testChild() {
		$scope1 = new Scope();
		$scope2 = $scope1->newChild();

		$scope1->test = 1;
		$this->assertEquals($scope1->test, 1);
		$this->assertEquals($scope2->test, 1);
		
		$scope2->test = 4;
		$this->assertEquals($scope1->test, 1);
		$this->assertEquals($scope2->test, 4);
	}

	/**
     * @expectedException PHPUnit_Framework_Error_Warning
     */
	public function testSetVariablePassByRef() {
		$scope = new Scope();
		$scope->set('test', 4);
		$scope->passByRef('test', false);
		$scope->call(function(&$test) { $test = 10; });
		$scope->assertEquals($scope->get('test'), 4);
	}

	public function testCallFunction() {
		$scope = new Scope();
		$scope->set('test', 1);
		$scope->call(function($test) { $this->assertEquals($test, 1); });
	}

	public function testCallFunctionsFancy() {
		$scope = new Scope();
		$scope->call(function() {});
		$scope->call('rand');
		// TODO: static function of a class (must find a static function in a class in stdlib)
		$this->assertInstanceOf('Exception', $scope->call('Exception'));
	}
	
	public function testCallFunctionScopeAccessible() {
		$scope = new Scope();
		$scope->set('test', 1);
		$scope->call(function($scope) {
			$this->assertEquals($scope->get('test'), 1);
		});
	}
	
	public function testCallFunctionByType() {
		$scope = new Scope();
		$scope->set('e', new \LogicException('test'));
		$scope->call(function(\Exception $x) {
			$this->assertNotNull($x);
			$this->assertEquals($x->getMessage(), 'test');
		});
	}

	public function testCallFunctionMultipleParams() {
		$scope = new Scope();
		$scope->set('a', 1);
		$scope->set('b', 2);
		$scope->call(function($a, $b) {
			$this->assertEquals($a, 1);
			$this->assertEquals($b, 2);
		});
	}

	public function testCallFunctionWithCallback() {
		$scope = new Scope();
		$scope->callback('test', function() { return 3; });
		$scope->call(function($test) { $this->assertEquals($test, 3); });
		$this->assertEquals($scope->get('test'), 3);
	}
	
	public function testCallFunctionUniqueCallback() {
		$n = 0;		// number of time the callback is called
		
		$scope = new Scope();
		$scope->callback('test', function() use (&$n) { $n += 1; return 3; });
		
		$scope->call(function($test) {});
		$scope->call(function($test) {});
		
		$this->assertEquals($n, 1);
	}
	
	public function testCallFunctionWithReference() {
		$scope = new Scope();
		$scope->set('test', 1);
		$scope->call(function(&$test) { $test = 2; });
		$this->assertEquals($scope->get('test'), 2);
	}

	public function testCallFunctionCombo() {
		$scope = new Scope();
		$scope->set('a', 1);
		$scope->callback('b', function() { return 2; });
		$scope->set('cTest', new \LogicException('testC'));
		$scope->callback('dTest', function() { return new \RuntimeException('testD'); }, 'RuntimeException');
		$scope->set('e', 20);

		$scope->call(function($a, $b, \LogicException $c, \RuntimeException &$d, &$e) {
			$this->assertEquals($a, 1);
			$this->assertEquals($b, 2);
			$this->assertEquals($c->getMessage(), 'testC');
			$this->assertEquals($d->getMessage(), 'testD');
			$d = 'testModified';
			$e *= 2;
		});

		$this->assertEquals($scope->get('e'), 40);
		$this->assertEquals($scope->get('dTest'), 'testModified');
	}
};

?>