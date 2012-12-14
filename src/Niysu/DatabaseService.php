<?php
namespace Niysu;

class DatabaseService implements \Iterator, \ArrayAccess, \Countable {
	public function OrderBy($param) {
		$newThis = clone $this;
		if ($newThis->orderByClause !== null)		$newThis->orderByClause .= ', ';
		else 										$newThis->orderByClause = '';
		$newThis->orderByClause .= $param;
		$newThis->currentResultSet = null;
		return $newThis;
	}

	public function BeginTransaction() {
		if ($this->databasePDO->inTransaction())
			throw new \LogicException('A transaction is already in progress');
		if (!$this->databasePDO->beginTransaction())
			throw new \RuntimException('Error while starting a transaction');
	}

	public function Commit() {
		if (!$this->databasePDO->inTransaction())
			throw new \LogicException('No transaction is currently in progress');
		if (!$this->databasePDO->commit())
			throw new \RuntimException('Error during commit');
	}

	public function RollBack() {
		if (!$this->databasePDO->inTransaction())
			throw new \LogicException('No transaction is currently in progress');
		if (!$this->databasePDO->rollBack())
			throw new \RuntimException('Error during rollback');
	}
	
	/// \brief Executes a query and returns an array containing the results with PDO::FETCH_BOTH
	public function query($sql, $params = []) {
		if (is_null($params))
			$params = [];
		if (!is_array($params))
			$params = [$params];
		
		$query = $this->databasePDO->prepare($sql);
		$query->execute($params);
		return $query->fetchAll(\PDO::FETCH_BOTH);
	}
	
	/// \brief Executes a query and returns the first of the results with PDO::FETCH_BOTH, or null if no answer
	public function querySingle($sql, $parameters = []) {
		$r = $this->query($sql, $parameters);
		return count($r) > 0 ? $r[0] : null;
	}

	/// \brief Executes a query
	/// \note Use this for UPDATE, INSERT, DELETE, etc.
	public function execute($sql, $params = []) {
		if (!is_null($params) && !is_array($params))
			$params = array($params);
		$query = $this->databasePDO->prepare($sql);
		$query->execute($params);
	}
	
	/// \brief Returns the last insert ID
	public function getLastInsertID($sequenceName = null) {
		return $this->databasePDO->lastInsertId($sequenceName);
	}


	public function __construct($database) {
		if ($database instanceof \PDO) {
			$this->databasePDO = $database;
		} else if (is_string($database)) {
			$this->databasePDO = new \PDO($database, func_get_arg(1), func_get_arg(2));
			$this->databasePDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} else {
			throw new \LogicException('Parameter passed to DatabaseService constructor is not valid');
		}

		switch ($this->databasePDO->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
			case 'sqlite':		$this->colNameDelimiter = ''; break;
			case 'mysql':		$this->colNameDelimiter = '`'; break;
			case 'pgsql':		$this->colNameDelimiter = '"'; break;
			default:
				throw new \LogicException('Unknown PDO driver: '.$this->databasePDO->getAttribute(\PDO::ATTR_DRIVER_NAME));
		}
	}

	public function __get($varName) {
		if ($this->tableName === null) {
			$newThis = clone $this;
			$newThis->tableName = $varName;
			$newThis->tableAlias = uniqid('tb');
			return $newThis;

		} else if ($this->fieldName === null) {
			$newThis = clone $this;
			$newThis->fieldName = $varName;
			return $newThis;

		} else {
			// foreign key
			$newThis = $this->followForeignKey();
			$newThis->fieldName = $varName;
			return $newThis;
		}
	}

	public function __call($fnName, $args) {
		return $this->__get($fnName)->__invoke($args);
	}

	public function __invoke($args) {
		if ($this->currentResultSet === null)
			$this->buildResultSet();

		if ($this->offsetClause === null && isset($this->currentResultSet[0][$this->fieldName]))
			return $this->currentResultSet[0][$this->fieldName];
		if (!isset($this->currentResultSet[$this->offsetClause]))
			return null;
		if (!isset($this->currentResultSet[$this->offsetClause][$this->fieldName]))
			return null;
		return $this->currentResultSet[$this->offsetClause][$this->fieldName];
	}

	public function __toString() {
		if (!$this->tableName)
			return '';
		return (string)($this->__invoke([]));
	}

	public function __unset($varName) {
		$this->__set($varName, null);
	}

