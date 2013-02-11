<?php
namespace Niysu\Output;

/**
 * Send CSV data to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class CSVOutput implements \Niysu\OutputInterface {
	public function __construct(\Niysu\HTTPResponseInterface $next) {
		$this->outputResponse = $next;
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
		foreach ($csv as $row)
			fputcsv($fp, $row, $separator);
		rewind($fp);
		$csv = stream_get_contents($fp);
		fclose($fp);
		return $csv;
	}

	/**
	 * Adds a row to the CSV data that will be answered.
	 *
	 * @param array 	$csv 			Array of strings representing the row
	 * @param string 	$separator		The separator to use
	 */
	public function addCSVRow($row, $separator = ';') {
		$fp = fopen('php://memory', 'r+');
		fputcsv($fp, $row, $separator);
		rewind($fp);
		$csv = stream_get_contents($fp);
		fclose($fp);
		$this->data .= $csv;
	}

	/**
	 * Sets the CSV data to send when the response is sent.
	 *
	 * See toString for the format to use.
	 *
	 * @param array 	$csv 		Data representing CSV
	 * @param string 	$separator		The separator to use
	 */
	public function setCSVData($csv, $separator = ';') {
		$this->data = $this->toString($csv, $separator);
	}



	public function flush() {
		$this->outputResponse->setHeader('Content-Type', 'text/csv');
		$this->outputResponse->appendData($this->data);
	}


	private $outputResponse;
	private $data;
};
