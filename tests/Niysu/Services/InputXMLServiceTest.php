<?php
namespace Niysu\Services;

class InputXMLServiceTest extends \PHPUnit_Framework_TestCase {
	private $service;

	protected function setUp() {
		$this->service = (new \Niysu\Scope())->call(__NAMESPACE__.'\\InputXMLService');
	}

	/**
	 * @dataProvider isXMLContentTypeProvider
	 */
	public function testIsXMLContentType($contentType, $expected) {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => $contentType ]);

		$this->assertEquals($this->service->isXMLContentType($request), $expected);
	}

	public function isXMLContentTypeProvider() {
		return [
			[ 'application/xml',			true ],
			[ 'text/xml', 					true ],
			[ 'application/xslt+xml', 		true ],
			[ 'image/svg+xml', 				true ],
			[ 'application/rdf+xml', 		true ],
			[ 'text/html',					false ],
			[ 'text/css',					false ]
		];
	}

	public function testGetXMLData() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => 'application/json' ], '');

		$request->setRawData('<a>test</a>');
		$parsed = $this->service->getXMLData($request);
		$this->assertNotNull($parsed);
		$this->assertNotNull($parsed[0]);
		$this->assertEquals('test', $parsed[0]);

		$request->setRawData('<a><b attr="val" /></a>');
		$parsed = $this->service->getXMLData($request);
		$this->assertNotNull($parsed);
		$this->assertNotNull($parsed->b);
		$this->assertEquals('val', $parsed->b['attr']);
	}

	/**
	 * @expectedException Exception
	 */
	public function testGetXMLInvalidData() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => 'application/json' ], '');
		$request->setRawData('< invalid> xml data');

		$parsed = $this->service->getXMLData($request);
	}
};

?>