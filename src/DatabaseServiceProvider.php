<?php
namespace Niysu;

class DatabaseServiceProvider {
	public function __construct() {
	}

	public function setDatabase($dsn, $username, $password) {
		$this->dsn = $dsn;
		$this->username = $username;
		$this->password = $password;
		$this->databasePDO = null;
	}
	
	public function __invoke() {
		if (!$this->databasePDO) {
			$beforeConnection = microtime(true);
			if (!$this->dsn)	$this->databasePDO = new PDO('sqlite::memory:');
			else 				$this->databasePDO = new PDO($this->dsn, $this->username, $this->password);
			$this->databasePDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->databasePDO->exec('SET NAMES \'UTF8\'');
			$this->connectionDuration = microtime(true) - $beforeConnection;
		}

		return new DatabaseService($this->databasePDO);
	}
	
	/// \brief Returns the number of seconds it took to connect to the database
	public function getConnectionDuration() {
		return $this->connectionDuration;
	}
	
	
	private $databasePDO;
	private $dsn;
	private $username;
	private $password;
	private $connectionDuration;
};

?>