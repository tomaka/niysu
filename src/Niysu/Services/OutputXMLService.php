<?php
namespace Niysu\Services;

/// \brief 
class OutputXMLService {
	public function __construct(\Niysu\Scope $scope) {
		$this->scope = $scope;
	}

	public function toString($xml) {
		if (is_array($xml))
			return $this->arrayToString($xml);
		else
			throw new \LogicException('Wrong ');
	}

	public function output($xml, $contentType = 'application/xml') {
		$this->scope->response->setHeader('Content-Type', $contentType);
		$this->scope->response->appendData($this->toString($xml));
	}



	/// \brief Writes an XML document
	private function arrayToString($xml) {
		// this is a function object that will take an XML array and call all xml writer functions
		$writeNode = function($xmlWriter, $node) use (&$writeNode) {
			// first case: the node is a DOMDocument or derivate
			// writing the raw data
			if ($node instanceof \DOMDocument) {
				$xmlWriter->writeRaw($node->saveXML($node->documentElement));
				return;
			} else if ($node instanceof \DOMNode) {
				$xmlWriter->writeRaw($node->ownerDocument->saveXML($node));
				return;
			} else if ($node instanceof \DOMNodeList) {
				foreach ($node as $n) {
					$xmlWriter->writeRaw($n->ownerDocument->saveXML($n));
					$xmlWriter->writeRaw("\n");
				}
				return;
			} else if ($node instanceof \SimpleXMLElement) {
				$xmlWriter->writeRaw($node->asXML());
				return;
			}
			
			// ignoring if empty
			if (!is_array($node) || !isset($node[0]))
				return;

			// now we know that $node is an array
			if (is_array($node[0])) {
				// if $node is in the format [ [ something ] ]
				// then we call ourselves for each subelement
				foreach ($node as $elem)
					$writeNode($xmlWriter, $elem);

			} else if (is_string($node[0])) {
				// handling two special syntaxes
				if ($node[0] == '#comment') {
					// $node is in format ['#comment', 'something']
					$xmlWriter->writeComment($node[1]);
				} else if ($node[0] == '#cdata') {
					// $node is in format ['#cdata', 'something']
					$xmlWriter->writeCData($node[1]);
				} else if ($node[0] == '#raw') {
					// $node is in format ['#raw', 'something']
					$xmlWriter->writeRaw($node[1]);

				} else {
					// we are at the start of an element
					$xmlWriter->startElement($node[0]);

					// writing all attributes (ie. elements of $node where keys are strings)
					foreach ($node as $key => $value) {
						if (!is_string($key))
							continue;
						if (strlen($key) > 0)
							$xmlWriter->writeAttribute($key, $value);
					}

					// now writing all children (ie. elements of $node where keys are not strings)
					foreach ($node as $key => $value) {
						if (is_string($key))
							continue;
						if ($key === 0)
							continue;

						if (is_string($value)) {
							// text node
							$xmlWriter->text($value);
						} else {
							// $value is an array or a DOMNode or anything else, calling ourselves
							$writeNode($xmlWriter, $value);
						}
					}

					// end of element
					$xmlWriter->endElement();
				}
			}
		};
		
		// main body of the function
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndentString(' ');
		$writer->setIndent(true);
		$writer->startDocument('1.0', 'utf-8');
		$writeNode($writer, $xml);
		$writer->endDocument();
		return $writer->outputMemory();
	}


	private $scope;
};

?>