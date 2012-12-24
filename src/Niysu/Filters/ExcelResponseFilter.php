<?php
namespace Niysu\Filters;

/**
 * Allows to easily send Excel documents to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class ExcelResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $next) {
		parent::__construct($next);
		$this->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

		$this->excelDoc = new \PHPExcel();
	}

	/**
	 * Changes the value inside a cell
	 *
	 * @param string 	$cell 		The cell to change (eg. 'A1')
	 * @param string 	$value 		New value of the cell (formulas start by '=')
	 * @param integer 	$sheet 		Numero of the sheet to modify
	 */
	public function setCellValue($cell, $value, $sheet = null) {
		$this->getSheet($sheet)->setCellValue($cell, $value);
	}

	/**
	 * Changes the style of a cell
	 *
	 * @param string 	$cell 		The cell to change (eg. 'A1')
	 * @param array 	$style 		New style of the cell
	 * @param integer 	$sheet 		Numero of the sheet to modify
	 */
	public function setCellStyle($cell, $style, $sheet = null) {
		$this->getSheet($sheet)->getStyle($cell)->applyFromArray($style);
	}


	public function flush() {
		$writer = new \PHPExcel_Writer_Excel2007($this->excelDoc);

		$tempFile = tempnam(sys_get_temp_dir(), 'NiysuExcel');
		$writer->save($tempFile);
		parent::appendData(file_get_contents($tempFile));
		unlink($tempFile);

		parent::flush();
	}

	public function appendData($data) {
	}


	private function getSheet($sheet) {
		if ($sheet === null)
			return $this->excelDoc->getActiveSheet();
		// TODO: if not enough sheets, create new ones
		return $this->excelDoc->getSheet($sheet);
	}

	private $excelDoc;
};

?>