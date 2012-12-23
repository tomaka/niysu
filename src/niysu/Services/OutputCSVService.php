<?php
namespace Niysu\Services;

/**
 * Allows to easily send CSV data to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class OutputCSVService {
	public function __construct(\Niysu\Scope $scope) {
		$this->scope = $scope;
	}

	/**
	 * Turns an 2D-array into a CSV string.
	 *
	 * The $csv parameter must be an array of rows.
	 * Each row must be an array of columns.
	 *
	 * @param array 	$csv 			2D array representing CSV
	 * @param string 	$separator		The separator to use
	 * @return string
	 */
	public function toString($csv, $separator = ';') {
		$fp = fopen('php://memory', 'r+');
		foreach ($data as $row)
			fputcsv($fp, $row, $separator);
		rewind($fp);
		$data = stream_get_contents($fp);
		fclose($fp);
		return $data;
	}

	/**
	 * Turns a CSV array into a string and sends it to the response.
	 *
	 * @param array 	$csv 		Data representing CSV
	 */
	public function output($csv) {
		if (!isset($this->scope->response))
			throw new \LogicException('Cannot be called from outside a route');
		
		$this->scope->response->setHeader('Content-Type', 'text/csv');
		$this->scope->response->appendData($this->toString($csv));

	}


	private $scope;
};

?>