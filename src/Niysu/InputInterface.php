<?php
namespace Niysu;

/**
 * Interface for an object that reads data from a request.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
interface InputInterface {
	/**
	 * Returns true if the data is in a valid format.
	 */
	public function isValid();
};
