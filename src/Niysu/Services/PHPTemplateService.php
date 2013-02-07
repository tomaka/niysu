<?php
namespace Niysu\Services;

/**
 * Service which allows to use PHP templates.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class PHPTemplateService {
	public function __construct(\Niysu\Server $server, \Monolog\Logger $log = null) {
		$this->server = $server;
		$this->log = $log;
	}

	/**
	 * Processes a template.
	 *
	 * This function doesn't return anything but calls the third parameter with data as first parameter.
	 *
	 * @param string $template 				PHP source code to process as a template
	 * @param Scope $scope 					The scope that will be used to read variables from
	 * @param callable $outputFunction 		Function that will be called to output the result
	 */
	public function render($template, \Niysu\Scope $scope, $outputFunction) {
		$compiledPHP = $this->compileTemplate($template);

		ob_start();
		$currentObNestLevel = ob_get_level();

		// defining all functions accessible from within the template
		// flush
		$_niysu_function_flush = function() use ($outputFunction) {
			ob_end_flush();
			$content = ob_get_contents();
			ob_end_clean();
			$outputFunction($content);
			ob_start();
			ob_start(function($str) { return htmlentities($str); });
		};

		// path
		$_niysu_function_path = function($name, $params = []) {
			$route = $this->server->getRouteByName($name);
			if (!$route) {
				if ($this->log)	$this->log->err('Unable to find route named '.$name.' in PHP template');
				return '';
			}

			if (!isset($params) || !is_array($params))
				$params = [];

			try {
				return $route->getURL($params);

			} catch(\Exception $e) {
				if ($this->log) $this->log->err('Unable to build route URL for '.$name.' in PHP template', [ 'params' => $params ]);
				return '';
			}
		};

		try {
			// we'll execute the template in an isolated scope
			call_user_func(function() use ($compiledPHP, $scope, $_niysu_function_flush, $_niysu_function_path) {
				// executing the template
				eval('unset($compiledPHP);?>'.$compiledPHP);
			});
			
		} catch(\Exception $e) {
			while (ob_get_level() > $currentObNestLevel)
				ob_end_flush();
			ob_end_clean();
			throw $e;
		}

		// closing all inner output buffers
		while (ob_get_level() > $currentObNestLevel)
			ob_end_flush();
		if (ob_get_level() != $currentObNestLevel)
			throw new \RuntimeException('Template called ob_end_clean or ob_end_flush too many times');

		// final output
		$content = ob_get_contents();
		ob_end_clean();
		if ($content)
			$outputFunction($content);
	}



	private function compileTemplate($template) {
		$tokens = token_get_all($template);

		$compiledPHP = '';
		foreach ($tokens as $token) {
			if (is_string($token)) {
				$compiledPHP .= $token;

			} else if ($token[0] == T_OPEN_TAG) {
				$compiledPHP .= $token[1].' ob_start(function($str) { return htmlentities($str); });';

			} else if ($token[0] == T_OPEN_TAG_WITH_ECHO) {
				$compiledPHP .= '<?php ob_start(function($str) { return htmlentities($str); }); echo ';

			} else if ($token[0] == T_CLOSE_TAG) {
				$compiledPHP .= ';ob_end_flush();'.$token[1];

			} else if ($token[0] == T_VARIABLE) {
				$compiledPHP .= '$scope->'.substr($token[1], 1);

			} else if ($token[0] == T_STRING && ($token[1] == 'flush' || $token[1] == 'path')) {
				$compiledPHP .= '$_niysu_function_'.$token[1];

			} else if ($token[0] == T_STRING && substr($token[1], 0, 3) == 'ob_') {
				throw new \LogicException('Output buffer functions are not available in PHP templates');

			} else {
				$compiledPHP .= $token[1];
			}
		}

		return $compiledPHP;
	}


	private $server;
	private $log;
};
