<?php
namespace Niysu\Services;

/**
 * 
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class DatabaseSchemaService {
	/**
	 * Constructor.
	 * @param Logger 	$log 	Logging object
	 */
	public function __construct(DatabaseService $databaseService, \Monolog\Logger $log = null) {
		$this->log = $log;
		$this->databaseService = $databaseService;
	}

	/**
	 * 
	 */
	public function toDatabase($sql) {
		$existingTablesList = $this->getTablesList();

		foreach (\SqlFormatter::splitQuery($sql) as $query) {
			if (preg_match('/^CREATE\\s+TABLE\\s+(\\w+)\\s+\\((.*?)\\).*$/is', $query, $matches)) {
				// handling CREATE TABLE
				if (array_search($matches[1], $existingTablesList) === false) {
					if ($this->log) $this->log->debug('Creating non-existing table '.$matches[1]);
					$this->databaseService->execute($query);

				} else {
					throw new \LogicException('Columns checking not yet implemented');
				}

			} else if (false) {
				// handling CREATE TRIGGER

			} else if (false) {
				// handling CREATE FUNCTION

			} else if (false) {
				// handling CREATE INDEX

			}
		}
	}

	/**
	 * 
	 */
	public function fromDatabase() {
	}




	private function getTablesList() {
		$result = [];
		$list = $this->databaseService->query('SELECT name FROM sqlite_master WHERE type = \'table\'');
		foreach($list as $entry)
			$result[] = $entry['name'];
		return $result;
	}

	private function getColumnsList($tableName) {
		$sql = $this->databaseService->query('SELECT sql FROM sqlite_master WHERE name = ? AND type = \'table\'', [ $tableName ])[0];
		if (!$sql)
			throw new \RuntimeException('Table doesn\'t exist: '.$tableName);


	}


	private $log;
	private $databaseService;
};

?>