	public function __set($varName, $value) {
		$otherMe = $this->__get($varName);
		$otherMe->execUpdate([$varName => $value]);
	}

	public function current() {
		$newThis = clone $this;
		$newThis->offsetClause = $this->currentTraversedRow;
		$newThis->currentTraversedRow = 0;
		$newThis->currentResultSet = [$this->currentTraversedRow => $this->currentResultSet[$this->currentTraversedRow]];
		return $newThis;
	}

	public function key() {
		return $this->currentTraversedRow;
	}

	public function next() {
		if ($this->currentResultSet === null)
			return rewind();
		$this->currentTraversedRow++;
	}

	public function rewind() {
		$this->currentTraversedRow = 0;
		if ($this->offsetClause !== null)
			$this->currentTraversedRow = $this->offsetClause;

		if ($this->tableName)
			$this->buildResultSet();
	}

	public function valid() {
		if ($this->currentResultSet === null)
			$this->rewind();
		if ($this->offsetClause !== null && $this->currentTraversedRow != $this->offsetClause)
			return false;
		return isset($this->currentResultSet[$this->currentTraversedRow]);
	}

	public function offsetExists($offset) {
		return true;
	}

	public function offsetGet($offset) {
		return $this->addWhereClause($offset);
	}

	public function offsetSet($offset, $value) {
		// handling the $something[] = [...] syntax
		if ($offset === null) {
			if (!is_array($value))
				throw new \LogicException('Value for [] must be an array');
			if ($this->fieldName !== null)
				throw new \LogicException('You can only insert after a table name');

			$fields = [];
			$vals = [];
			foreach ($value as $k => $v) {
				if (is_numeric($k))
					continue;
				$fields[] = '"'.$k.'"';
				$vals[] = '?';
			}
			$sql = 'INSERT INTO '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter.(empty($fields) ? '' : ' ('.implode(', ', $fields).') VALUES ('.implode(', ', $vals).')');
			$query = $this->databasePDO->prepare($sql);
			$query->execute(array_values($value));
			return;
		}

		$other = $this->addWhereClause($offset);
		if ($value === null) {
			$other->destroyMe();
			return;
		}

	}

	public function offsetUnset($offset) {
		return $this->offsetSet($offset, null);
	}

	public function count() {
		if ($this->fieldName !== null)
			return $this->followForeignKey()->count();
		
		if ($this->currentResultSet === null)
			$this->buildResultSet();
		return count($this->currentResultSet);
	}


	// if our request targets a foreign key, we can follow it so we target the new table
	private function followForeignKey() {
		$createTable = $this->databasePDO->query('SHOW CREATE TABLE "'.$this->tableName.'"')->fetch()[1];
		if (!preg_match('/FOREIGN\\s+KEY\\s*\\("'.$this->fieldName.'"\\)\\s+REFERENCES\\s+"(.*?)"\s+\\("(.*?)"\\)/i', $createTable, $matches))
			throw new \RuntimException('The field '.$this->fieldName.' is not a foreign key');

		$newThis = clone $this;
		$newThis->tableName = $matches[1];
		$newThis->tableAlias = uniqid('tb');
		$newThis->fieldName = null;
		$newThis->offsetClause = null;
		$newThis->currentResultSet = null;
		$newThis->whereClause = null;

		if ($this->offsetClause !== null) {
			$newThis->joinClause = null;
			$newThis->whereClause = $newThis->tableAlias.'.'.$this->colNameDelimiter.$matches[2].$this->colNameDelimiter.' = ('.$this->generateSQLRequest().')';

		} else {
			if ($newThis->joinClause === null)
				$newThis->joinClause = '';
			$newThis->joinClause = 'INNER JOIN '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter.' AS '.$this->tableAlias.' ON '.$newThis->tableAlias.'."'.$matches[2].'" = '.$this->tableAlias.'."'.$this->fieldName.'"'.$newThis->joinClause;
			if ($this->whereClause)
				$newThis->joinClause .= ' AND '.$this->whereClause;
		}

		return $newThis;
	}

