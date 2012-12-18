<?php
namespace Niysu\Services;

class DatabaseProfilingService {
	public function signalConnection($dsn, $timer) {
		$this->connectionTime += $timer;
	}

	public function signalQuery($sql, $dsn, $timer) {
		++$this->numQueries;
		if (is_numeric($timer))
			$this->totalMilliseconds += $timer;
	}
	
	public function getNumberOfQueries() {
		return $this->numQueries;
	}
	
	public function getQueriesTotalMilliseconds() {
		return $this->totalMilliseconds;
	}
	
	public function getTotalConnectionMilliseconds() {
		return $this->connectionTime;
	}


	private $connectionTime = 0;
	private $numQueries = 0;
	private $totalMilliseconds = 0;
};

?>