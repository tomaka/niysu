<?php
namespace Niysu;

class DatabaseServiceTest extends \PHPUnit_Framework_TestCase {
	protected $service;

	protected function setUp() {
		$this->service = new DatabaseService('sqlite::memory:');
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
	public function testInsert() {
		$this->service->test[] = [ 'id' => 2 ];
		$val = $this->service->test[0]->id();
		$this->assertEquals($val, 2);
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
};

?>