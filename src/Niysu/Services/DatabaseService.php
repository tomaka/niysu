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
			array_shift($params);
		}

		$this->buildDatabase();

		try {
			if ($this->logService)
				$this->logService->debug('SQL query: '.$sql);
		} catch(\Exception $e) {}

		$before = microtime(true);
		$query = $this->databasePDO->prepare($sql);

		foreach ($params as $key => $val) {
			$realKey = is_numeric($key) ? $key+1 : $key;

			if (is_null($val))				$type = \PDO::PARAM_NULL;
			else if (is_resource($val))		$type = \PDO::PARAM_LOB;
			//else if (is_numeric($val))		$type = \PDO::PARAM_INT;
			else if (is_bool($val))			$type = \PDO::PARAM_BOOL;
			else if (is_string($val))		$type = \PDO::PARAM_STR;
			else							throw new \LogicException('SQL query parameter of unknown type');

			$query->bindValue($realKey, $val, $type);
		}

		$query->execute();
		$after = microtime(true);

		if ($this->databaseProfilingService)
			$this->databaseProfilingService->signalQuery($sql, $this->dsn, round(1000 * ($after - $before)));

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
		$query = call_user_func_array([ $this, 'openQuery' ], func_get_args());

		$resultRow = [];
		for ($i = 0; $i < $query->columnCount(); ++$i) {
			$meta = $query->getColumnMeta($i);
			$query->bindColumn($i + 1, $resultRow[$meta['name']], $meta['pdo_type']);
			$resultRow[$i] =& $resultRow[$meta['name']];
		}

		$result = [];
		while ($query->fetch(\PDO::FETCH_BOUND)) {
			$rowCopy = [];
			foreach ($resultRow as $k => $v)
				$rowCopy[$k] = $v;
			$result[] = $rowCopy;
		}
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
		$r = call_user_func_array([ $this, 'query' ], func_get_args());
		return count($r) > 0 ? $r[0] : null;
	}
	
	/**
	 * Opens a query without results and executes it.
	 *
	 * @param string 	$sql 		The SQL query to execute
	 * @param array 	$param 		An array with the parameters
	 */
	public function execute($sql, $params = []) {
		call_user_func_array([ $this, 'query' ], func_get_args());
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
		$this->databasePDO = new \PDO($this->dsn, $this->username, $this->password, [
			\PDO::ATTR_PERSISTENT => true,
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
		]);

		if ($this->databaseProfilingService)
			$this->databaseProfilingService->signalConnection($this->dsn, round(1000 * (microtime(true) - $before)));

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
