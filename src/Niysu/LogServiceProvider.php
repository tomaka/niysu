<?php
namespace Niysu;

/// \brief Class which allows to write in logs
/// \details Uses Monolog
class LogServiceProvider {
	public function __construct() {
		$this->monolog = new \Monolog\Logger('NiysuServer');
		$this->addLogHandler(__DIR__.'/../logs/log.txt');
	}
	
	public function __invoke() {
		return $this->monolog;
	}
	
	public function setLogsPath($path) {
		$this->monolog->popHandler();
		$this->addLogHandler($path);
	}
	
	
	private function addLogHandler($path) {
		$this->monolog->pushHandler(new \Monolog\Handler\RotatingFileHandler($path, 7));
	}
	
	private $monolog;
};

?>