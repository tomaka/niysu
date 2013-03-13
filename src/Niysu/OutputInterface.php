<?php
namespace Niysu;

/**
 * Interface for an object who should be flushed at the end of its lifetime
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
interface OutputInterface {
	/**
	 * Sends the response to the HTTPResponseInterface.
	 *
	 * This function should have no effect if the user did not explicitely specify that it wants to use the object
	 * For example $x = new Foo(); $x->flush(); should have no effect
	 */
	public function flush();
};
