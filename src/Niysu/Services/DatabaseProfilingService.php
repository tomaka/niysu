<?php
namespace Niysu\Services;

/**
 * This service stores statistics about database interactions.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class DatabaseProfilingService {
	/**
	 * Call this function to signal a connection to a database.
	 * @param string 	$dsn 		The DSN of the database
	 * @param number 	$timer 		The time in seconds it took to connect
	 */
	public function signalConnection($dsn, $timer) {
		$this->connectionTime += $timer;
	}

	/**
	 * Call this function to signal a query to a database.
	 * @param string 	$sql 		The SQL query
	 * @param string 	$dsn 		The DSN of the database
	 * @param number 	$timer 		The time in seconds it took
	 */
	public function signalQuery($sql, $dsn, $timer) {
		if (is_numeric($timer))
			$this->totalTime += $timer;

		$this->queries[] = [
			'sql' => $sql,
			'dsn' => $dsn,
			'time' => $timer
		];
	}
	
	/**
	 * Returns the total number of queries that have been signaled.
	 * @return integer
	 */
	public function getNumberOfQueries() {
		return count($this->queries);
	}
	
	/**
	 * Returns the total time it took for queries that have been signaled.
	 * @return number
	 */
	public function getQueriesTotalDuration() {
		return $this->totalTime;
	}
	
	/**
	 * Returns the total time it took to connect to databases.
	 * @return number
	 */
	public function getTotalConnectionDuration() {
		return $this->connectionTime;
	}

	/**
	 * Returns the list of all queries and their infos.
	 * Returns an array where each entry is a query.
	 * Each query is an array with keys 'sql', 'dsn' and 'time' which match the values that were passed to signalQuery.
	 * @return array
	 */
	public function getQueriesList() {
		return $this->queries;
	}


	private $connectionTime = 0;
	private $totalTime = 0;
	private $queries = [];
};

?>