<?php
namespace Niysu\Filters;

/**
 * This filter will add a debug panel to any HTML response.
 *
 * This debug panel shows:
 *  - informations about PHP (version, extensions)
 *  - the time it took to build the page
 *  - memory usage
 *  - the list of SQL queries and the time it took to execute them
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class DebugPanelResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;

	public function __construct(\Niysu\HTTPResponseInterface $response, $twigService, $scope, $databaseProfilingService) {
		$this->twigService = $twigService;
		$this->databaseProfilingService = $databaseProfilingService;
		$this->scope = $scope;

		$this->outputResponse = $response;
	}

	public function setHeader($header, $value) {
		if (strtolower($header) == 'content-type')
			$this->goodContentType = self::testContentType($value);
		$this->outputResponse->setHeader($header, $value);
	}

	public function addHeader($header, $value) {
		if (strtolower($header) == 'content-type')
			$this->goodContentType = self::testContentType($value);
		$this->outputResponse->addHeader($header, $value);
	}

	public function appendData($data) {
		if (!$this->goodContentType) {
			$this->outputResponse->appendData($data);
			return;
		}

		if (!preg_match('/\\<\\/body\\>/i', $data, $matches, PREG_OFFSET_CAPTURE)) {
			$this->outputResponse->appendData($data);
			return;
		}
		
		$evaluatedPanel = $this->twigService->render(self::$panelTemplate, [
			'version' => 'PHP '.phpversion().' on '.php_uname('s'),
			'extensions' => get_loaded_extensions(),
			'timeElapsed' => call_user_func($this->scope->elapsedTime),
			'peakMemory' => self::formatBytes(memory_get_peak_usage()),
			'numQueries' => $this->databaseProfilingService->getNumberOfQueries(),
			'queriesList' => $this->databaseProfilingService->getQueriesList(),
			'queriesTime' => $this->databaseProfilingService->getQueriesTotalDuration(),
			'connectionMS' => $this->databaseProfilingService->getTotalConnectionDuration(),
			'totalDatabaseTime' => $this->databaseProfilingService->getTotalConnectionDuration() + $this->databaseProfilingService->getQueriesTotalDuration(),
			/*'sessionID' => $this->scope->sessionService->getID(),
			'sessionVariables' => $this->scope->sessionService->getVariables()*/
		]);
		
		$splitOffset = $matches[0][1];
		$data = substr($data, 0, $splitOffset).$evaluatedPanel.substr($data, $splitOffset);
		$this->outputResponse->appendData($data);
	}


	
	private static function formatBytes($bytes, $precision = 2) { 
	    $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB'); 

	    $bytes = max($bytes, 0); 
	    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	    $pow = min($pow, count($units) - 1);
	    $bytes /= pow(1024, $pow);

	    return round($bytes, $precision) . ' ' . $units[$pow]; 
	}

	private static function testContentType($value) {
		return strpos($value, 'text/html') === 0;
	}
	
	
	private $goodContentType = false;
	private $databaseProfilingService;
	private $twigService;
	private $scope;
	
	private static $panelTemplate =
		'<div style="clear:both; height:40px;"></div>
		<div style="font-size:initial; font-family:Verdana,sans-serif; color:black; position:fixed; left:0; bottom:0; width:100%; padding:0.5em 1em; background-color:gray; border-top:3px double black;">
			<em><a style="color:darkblue; text-decoration:inherit;" href="https://github.com/Tomaka17/niysu">Niysu debug panel</a></em>
			<span style="margin-left:2em;">
				<span
					onmouseover="this.querySelector(&quot;div&quot;).style.display=&quot;block&quot;"
					onmouseout="this.querySelector(&quot;div&quot;).style.display=&quot;none&quot;"
					style="background-color:#bbb; border:2px solid #222; border-radius:0.3em; padding:0.1em 0.3em; position:relative;"
				>
					<strong>{{ version }}</strong>
					<div style="display:none; background-color:#eee; border:2px solid black; border-radius:10px; padding:1em 2em; position:absolute; bottom:0.7em; left:-50px; width:600px; max-height:40em; overflow-y:auto;">
						<p style="font-weight:bold;">{{ version }}</p>
						<p><strong>Extensions list</strong>
						{% for e in extensions | sort %}
							<br />{{ e }}
						{% endfor %}
						</p>
					</div>
				</span>
			</span>
			<span style="margin-left:2em;">Time to build this page: {{ (1000 * timeElapsed) | number_format }} ms</span>
			<span style="margin-left:2em;">Peak memory: {{ peakMemory }}</span>
			<span style="margin-left:2em;">
				<span
					onmouseover="this.querySelector(&quot;div&quot;).style.display=&quot;block&quot;"
					onmouseout="this.querySelector(&quot;div&quot;).style.display=&quot;none&quot;"
					style="background-color:#bbb; border:2px solid #222; border-radius:0.3em; padding:0.1em 0.3em; position:relative;"
				>
					<img style="height:1em;" alt="Queries" src="data:image/png;base64,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" />
					<strong>{{ totalDatabaseTime }} ms</strong>
					<div style="display:none; background-color:#eee; border:2px solid black; border-radius:10px; padding:1em 2em; position:absolute; bottom:0.7em; left:-300px; width:600px; max-height:40em; overflow-y:auto;">
						<ul style="list-style-type:none; padding:0; margin:0;">
							<li style="margin-bottom:1em;">Connection: <strong>{{ connectionMS }}</strong> ms</li>
							{% for q in queriesList %}
								<li style="margin-bottom:1em;"><strong>Query in {{ q.time }}ms:</strong><br /><em>{{ q.sql }}</em></li>
							{% endfor %}
							<li>Total: <strong>{{ numQueries }}</strong> queries in <strong>{{ queriesTime }}</strong> ms</li>
						</ul>
					</div>
				</span>
			</span>
			<span style="margin-left:2em;">
				<span
					onmouseover="this.querySelector(&quot;div&quot;).style.display=&quot;block&quot;"
					onmouseout="this.querySelector(&quot;div&quot;).style.display=&quot;none&quot;"
					style="background-color:#bbb; border:2px solid #222; border-radius:0.3em; padding:0.1em 0.3em; position:relative;"
				>
					<strong>Session: {{ sessionID ? "on" : "off" }}</strong>
					<div style="display:none; background-color:#eee; border:2px solid black; border-radius:10px; padding:1em 2em; position:absolute; bottom:0.7em; left:-50px; width:600px; max-height:40em; overflow-y:auto;">
						<p style="font-weight:bold;">Session ID: {{ sessionID }}</p>
						<p><strong>Variables</strong>
						{% for key, val in sessionVariables %}
							<br /><strong>{{ key }}</strong>: {{ val }}
						{% endfor %}
						</p>
					</div>
				</span>
			</span>
		</div>';
};

?>