<?php
namespace Niysu\Output;

/**
 * Allows to easily send Excel documents to the response using PHPExcel.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class PHPExcelOutput implements \Niysu\OutputInterface {
	public function __construct(\Niysu\HTTPResponseInterface $next) {
		$this->outputResponse = $next;
		$this->excelDoc = new \PHPExcel();
	}

	/**
	 * Invokes PHPExcel.
	 * Every function call you make is redirected to PHPExcel.
	 */
	public function __call($function, $arguments) {
		call_user_func_array([ $this->excelDoc, $function ], $arguments);
	}


	public function flush() {
		$this->outputResponse->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

		$writer = new \PHPExcel_Writer_Excel2007($this->excelDoc);

		$tempFile = tempnam(sys_get_temp_dir(), 'NiysuExcel');
		$writer->save($tempFile);
		$this->outputResponse->appendData(file_get_contents($tempFile));
		unlink($tempFile);
	}


	private $excelDoc;
	private $outputResponse;
};

?>