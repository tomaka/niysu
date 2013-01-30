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

	public function render($template, \Niysu\Scope $scope) {
		$compiledPHP = $this->compileTemplate($template);

		try {
			$currentObNestLevel = ob_get_level();
			call_user_func(function() use ($compiledPHP, $scope) {
				eval('unset($compiledPHP);?>'.$compiledPHP);
			});
			while (ob_get_level() > $currentObNestLevel)
				ob_end_flush();

		} catch(\Exception $e) {
			while (ob_get_level() > $currentObNestLevel)
				ob_end_flush();
			throw $e;
		}
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

			} else {
				$compiledPHP .= $token[1];
			}
		}

		return $compiledPHP;
	}
};

?>