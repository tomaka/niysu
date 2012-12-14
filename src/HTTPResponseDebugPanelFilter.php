<?php
namespace Niysu;

class HTTPResponseDebugPanelFilter extends HTTPResponseFilter {
	public static function buildBeforeFilter() {
		return function(&$response, $elapsedTime, $server) { $response = new HTTPResponseDebugPanelFilter($response, $elapsedTime, $server); };
	}
	
	public function __construct(HTTPResponseInterface $output, $elapsedTime, $server) {
		$this->elapsedTime = $elapsedTime;
		$this->dbConnectionTime = function() use ($server) { return $server->getServiceProvider('database')->getConnectionDuration(); };
		parent::__construct($output);
	}
	
	public function __destruct() {
		$timeElapsed = call_user_func($this->elapsedTime);
		parent::setHeader('X-TimeElapsed', $timeElapsed);
		$dbConnectionTime = round(1000 * call_user_func($this->dbConnectionTime));

		if (preg_match('/\\<\\/body\\>/i', $this->dataBuffer, $matches, PREG_OFFSET_CAPTURE)) {
			eval('$evaluatedPanel = "'.addslashes(self::$panelTemplate).'";');

			$splitOffset = $matches[0][1];
			$this->dataBuffer = substr($this->dataBuffer, 0, $splitOffset).$evaluatedPanel.substr($this->dataBuffer, $splitOffset);	
		}
		
		$this->getOutput()->appendData($this->dataBuffer);

		parent::__destruct();
	}
		
	public function appendData($data) {
		$this->dataBuffer .= $data;
	}
	
	public function addHeader($header, $value) {
		if (strtolower($header) == 'content-type' && $value == 'text/html')
			$this->activatePanel = true;
		parent::addHeader($header, $value);
	}
	
	public function setHeader($header, $value) {
		if (strtolower($header) == 'content-type' && $value == 'text/html')
			$this->activatePanel = true;
		parent::setHeader($header, $value);
	}
	
	private $dataBuffer = '';
	private $activatePanel = false;
	private $elapsedTime = null;
	private $dbConnectionTime;

	
	private static $panelTemplate =
		'<div style="position:fixed; bottom:0; width:100%; padding:0.5em 1em; background-color:gray; border-top:2px double black;">$timeElapsed ms - $dbConnectionTime ms</div>';
};

?>