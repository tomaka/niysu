<?php
namespace Niysu;

class HTTPResponseXSLTFilter extends HTTPResponseFilter {
	public static function buildBeforeFilter($pathToXSLT, $turnIntoHTML = false) {
		return function(&$response) use ($pathToXSLT, $turnIntoHTML) { $response = new HTTPResponseXSLTFilter($response, $pathToXSLT, $turnIntoHTML); };
	}
	
	public function __construct(HTTPResponseInterface $output, $pathToXSLT, $turnIntoHTML = false) {
		if (!extension_loaded('xsl'))
			throw new LogicException('The php_xsl extension must be activated');
		if (!file_exists($pathToXSLT))
			throw new LogicException('XSLT file passed to HTTPResponseXSLTFilter doesn\'t exist ("'.$pathToXSLT.'")');

		parent::__construct($output);
		$this->pathToXSLT = $pathToXSLT;
		$this->turnIntoHTML = $turnIntoHTML;
	}
	
	public function __destruct() {
		$xmldoc = new DOMDocument();
		$xmldoc->encoding = 'utf-8';
		$xmldoc->recover = true;

		try {
			@$xmldoc->loadXML($this->dataBuffer);
		} catch(Exception $e) {
			$xmldoc->loadXML('<fdmjc:page xmlns:fdmjc="http://www.fdmjc67.net"><fdmjc:body><p><em>Page non valide</em></p></fdmjc:body></fdmjc:page>');
		}
		
		$xsl = new XSLTProcessor();
		$xsl->registerPHPFunctions();
		$xsldoc = new DOMDocument();
		$xsldoc->load($this->pathToXSLT);
		$xsl->importStyleSheet($xsldoc);
		
		$xsl->setParameter('', 'pathToTemplate', substr(__DIR__, strlen($_SERVER['DOCUMENT_ROOT'])).'/../templates/public');
		
		$result = $xsl->transformToDoc($xmldoc);
		if ($result->getElementsByTagName('html')->item(0)) {
			$result->getElementsByTagName('html')->item(0)->removeAttributeNS('http://php.net/xsl', 'php');
			$result->getElementsByTagName('html')->item(0)->removeAttributeNS('http://www.fdmjc67.net', 'fdmjc');
		}

		if ($this->turnIntoHTML) {
			// replacing xml:lang by lang
			$body = $result->getElementsByTagName('body')->item(0);
			$body->setAttribute('lang', $body->getAttribute('xml:lang'));
		}

		//$result->normalizeDocument();
		$result->formatOutput = true;
		$resultText = $this->turnIntoHTML ? $result->saveHTML($result->documentElement) : $result->saveXML();

		if ($this->turnIntoHTML) {
			$resultText = preg_replace('/\\<(\w+) (.*)\\/\\>/', '<$1 $2></$1>', $resultText);
			$resultText = str_replace('<br></br>', '<br>', $resultText);
		}
		
		$this->getOutput()->setHeader('Content-Type', $this->turnIntoHTML ? 'text/html' : 'application/xhtml+xml');
		if ($this->turnIntoHTML)
			$this->getOutput()->appendData('<!DOCTYPE html>'.PHP_EOL);
		$this->getOutput()->appendData($resultText);

		parent::__destruct();
	}
		
	public function appendData($data) {
		$this->dataBuffer .= $data;
	}
	
	private $dataBuffer = '';
	private $pathToXSLT = null;
	private $turnIntoHTML = false;
};

function getPublicMenuXML() {
	$xmlWriter = new XMLWriter();
	$xmlWriter->openMemory();
	$xmlWriter->setIndentString(' ');
	$xmlWriter->setIndent(true);
	$xmlWriter->startDocument('1.0', 'utf-8');

	$xmlWriter->startElementNS('fdmjc', 'menu', 'http://www.fdmjc67.net');
	$xmlWriter->writeAttribute('xmlns', 'http://www.w3.org/1999/xhtml');

	/*$query = buildDatabaseConnection()->prepare('SELECT id, html_code FROM "fdmjc_menus" WHERE CASE :p WHEN NULL THEN parent IS NULL ELSE parent = :p END');
	$f = function($currentElement, $query) use ($xmlWriter, &$f) {
		$query->execute(array(':p' => $currentElement));
		$result = $query->fetchAll();
		foreach($result as $e) {
			$xmlWriter->startElementNS('fdmjc', 'element', null);
			$xmlWriter->startElementNS('fdmjc', 'content', null);
			$xmlWriter->writeRaw($e[1]);
			$xmlWriter->endElement();
			$f($e[0], $query);
			$xmlWriter->endElement();
		}
	};
	$f(null, $query);*/

	$xmlWriter->endElement();

	$xmlWriter->endDocument();

	$doc = new DOMDocument();
	$doc->loadXML($xmlWriter->outputMemory(true));
	return $doc;
}

?>