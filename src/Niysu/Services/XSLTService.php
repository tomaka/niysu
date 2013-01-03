<?php
namespace Niysu\Services;

/**
 * This class allows for easy XSLT transformations.
 *
 * It requires the php_xsl extension to run.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class XSLTService {
	public function __construct(&$response, $outputXMLService) {
		if (!extension_loaded('xsl'))
			throw new \LogicException('The php_xsl extension must be activated in order to use XSLTService');

		$this->response =& $response;
		$this->outputXMLService = $outputXMLService;
	}
	
	/**
	 * Transforms XML using XSLT.
	 *
	 * The $xml parameter can be either raw XML or an array that can be passed to OutputXMLService::toString.
	 *
	 * @param string 	$template 	Name of the file containing the XSLT template
	 * @param mixed 	$xml 		The XML data to transform
	 * @throws RuntimeException In case of a problem when parsing the XML, the XSLT, or during the transformation
	 */
	public function transform($template, $xml) {
		if (is_array($xml)) {
			$xmldoc = new \DOMDocument();
			$xmldoc->encoding = 'utf-8';
			$xmldoc->recover = true;
			$data = $this->outputXMLService->toString($xml);
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
	
	/**
	 * Transforms XML using XSLT and writes it to the response.
	 *
	 * Same as transform, but outputs the data to the response. If no response is given, uses the response of the current route.
	 *
	 * @param string 					$template 	Name of the file containing the XSLT template
	 * @param mixed 					$xml 		The XML data to transform
	 * @param HTTPResponseInterface 	$response 	The response where to write the output, or null to use the response of the current route
	 */
	public function output($template, $xml, $response = null) {
		if (!$response)
			$response = $this->response;
		if (!$response)
			throw new \LogicException('If you call XSLTService from outside a route, you have to pass a response as argument');

		$output = $this->transform($template, $xml);
		$response->setHeader('Content-Type', 'application/xml');
		$response->appendData($output);
	}
	

	private $response;
	private $outputXMLService;
};

?>