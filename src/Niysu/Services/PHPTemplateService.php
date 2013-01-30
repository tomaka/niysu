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

		try {
			// executing the template
			call_user_func(function() use ($compiledPHP, $scope) {
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

			} else if ($token[0] == T_STRING && $token[1] == 'flush') {
				$compiledPHP .= $token[1];

			} else {
				$compiledPHP .= $token[1];
			}
		}

		return $compiledPHP;
	}
};

?>