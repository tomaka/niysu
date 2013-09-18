<?php
namespace Niysu\Services;

class DatabaseServiceTest extends \PHPUnit_Framework_TestCase {
	protected $service;
	
	protected function setUp() {
		$scope = new \Niysu\Scope();
		$this->service = $scope->call(__NAMESPACE__.'\\DatabaseService');
		$this->service->setDatabase('sqlite::memory:');
		try {
			$this->service->execute('CREATE TABLE test (id INTEGER)');
		} catch(\Exception $e) {
			$this->service->execute('DELETE FROM test WHERE 1');
		}
	}

	public function testExecute() {
		$this->service->execute('INSERT INTO test VALUES (1)');
	}

	/**
	 * @depends testExecute
	 **/
	public function testQuery() {
		$this->service->execute('INSERT INTO test VALUES (1)');
		$this->service->execute('INSERT INTO test VALUES (2)');

		$result = $this->service->query('SELECT id FROM test');
		$this->assertEquals(count($result), 2);
		$this->assertEquals($result[0][0], 1);
		$this->assertEquals($result[0]['id'], 1);
		$this->assertEquals($result[1][0], 2);
		$this->assertEquals($result[1]['id'], 2);
	}

	/**
	 * @depends testQuery
	 **/
	public function testQuerySingle() {
		$this->service->execute('INSERT INTO test VALUES (1)');
		$this->service->execute('INSERT INTO test VALUES (2)');

		$result = $this->service->querySingle('SELECT id FROM test');
		$this->assertEquals($result[0], 1);
		$this->assertEquals($result['id'], 1);
	}
	
	/**
	 * @depends testExecute
     * @expectedException PDOException
     */
	public function testExceptions() {
		$this->service->execute('UNVALID SQL QUERY');
	}

	/**
	 * @depends testExecute
	 **/
	public function testSelect() {
		$this->service->execute('INSERT INTO test(id) VALUES (1)');
		$val = $this->service->test[0]->id;
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
			$this->assertEquals($element->id, $i);
			++$i;
		}
	}

	/**
     * @depends testSelect
     */
	public function testInsert() {
		$this->service->test[] = [ 'id' => 2 ];
		$val = $this->service->test[0]->id;
		$this->assertEquals($val, 2);
	}

	/**
     * @depends testInsert
     */
	public function testMultipleInsert() {
		$this->markTestIncomplete();

		$this->service->test[] = [ [ 'id' => 2 ], [ 'id' => 3 ], [ 'id' => 4 ], [ 'id' => 5 ] ];
		$this->assertEquals(count($this->service->test, 4));
		$this->assertEquals($this->service->test[0]->id, 2);
		$this->assertEquals($this->service->test[1]->id, 3);
		$this->assertEquals($this->service->test[2]->id, 4);
		$this->assertEquals($this->service->test[3]->id, 5);
	}
	
	public function testUpdate() {
		$this->markTestIncomplete();
		
		$this->service->execute('INSERT INTO test VALUES (1)');
		
		$this->service->test->id = 5;
		$this->assertEquals($this->service->test[0]->id, 5);
		
		$this->service->test[0]->id = 6;
		$this->assertEquals($this->service->test[0]->id, 6);
	}
};

?>