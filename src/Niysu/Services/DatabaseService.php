<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class DatabaseService {
	/**
	 * @deprecated
	 */
	public static function beforeConfigureDatabase($dsn, $login = null, $password = null) {
		return function($databaseService) use ($dsn, $login, $password) {
			$databaseService->setDatabase($dsn, $login, $password);
		};
	}

	public function beginTransaction() {
		$this->buildDatabase();
		if ($this->databasePDO->inTransaction())
			throw new \LogicException('A transaction is already in progress');
		if (!$this->databasePDO->beginTransaction())
			throw new \RuntimException('Error while starting a transaction');
	}

	public function commit() {
		$this->buildDatabase();
		if (!$this->databasePDO->inTransaction())
			throw new \LogicException('No transaction is currently in progress');
		if (!$this->databasePDO->commit())
			throw new \RuntimException('Error during commit');
	}

	public function rollBack() {
		$this->buildDatabase();
		if (!$this->databasePDO->inTransaction())
			throw new \LogicException('No transaction is currently in progress');
		if (!$this->databasePDO->rollBack())
			throw new \RuntimException('Error during rollback');
	}
	
	/**
	 * Opens a query and executes it.
	 *
	 * @param string 	$sql 		The SQL query to execute
	 * @param array 	$param 		An array with the parameters
	 * @return PDOStatement
	 */
	public function openQuery($sql, $params = []) {
		if (!is_array($params)) {
			$params = func_get_args();
			array_splice($params, 0, 1);
		}

		$this->buildDatabase();

		try {
			if ($this->logService)
				$this->logService->debug('SQL query: '.$sql);
		} catch(\Exception $e) {}
		
		$query = $this->databasePDO->prepare($sql);
		$query->execute($params);
		return $query;
	}
	
	/**
	 * Opens a query with results, executes it, and returns all the rows.
	 *
	 * Returns an array containing the results with PDO::FETCH_BOTH
	 *
	 * @param string 	$sql 		The SQL query to execute
	 * @param array 	$param 		An array with the parameters
	 * @return array
	 */
	public function query($sql, $params = []) {
		if (!is_array($params)) {
			$params = func_get_args();
			array_splice($params, 0, 1);
		}

		$this->buildDatabase();

		try {
			if ($this->logService)
				$this->logService->debug('SQL query: '.$sql);
		} catch(\Exception $e) {}
		
		$before = microtime(true);
		$query = $this->databasePDO->prepare($sql);
		$query->execute($params);
		$result = $query->fetchAll(\PDO::FETCH_BOTH);
		$after = microtime(true);

		if ($this->databaseProfilingService)
			$this->databaseProfilingService->signalQuery($sql, $this->dsn, round(1000 * ($after - $before)));

		return $result;
	}
	
	/**
	 * Opens a query with results, executes it, and returns the first row.
	 *
	 * Returns an array containing the first element of the results with PDO::FETCH_BOTH
	 *
	 * @param string 	$sql 		The SQL query to execute
	 * @param array 	$param 		An array with the parameters
	 * @return array
	 */
	public function querySingle($sql, $params = []) {
		if (!is_array($params)) {
			$params = func_get_args();
			array_splice($params, 0, 1);
		}
		$r = $this->query($sql, $params);
		return count($r) > 0 ? $r[0] : null;
	}
	
	/**
	 * Opens a query without results and executes it.
	 *
	 * @param string 	$sql 		The SQL query to execute
	 * @param array 	$param 		An array with the parameters
	 */
	public function execute($sql, $params = []) {
		if (!is_array($params))
			$params = array_splice(func_get_args(), 0, 1);

		$this->buildDatabase();

		try {
			if ($this->logService)
				$this->logService->debug('SQL query: '.$sql);
		} catch(\Exception $e) {}

		$before = microtime(true);
		$query = $this->databasePDO->prepare($sql);
		$query->execute($params);
		$after = microtime(true);

		if ($this->databaseProfilingService)
			$this->databaseProfilingService->signalQuery($sql, $this->dsn, round(1000 * ($after - $before)));
	}
	
	/// \brief Returns the last insert ID
	public function getLastInsertID($sequenceName = null) {
		$this->buildDatabase();
		return $this->databasePDO->lastInsertId($sequenceName);
	}


	public function __construct($logService = null, $databaseProfilingService = null) {
		$this->logService = $logService;
		$this->databaseProfilingService = $databaseProfilingService;
	}

	
	/**
	 * Configures the database to use.
	 *
	 * @param string 	$database 		The DSN of the database
	 * @param string 	$username 		(optional) Username
	 * @param string 	$password 		(optional) Password
	 */
	public function setDatabase($database, $username = null, $password = null) {
		if ($database instanceof \PDO) {
			$this->databasePDO = $database;

		} else if (is_string($database)) {
			$this->dsn = $database;
			$this->username = $username;
			$this->password = $password;

		} else {
			throw new \LogicException('Parameter passed to DatabaseService constructor is not valid');
		}
	}

	public function __get($tableName) {
		$this->buildDatabase();
		return new DatabaseServiceTable($this, $tableName, $this->colNameDelimiter);
	}


	private function buildDatabase() {
		if ($this->databasePDO)
			return;

		try {
			if ($this->logService)
				$this->logService->debug('Connection attempt to '.$this->dsn.' [with'.($this->username ? '' : 'out').' username][with'.($this->password ? '' : 'out').' password]');
		} catch(\Exception $e) {}

		$before = microtime(true);
		$this->databasePDO = new \PDO($this->dsn, $this->username, $this->password);
		$this->databasePDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		if ($this->databaseProfilingService)
			$this->databaseProfilingService->signalConnection($thsi->dsn, round(1000 * (microtime(true) - $before)));

		try {
			if ($this->logService)
				$this->logService->debug('Successfully connected to '.$this->dsn);
		} catch(\Exception $e) {}

		switch ($this->databasePDO->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
			case 'sqlite':		$this->colNameDelimiter = ''; break;
			case 'mysql':		$this->colNameDelimiter = '`'; break;
			case 'pgsql':		$this->colNameDelimiter = '"'; break;
			default:
				throw new \LogicException('Unknown PDO driver: '.$this->databasePDO->getAttribute(\PDO::ATTR_DRIVER_NAME));
		}
	}


	private $logService = null;
	private $databaseProfilingService = null;

	private $dsn;
	private $username;
	private $password;

	private $databasePDO = null;
	private $colNameDelimiter = '"';
};

?>