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
		$scope1->test = 1;

		$scope2 = clone $scope1;
		$this->assertEquals($scope2->test, 1);
		$scope2->test = 5;

		$this->assertEquals($scope1->test, 1);
		$this->assertEquals($scope2->test, 5);
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
	 * @depends testChild
	 */
	public function testChildNewRefsFromParent() {
		$scope1 = new Scope();
		$scope2 = $scope1->newChild([], true, false, false);

		$a =& $scope2->getByRef('test');
		$a = 12;
		
		$this->assertEquals(12, $scope1->test);
		$this->assertEquals(12, $scope2->test);
	}
	
	/**
	 * @depends testChild
	 */
	public function testChildNoRefsFromParent() {
		$scope1 = new Scope();
		$scope2 = $scope1->newChild([], false, true, false);

		$a =& $scope2->getByRef('test');
		$a = 12;
		
		$this->assertNull($scope1->test);
		$this->assertEquals(12, $scope2->test);
	}
	
	/**
	 * @depends testChild
	 */
	public function testChildSetModifiesParent() {
		$scope1 = new Scope();
		$scope2 = $scope1->newChild([], false, false, true);

		$scope2->test = 5;
		
		$this->assertEquals(5, $scope1->test);
		$this->assertEquals(5, $scope2->test);
	}

	/**
     * @expectedException RuntimeException
     */
	public function testSetVariablePassByRef() {
		$scope = new Scope();
		$scope->set('test', 4);
		$scope->passByRef('test', false);
		$scope->call(function(&$test) { $test = 10; });
		$scope->assertEquals($scope->get('test'), 4);
	}

	public function testCallParamTypes() {
		$scope = new Scope();

		$ret = $scope->call(function() { return 2; });
		$this->assertEquals($ret, 2);

		$scope->call('rand');

		$ret = $scope->call('Exception');
		$this->assertInstanceOf('Exception', $ret);
	}

	/**
	 * @depends testCallParamTypes
	 */
	public function testCallValuePassing() {
		$scope = new Scope();
		$scope->test = 1;
		$scope->call(function($test) { $this->assertEquals($test, 1); });
	}

	/**
	 * @depends testCallParamTypes
	 */
	public function testCallTypePassing() {
		$scope = new Scope();
		$scope->test = new \Exception();
		$scope->call(function(\Exception $x, $scope) {
			$this->assertInstanceOf('Exception', $x);
			$this->assertEquals($scope->test, $x);
		});
	}

	/**
	 * @depends testCallParamTypes
	 */
	public function testCallTypeInheritancePassing() {
		$scope = new Scope();
		$scope->test = new \LogicException();
		$scope->call(function(\Exception $x, $scope) {
			$this->assertInstanceOf('LogicException', $x);
			$this->assertEquals($scope->test, $x);
		});
	}

	/**
	 * @depends testCallValuePassing
	 */
	public function testCallScopeValueAvailable() {
		$scope = new Scope();
		$scope->testD = 93;
		$scope->call(function($scope) { $this->assertEquals($scope->testD, 93); $scope->testD++; });
		$this->assertEquals($scope->testD, 94);
	}

	/**
	 * @depends testCallValuePassing
	 */
	public function testCallScopeTypeAvailable() {
		$scope = new Scope();
		$scope->testD = 93;
		$scope->call(function(Scope $s) { $this->assertEquals($s->testD, 93); $s->testD++; });
		$this->assertEquals($scope->testD, 94);
	}

	/**
	 * @depends testCallValuePassing
	 */
	public function testCallPassingValueByReference() {
		$scope = new Scope();

		$scope->call(function(&$test) { $test = 5; });
		$this->assertEquals($scope->test, 5);

		$scope->call(function(&$test) { $test = 3; });
		$this->assertEquals($scope->test, 3);
	}

	/**
	 * @depends testCallPassingValueByReference
     * @expectedException RuntimeException
	 */
	public function testCallNoPassByRef() {
		$scope = new Scope();
		$scope->test = 1;
		$scope->passByRef('test', false);
		$scope->call(function(&$test) { });
	}

	/**
	 * @depends testCallValuePassing
	 */
	public function testCallPassingTypeByReference() {
		$scope = new Scope();
		$scope->test = new \LogicException();
		$scope->call(function(\Exception &$x) {
			$this->assertInstanceOf('LogicException', $x);
			$x = new \RuntimeException();
		});
		$this->assertInstanceOf('RuntimeException', $scope->test);
	}

	/**
	 * @depends testCallPassingTypeByReference
     * @expectedException RuntimeException
     */
	public function testCallErrorOnNonexistingRefType() {
		$scope = new Scope();
		$scope->test = new \LogicException();
		$scope->call(function(\RuntimeException &$x) { });
	}

	/**
	 * @depends testCallValuePassing
	 */
	public function testCallPassingValueWithCallback() {
		$scope = new Scope();
		$scope->callback('test', function() { return 12; });
		$scope->call(function($test) { $this->assertEquals($test, 12); });
		$this->assertEquals($scope->test, 12);
	}

	/**
	 * @depends testCallValuePassing
	 */
	public function testCallPassingTypeWithCallback() {
		$scope = new Scope();
		$scope->callback('test', function() { return new \Exception(); }, 'Exception');
		$scope->call(function(\Exception $x) {
			$this->assertInstanceOf('Exception', $x);
		});
	}

	/**
	 * @depends testCallValuePassing
	 */
	public function testCallNullIfNotExists() {
		$scope = new Scope();
		$scope->test = 3;
		$scope->call(function($x) {
			$this->assertNull($x);
		});
	}

	/**
	 * @depends testCallValuePassing
	 */
	public function testCallDefaultValueIfNotExists() {
		$scope = new Scope();
		$scope->test = 3;
		$scope->call(function($x = 5) {
			$this->assertEquals(5, $x);
		});
	}
	
};

?>