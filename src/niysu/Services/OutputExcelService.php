<?php
namespace Niysu\Services;

/**
 * Allows to easily send Excel documents to the response.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class OutputExcelService {
	public function __construct(\Niysu\Scope $scope) {
		$this->scope = $scope;
	}

	/**
	 * Converts an array representing an Excel document and sends it to the response.
	 *
	 * The array should have the format cell => infos.
	 * The cell must be 'A1', 'B6', etc.
	 * The infos can be either a string, or an array containing some style:
	 *  - bold: true/false
	 *  - data: the content of the cell ; start by '=' for formulas
	 *  - format: format of the numbers in the cell, 'General' is the default
	 *  - frozen: true/false, don't know what this does
	 *  - height: height of the row
	 *  - italic: true/false
	 *  - merge: cell, merges a cells in a rect between the current one and the one of this parameter
	 *  - value: alternative to data property
	 *  - width: width of the column
	 *
	 * @param array 	$data 					Data representing Excel
	 * @param string 	$attachementFilename 	If non-null, will send a Content-Disposition header will this filename
	 */
	public function output($data, $attachmentFilename = null) {
		if (!isset($this->scope->response))
			throw new \LogicException('Cannot be called from outside a route');
		
		$this->scope->response->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		if ($attachmentFilename)
			$this->scope->response->setHeader('Content-Disposition', 'attachment; filename='.$attachmentFilename);

		$excelDoc = $this->convertArray($data);
		$writer = new \PHPExcel_Writer_Excel2007($excelDoc);

		$tempFile = tempnam(sys_get_temp_dir(), 'NiysuExcel');
		$writer->save($tempFile);
		$this->scope->response->appendData(file_get_contents($tempFile));
		unlink($tempFile);
	}



	private function convertArray($sourceData) {
		$doc = new \PHPExcel();
		$sheet = $doc->getActiveSheet();

		foreach ($sourceData as $cell => $infos) {
			// checking cell format
			if (!preg_match('/^([[:alpha:]]+)(\\d+)$/i', $cell, $cellMatches))
				throw new \LogicException('Wrong cell: '.$cell);
			list(, $cellLetter, $cellDigit) = $cellMatches;

			// if $infos is string, then writing this and returning
			if (is_string($infos)) {
				$sheet->setCellValue($cell, $infos);
				continue;
			}

			// now $infos must be an array
			if (!is_array($infos))
				throw new \LogicException('Cell definition must be either a string or an array');

			// studying array
			$style = $sheet->getStyle($cell);
			$styleFont = $style->getFont();
			$styleFont->applyFromArray($infos);


			// data
			if (isset($infos['data']))
				$sheet->setCellValue($cell, $infos['data']);
			else if (isset($infos['value']))
				$sheet->setCellValue($cell, $infos['value']);

			// the format property
			if (isset($infos['format']))
				$style->getNumberFormat()->applyFromArray([ 'code' => $infos['format'] ]);

			// the frozen property
			if (isset($infos['frozen']))
				$sheet->freezePane($cell);

			// the height property
			if (isset($infos['height']))
				$sheet->getRowDimension($cellDigit)->setRowHeight(intval($infos['height']));

			// the merge property allows to merge with another cell
			if (isset($infos['merge']))
				$sheet->mergeCells($cell.':'.$infos['merge']);

			// the width property
			if (isset($infos['width']))
				$sheet->getColumnDimension($cellLetter)->setWidth(intval($infos['width']));
		}

		return $doc;
	}

	private $scope;
};

?>