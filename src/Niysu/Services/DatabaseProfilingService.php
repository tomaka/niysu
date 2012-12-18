<?php
namespace Niysu\Services;

class DatabaseProfilingService {
	public function signalConnection($dsn, $timer) {
		$this->connectionTime += $timer;
	}

	public function signalQuery($sql, $dsn, $timer) {
		if (is_numeric($timer))
			$this->totalMilliseconds += $timer;

		$this->queries[] = [
			'sql' => $sql,
			'dsn' => $dsn,
			'time' => $timer
		];
	}
	
	public function getNumberOfQueries() {
		return count($this->queries);
	}
	
	public function getQueriesTotalMilliseconds() {
		return $this->totalMilliseconds;
	}
	
	public function getTotalConnectionMilliseconds() {
		return $this->connectionTime;
	}

	public function getQueriesList() {
		return $this->queries;
	}


	private $connectionTime = 0;
	private $totalMilliseconds = 0;
	private $queries = [];
};

?>