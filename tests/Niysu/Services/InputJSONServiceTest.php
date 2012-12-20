<?php
namespace Niysu\Services;

class InputJSONServiceTest extends \PHPUnit_Framework_TestCase {
	private $service;

	protected function setUp() {
		$this->service = (new \Niysu\Scope())->call(__NAMESPACE__.'\\InputJSONService');
	}

	/**
	 * @dataProvider isValidContentTypeProvider
	 */
	public function testIsValidContentType($contentType, $expected) {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => $contentType ]);

		$this->assertEquals($expected, $this->service->isValidContentType($request));
	}

	public function isValidContentTypeProvider() {
		return [
			[ 'application/json',			true ],
			[ 'application/javascript', 	true ],
			[ 'text/javascript', 			true ],
			[ 'text/x-javascript', 			true ],
			[ 'text/x-json', 				true ],
			[ 'text/html',					false ],
			[ 'text/css',					false ],
			[ 'application/xml',			false ]
		];
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testGetInvalidData() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => 'application/json' ], '');
		$request->setRawData('wrong f,ormat { jso]n [');

		$parsed = $this->service->getData($request);
	}

	public function testGetData() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => 'application/json' ], '');

		$request->setRawData('"hello world"');
		$parsed = $this->service->getData($request);
		$this->assertEquals('hello world', $parsed);

		$request->setRawData('true');
		$parsed = $this->service->getData($request);
		$this->assertTrue($parsed);

		$request->setRawData('54');
		$parsed = $this->service->getData($request);
		$this->assertEquals(54, $parsed);

		$request->setRawData('[ "John Doe", 29, true, null ]');
		$parsed = $this->service->getData($request);
		$this->assertNotNull($parsed);
		$this->assertEquals($parsed[0], 'John Doe');
		$this->assertEquals($parsed[1], 29);
		$this->assertTrue($parsed[2]);
		$this->assertNull($parsed[3]);

		$request->setRawData('{ "var1": "hello", "var2": "world" }');
		$parsed = $this->service->getData($request);
		$this->assertNotNull($parsed);
		$this->assertNotNull($parsed->var1);
		$this->assertEquals($parsed->var1, 'hello');
		$this->assertNotNull($parsed->var2);
		$this->assertEquals($parsed->var2, 'world');
	}
};

?>