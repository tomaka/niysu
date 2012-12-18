<?php
namespace Niysu\Services;

class DatabaseServiceTable implements \Iterator, \ArrayAccess, \Countable {
	public function OrderBy($param) {
		$newThis = clone $this;
		if ($newThis->orderByClause !== null)		$newThis->orderByClause .= ', ';
		else 										$newThis->orderByClause = '';
		$newThis->orderByClause .= $param;
		$newThis->currentResultSet = null;
		return $newThis;
	}
	
	public function __construct(DatabaseService $service, $tableName, $colNameDelimiter = '"') {
		$this->service = $service;
		$this->tableName = $tableName;
		$this->colNameDelimiter = $colNameDelimiter;
	}

	public function __get($colName) {
		$this->buildResultSet();
		return $this->currentResultSet[$this->offsetClause][$colName];
	}

	public function __unset($varName) {
		$this->__set($varName, null);
	}

	public function __set($varName, $value) {
		$otherMe = $this->__get($varName);
		$otherMe->execUpdate([$varName => $value]);

		$setClauses = [];
		$setParams = [];
		foreach ($changes AS $key => $value) {
			$setClauses[] = $this->colNameDelimiter.$key.$this->colNameDelimiter.' = ?';
			$setParams[] = $value;
		}

		// TODO: update only nth entry
		$sql = 'UPDATE '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter.' AS '.$this->tableAlias.' SET '.implode(', ', $setClauses).($this->whereClause == null ? '' : ' WHERE '.$this->whereClause);
		try { $this->logService->debug('SQL query: '.$sql); } catch(\Exception $e) {}
		$query = $this->databasePDO->prepare($sql);
		$query->execute(array_merge($setParams, $this->whereParams));
		self::$numQueries++;
	}

	public function current() {
		return $this->currentResultSet[$this->currentTraversedRow];
	}

	public function key() {
		return $this->currentTraversedRow;
	}

	public function next() {
		if ($this->currentResultSet === null)
			$this->rewind();
		$this->currentTraversedRow++;
	}

	public function rewind() {
		$this->currentTraversedRow = 0;
		if ($this->offsetClause !== null)
			$this->currentTraversedRow = $this->offsetClause;

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
		if ($offset === null) {
			// handling the $something[] = [...] syntax
			// insertion in table
			if (!is_array($value))
				throw new \LogicException('Value for [] must be an array');

			$fields = [];
			$vals = [];
			foreach ($value as $k => $v) {
				if (is_numeric($k))
					continue;
				$fields[] = $this->colNameDelimiter.$k.$this->colNameDelimiter;
				$vals[] = '?';
			}

			$sql = 'INSERT INTO '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter.(empty($fields) ? '' : ' ('.implode(', ', $fields).') VALUES ('.implode(', ', $vals).')');
			$this->service->execute($sql, array_values($value));

		} else {
			// 
			$other = $this->addWhereClause($offset);
			if ($value === null) {
				$sql = 'DELETE FROM '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter.($this->whereClause === null ? '' : ' WHERE '.$this->whereClause);
				$this->service->execute($sql, $this->whereParams);

			} else {
				throw new \LogicException('Unvalid syntax: $databaseService->table[something] = value if value is not null');
			}
		}
	}

	public function offsetUnset($offset) {
		return $this->offsetSet($offset, null);
	}

	public function count() {
        var_dump(__METHOD__);
		$this->buildResultSet();
		return count($this->currentResultSet);
	}


	// add a [$offset] behind the object and returns the new one
	private function addWhereClause($offset) {
		if (is_numeric($offset)) {
			// first case: a numeric offset specifies
			if ($this->offsetClause !== null && $offset != $this->offsetClause)
				throw \LogicException('Cannot specify two offsets at the same time');

			$newThis = clone $this;
			$newThis->offsetClause = $offset;
			if ($newThis->currentResultSet && isset($newThis->currentResultSet[$offset]))
				$newThis->currentResultSet = [ $offset => $newThis->currentResultSet[$offset] ];
			return $newThis;

		} else if (is_string($offset)) {
			// second case: a string offset, a where clause
			$newThis = clone $this;
			$newThis->currentResultSet = null;
			if ($newThis->whereClause != '')
				$newThis->whereClause .= ' AND ';
			$newThis->whereClause .= $offset;
			return $newThis;

		} else if (is_array($offset)) {
			// third case: an array, a where clause
			$newThis = clone $this;
			$newThis->currentResultSet = null;
			foreach ($offset as $key => $value) {
				if (is_numeric($key))
					continue;
				if ($newThis->whereClause != '')
					$newThis->whereClause .= ' AND ';
				$newThis->whereClause .= $this->colNameDelimiter.$key.$this->colNameDelimiter.' = ?';
				$newThis->whereParams[] = $value;
			}
			return $newThis;

		} else {
			throw new \LogicException('Can only pass numbers, strings or arrays inside brackets');
		}
	}
	
	private function buildResultSet() {
		if ($this->currentResultSet !== null)
			return;

		$sql  = 'SELECT * ';
		$sql .= 'FROM '.$this->colNameDelimiter.$this->tableName.$this->colNameDelimiter;
		$sql .= ($this->whereClause !== null ? ' WHERE '.$this->whereClause : '');
		$sql .= ($this->orderByClause !== null ? ' ORDER BY '.$this->orderByClause : '');

		$this->currentResultSet = $this->service->query($sql);
	}
	

	private $service;
	private $tableName;
	private $colNameDelimiter = '"';

	// variables that localise the value we want
	private $whereClause = null;			// where clause, doesn't contain the "where" word, eg. "id = 5 and email is null"
	private $whereParams = [];				// parameters to pass to the prepared statement corresponding to the where clause
	private $orderByClause = null;			// sql part for "order by" (doesn't contain the "order by" keyword)
	private $offsetClause = null;			// the data we want is the nth result

	private $currentResultSet = null;		// the content of the database for the given configuration ; always in the form $currentResultSet[offset][field name]

	private $currentTraversedRow = 0;		// when iterating through ourselves, this stores the current row id
};

?>