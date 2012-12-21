<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
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

		$filesHandler = new \Monolog\Handler\RotatingFileHandler($path, 7);
		$fingersCrossedHandler = new \Monolog\Handler\FingersCrossedHandler($filesHandler);
		$this->monolog->pushHandler($fingersCrossedHandler);
	}
	
	private $monolog;
};

?>