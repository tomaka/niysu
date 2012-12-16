<?php
namespace Niysu\Services;

/// \brief Class which allows to write in logs
/// \details Uses Monolog
class LogServiceProvider {
	public function __construct() {
		$this->monolog = new \Monolog\Logger('NiysuServer');
		try { $this->addLogHandler('./logs/log.txt'); } catch (\Exception $e) {}
	}
	
	public function __invoke() {
		return $this->monolog;
	}
	
	public function setLogsPath($path) {
		try { $this->monolog->popHandler(); } catch (\Exception $e) {}
		$this->addLogHandler($path);
	}
	
	
	private function addLogHandler($path) {
		if (!is_writable(dirname($path)))
			throw new \LogicException('Log path is not writable: '.dirname($path));

		$this->monolog->pushHandler(new \Monolog\Handler\RotatingFileHandler($path, 7));
	}
	
	private $monolog;
};

?>