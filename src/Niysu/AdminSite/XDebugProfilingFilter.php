<?php
namespace Niysu\AdminSite;

/**
 * Filter necessary to use xdebug profiling in the AdminSite.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class XDebugProfilingFilter extends \Niysu\HTTPResponseFilterInterface {
	/**
	 * If profiling is enabled, will send back a header containing the profiling file name.
	 */
	public function __construct(\Niysu\HTTPResponseInterface $response) {
		parent::__construct($response);

		if (function_exists('xdebug_get_profiler_filename'))
			$val = xdebug_get_profiler_filename();

		if ($val)
			$this->setHeader('X-XDebugFilename', $val);
	}
}

?>