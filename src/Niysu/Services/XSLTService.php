<?php
namespace Niysu\Services;

class XSLTService {
	public function __construct($response) {
		if (!extension_loaded('xsl'))
			throw new \LogicException('The php_xsl extension must be activated in order to use XSLTService');

		$this->response = $response;
	}
	
	public function transform($template, $xml) {
		if (is_array($xml)) {
			$xmldoc = new \DOMDocument();
			$xmldoc->encoding = 'utf-8';
			$xmldoc->recover = true;
			$data = \Niysu\XMLOutput::writeXML($xml);
			if (!$xmldoc->loadXML($data))
				throw new \RuntimeException('Unable to parse XML data: '.$data);

		} else if (is_string($xml)) {
			$xmldoc = new \DOMDocument();
			$xmldoc->encoding = 'utf-8';
			$xmldoc->recover = true;
			if (!$xmldoc->loadXML($xml))
				throw new \RuntimeException('Unable to parse XML file: '.$xml);

		} else {
			throw new \LogicException('Wrong format for XML');
		}

		$xslDoc = new \DOMDocument();
		$xslDoc->load($template);
		if (!$xslDoc)
			throw new \RuntimeException('Unable to load XSLT template: '.$template);
		
		$xsl = new \XSLTProcessor();
		$xsl->registerPHPFunctions();
		$xsl->importStyleSheet($xslDoc);

		$output = $xsl->transformToXML($xmldoc);
		if (!$output)	throw new \RuntimeException('Error during XSLT transformation');
		return $output;
	}
	
	public function output($template, $xml, $response = null) {
		if (!$response)
			$response = $this->response;
		if (!$response)
			throw new \LogicException('If you call XSLTService from outside a route, you have to pass a response as argument');

		$output = $this->transform($template, $xml);
		$response->setHeader('Content-Type', 'application/xml');
		$response->appendData($output);
	}
	
	private $response = null;
};

?>