<?php
namespace Niysu\Output;

/**
 * Send a PDF file to the response using TCPDF.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class TCPDFOutput implements \Niysu\OutputInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response) {
		if (!class_exists('\\TCPDF'))
			throw new \LogicException('TCPDF must be installed to use TCPDFOutput');

		$this->outputResponse = $response;
		$this->pdf = new \TCPDF();
	}

	
	/**
	 * Invokes TCPDF.
	 * Every function call you make is redirected to TCPDF.
	 */
	public function __call($function, $arguments) {
		$this->active = true;
		return call_user_func_array([ $this->pdf, $function ], $arguments);
	}

	public function flush() {
		if (!$this->active)
			return;

		$data = $this->pdf->Output('', 'S');

		$this->outputResponse->setHeader('Content-Length', strlen($data));
		$this->outputResponse->setHeader('Content-Type', 'application/pdf');
		$this->outputResponse->appendData($data);
	}


	private $outputResponse;
	private $pdf;
	private $active = false;
};
