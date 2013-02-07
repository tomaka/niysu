<?php
namespace Niysu;

/**
 * Interface for the response of an HTTP request
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
interface OutputInterface {
	/**
	 * Sends the response to the HTTPResponseInterface.
	 */
	public function flush();
};
