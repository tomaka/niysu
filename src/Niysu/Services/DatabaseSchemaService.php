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
		// this will contain the final result
		$result = '';

		// listing sequences
		foreach ($this->databaseService->query('SELECT * FROM INFORMATION_SCHEMA.SEQUENCES WHERE "sequence_schema" = \'public\'') as $sequence) {
			$result .= 'CREATE SEQUENCE "'.$sequence['sequence_name'].'"'."\r";
			$result .= 'INCREMENT '.$sequence['increment'].' MINVALUE '.$sequence['minimum_value'].' MAXVALUE '.$sequence['maximum_value'].' START '.$sequence['start_value'].' CACHE 1';
			$result .= ';'."\r\r";
		}

		// listing tables
		foreach ($this->databaseService->query('SELECT "table_name" FROM INFORMATION_SCHEMA.TABLES WHERE "table_schema" = \'public\' AND "table_type" = \'BASE TABLE\'') as $table) {
			$result .= 'CREATE TABLE "'.$table['table_name'].'" ('."\r";

			// adding columns
			foreach ($this->databaseService->query('SELECT "column_name", "data_type", "character_maximum_length", "numeric_precision", "column_default", "is_nullable" = \'YES\' FROM INFORMATION_SCHEMA.COLUMNS WHERE "table_name" = ? ORDER BY "ordinal_position"', [ $table['table_name'] ]) as $column) {
				$result .= "\t".'"'.$column['column_name'].'" '.$column['data_type'];
				if ($column['character_maximum_length'])		$result .= '('.$column['character_maximum_length'].')';
				else if ($column['numeric_precision'])			$result .= '('.$column['numeric_precision'].')';
				if ($column['column_default'])					$result .= ' DEFAULT '.$column['column_default'];
				if (!$column['is_nullable'])					$result .= ' NOT NULL';
				$result .= ','."\r";
			}

			// primary keys
			if ($primaryKey = $this->databaseService->query('SELECT c."constraint_name", k."column_name" FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS c LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k ON c."constraint_name" = k."constraint_name" WHERE c."table_name" = ? AND c."constraint_type" = \'PRIMARY KEY\'', [ $table['table_name'] ])) {
				$result .= "\t";
				$result .= ($primaryKey[0]['constraint_name'] ? 'CONSTRAINT "'.$primaryKey[0]['constraint_name'].'" ' : '').'PRIMARY KEY(';
				$resultKey = '';
				foreach ($primaryKey as $k)
					$resultKey .= ($resultKey == '' ? '' : ', ').'"'.$k['column_name'].'"';
				$result .= $resultKey.'),'."\r";
			}

			// TODO: foreign keys and checks

			// finishing
			if (substr($result, -2, 1) == ',')
				$result = substr($result, 0, -2)."\r";
			$result .= ');'."\r\r";
		}

		// listing views
		foreach ($this->databaseService->query('SELECT "table_schema", "table_name", "view_definition" FROM INFORMATION_SCHEMA.VIEWS WHERE "table_schema" = \'public\'') as $view) {
			// adding line breaks in the SQL definition
			$view['view_definition'] = str_replace('WHERE', "\r".'WHERE', $view['view_definition']);
			$view['view_definition'] = str_replace('UNION', "\r".'UNION', $view['view_definition']);
			$view['view_definition'] = str_replace('GROUP', "\r".'GROUP', $view['view_definition']);
			$view['view_definition'] = str_replace('FROM', "\r".'FROM', $view['view_definition']);
			$view['view_definition'] = str_replace('ORDER', "\r".'ORDER', $view['view_definition']);
			$view['view_definition'] = str_replace('LEFT', "\r".'LEFT', $view['view_definition']);
			$view['view_definition'] = str_replace('RIGHT', "\r".'RIGHT', $view['view_definition']);
			$view['view_definition'] = str_replace('INNER', "\r".'INNER', $view['view_definition']);
			$view['view_definition'] = str_replace('FULL', "\r".'FULL', $view['view_definition']);

			$result .= 'CREATE VIEW "'.$view['table_schema'].'"."'.$view['table_name'].'" AS '."\r";
			$result .= $view['view_definition'];
			$result .= ';'."\r\r";
		}

		// listing triggers
		foreach ($this->databaseService->query('SELECT "trigger_name", "action_timing", "event_manipulation", "event_object_schema", "event_object_table", "action_orientation", "action_condition", "action_statement", "action_reference_old_table", "action_reference_old_row", "action_reference_new_table", "action_reference_new_row" FROM INFORMATION_SCHEMA.TRIGGERS WHERE "trigger_schema" = \'public\'') as $trigger) {
			$result .= 'CREATE TRIGGER "'.$trigger['trigger_name'].'"'."\r";
			$result .= $trigger['action_timing'].' '.$trigger['event_manipulation'];
			$result .= ' ON "'.$trigger['event_object_schema'].'"."'.$trigger['event_object_table'].'"'."\r";
			if ($trigger['action_reference_old_table'] || $trigger['action_reference_old_row'] || $trigger['action_reference_new_table'] || $trigger['action_reference_new_row']) {
				$result .= 'REFERENCES';
				if ($trigger['action_reference_old_table'])		$result .= ' OLD TABLE AS "'.$trigger['action_reference_old_table'].'"';
				if ($trigger['action_reference_new_table'])		$result .= ' NEW TABLE AS "'.$trigger['action_reference_new_table'].'"';
				if ($trigger['action_reference_old_row'])		$result .= ' OLD ROW AS "'.$trigger['action_reference_old_row'].'"';
				if ($trigger['action_reference_new_row'])		$result .= ' NEW ROW AS "'.$trigger['action_reference_new_row'].'"';
			}
			$result .= 'FOR EACH '.$trigger['action_orientation']."\r";
			if ($trigger['action_condition'])
				$result .= 'WHEN '.$trigger['action_condition']."\r";
			$result .= $trigger['action_statement'];
			$result .= ';'."\r\r";
		}

		// finished
		return $result;
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