<?php
namespace Niysu;

/**
 * Interface for the response of an HTTP request
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
interface HTTPResponseInterface {
	/**
	 * Sets the status code to return with the headers.
	 *
	 * @pre !isHeadersListSent()
	 * @param integer 	$statusCode 	The status code
	 */
	public function setStatusCode($statusCode);

	/**
	 * Adds a header to the headers list.
	 *
	 * @pre !isHeadersListSent()
	 * @param string 	$header 	Header to add
	 * @param string 	$value 		Value of the header
	 */
	public function addHeader($header, $value);

	/**
	 * Removes all headers of this name and adds a new one.
	 *
	 * Equivalent to "removeHeader($header); addHeader($header, $value);"
	 *
	 * @pre !isHeadersListSent()
	 * @param string 	$header 	Header to add
	 * @param string 	$value 		Value of the header
	 */
	public function setHeader($header, $value);

	/**
	 * Removes all headers with this name.
	 *
	 * @pre !isHeadersListSent()
	 * @param string 	$header 	Name of the headers to remove
	 */
	public function removeHeader($header);

	/**
	 * Returns true if the response has already sent its headers and is now sending data.
	 *
	 * If true, then you can't modify headers anymore.
	 *
	 * @return boolean
	 */
	public function isHeadersListSent();

	/**
	 * Appends data to the end of the response.
	 *
	 * Note that this may trigger a headers sending if $data is not empty.
	 *
	 * @param string 	$data 		Data to append
	 */
	public function appendData($data);

	/**
	 * Flushes the response at the end.
	 *
	 * Nothing else must be modified after this call.
	 */
	public function flush();
};
