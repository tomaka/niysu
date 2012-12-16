<?php
namespace Niysu\Services;

class DatabaseServiceTest extends \PHPUnit_Framework_TestCase {
	protected $service;
	
	protected function setUp() {
		$this->service = new DatabaseService();
		$this->service->setDatabase('sqlite::memory:');
		$this->service->execute('CREATE TABLE test (id INTEGER)');
	}
	
	/**
     * @expectedException PDOException
     */
	public function testExceptions() {
		$this->service->execute('UNVALID SQL QUERY');
	}

	public function testSelect() {
		$this->service->execute('INSERT INTO test(id) VALUES (1)');
		$val = $this->service->test[0]->id();
		$this->assertEquals($val, 1);
	}
	
	/**
     * @depends testSelect
     */
	public function testForeach() {
		$this->service->execute('INSERT INTO test VALUES (1)');
		$this->service->execute('INSERT INTO test VALUES (2)');
		$this->service->execute('INSERT INTO test VALUES (3)');

		$i = 1;
		foreach ($this->service->test as $element) {
			$this->assertEquals($element->id(), $i);
			++$i;
		}
	}

	/**
     * @depends testSelect
     */
	public function testInsert() {
		$this->service->test[] = [ 'id' => 2 ];
		$val = $this->service->test[0]->id();
		$this->assertEquals($val, 2);
	}

	/**
     * @depends testInsert
     */
	public function testMultipleInsert() {
		$this->markTestIncomplete();

		$this->service->test[] = [ [ 'id' => 2 ], [ 'id' => 3 ], [ 'id' => 4 ], [ 'id' => 5 ] ];
		$this->assertEquals(count($this->service->test, 4));
		$this->assertEquals($this->service->test[0]->id(), 2);
		$this->assertEquals($this->service->test[1]->id(), 3);
		$this->assertEquals($this->service->test[2]->id(), 4);
		$this->assertEquals($this->service->test[3]->id(), 5);
	}
	
	public function testUpdate() {
		$this->service->execute('INSERT INTO test VALUES (1)');
		
		$this->service->test->id = 5;
		$this->assertEquals($this->service->test[0]->id(), 5);
	}
};

?>