	// add a [$offset] behind the object and returns the new one
	private function addWhereClause($offset) {
		if (is_numeric($offset)) {
			if ($this->offsetClause !== null && $offset != $this->offsetClause)
				throw \LogicException('Cannot specify two offsets at the same time');
			$newThis = clone $this;
			$newThis->offsetClause = $offset;
			if ($newThis->currentResultSet && isset($newThis->currentResultSet[$offset]))
				$newThis->currentResultSet = [$offset => $newThis->currentResultSet[$offset]];
			return $newThis;

		} else {
			if ($this->fieldName !== null)
				return $this->followForeignKey()->addWhereClause($offset);

			if (is_string($offset)) {
				$newThis = clone $this;
				$newThis->currentResultSet = null;
				if ($newThis->whereClause != '')
					$newThis->whereClause .= ' AND ';
				$newThis->whereClause .= $offset;
				return $newThis;

			} else if (is_array($offset)) {
				$newThis = clone $this;
				$newThis->currentResultSet = null;
				foreach ($offset as $key => $value) {
					if (is_numeric($key))
						continue;
					if ($newThis->whereClause != '')
						$newThis->whereClause .= ' AND ';
					$newThis->whereClause .= $newThis->tableAlias.'.'.$this->colNameDelimiter.$key.$this->colNameDelimiter.' = ?';
					$newThis->whereParams[] = $value;
				}
				return $newThis;

			} else {
				throw new \LogicException('Can only pass numbers, strings or arrays inside brackets');
			}
		}
	}

	private function generateSQLRequest() {
		if (!$this->tableName || !$this->tableAlias)
			throw new \LogicException('Trying to retrive data of an undefined table');

		$sql  = 'SELECT '.$this->tableAlias.'.'.($this->fieldName === null ? '*' : $this->colNameDelimiter.$this->fieldName.$this->colNameDelimiter);
		$sql .= ' FROM '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter.' AS '.$this->tableAlias;
		$sql .= ($this->joinClause !== null ? ' '.$this->joinClause : '');
		$sql .= ($this->whereClause !== null ? ' WHERE '.$this->whereClause : '');
		$sql .= ($this->orderByClause !== null ? ' ORDER BY '.$this->orderByClause : '');
		$sql .= ($this->offsetClause !== null ? ' LIMIT '.$this->offsetClause.', 1' : '');
		//echo $sql.'<br/>';
		return $sql;
	}

	private function buildResultSet() {
		$sql = $this->generateSQLRequest();
		//echo $sql;
		$query = $this->databasePDO->prepare($sql);
		$query->execute($this->whereParams);
		self::$numQueries++;

		$this->currentResultSet = $query->fetchAll(\PDO::FETCH_ASSOC);
	}

	private function execUpdate($changes) {
		$setClauses = [];
		$setParams = [];
		foreach ($changes AS $key => $value) {
			$setClauses[] = $this->colNameDelimiter.$key.$this->colNameDelimiter.' = ?';
			$setParams[] = $value;
		}

		// TODO: update only nth entry
		$sql = 'UPDATE '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter.' AS '.$this->tableAlias.' SET '.implode(', ', $setClauses).($this->whereClause == null ? '' : ' WHERE '.$this->whereClause);
		//echo $sql;
		$query = $this->databasePDO->prepare($sql);
		$query->execute(array_merge($setParams, $this->whereParams));
		self::$numQueries++;
	}

	private function destroyMe() {
		$sql = 'DELETE FROM '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter.' AS '.$this->tableAlias.($this->joinClause === null ? '' : $this->joinClause).($this->whereClause === null ? '' : ' WHERE '.$this->whereClause);
		$query = $this->databasePDO->prepare($sql);
		$query->execute($whereParams);
		self::$numQueries++;
	}
	
	public static function GetTotalQueriesCount() {
		return self::$numQueries;
	}


	private $databasePDO = null;
	private $colNameDelimiter = '"';

	// variables that localise the value we want
	private $tableName = null;				// table containing the data we want
	private $tableAlias = null;				// uniqid that will be used as an alias for the current table
	private $joinClause = null;				// inner joins, left joins, etc.
	private $fieldName = null;				// field containing the data we want
	private $whereClause = null;			// where clause, doesn't contain the "where" word, eg. "id = 5 and email is null"
	private $whereParams = [];				// parameters to pass to the prepared statement corresponding to the where clause
	private $orderByClause = null;			// sql part for "order by" (doesn't contain the "order by" keyword)
	private $offsetClause = null;			// the data we want is the nth result

	private $currentResultSet = null;		// the content of the database for the given configuration ; always in the form $currentResultSet[offset][field name]

	private $currentTraversedRow = 0;		// when iterating through ourselves, this stores the current row id

	static private $numQueries = 0;
};

?>