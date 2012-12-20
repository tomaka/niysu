<?php
namespace Niysu\Services;

class InputJSONServiceTest extends \PHPUnit_Framework_TestCase {
	private $service;

	protected function setUp() {
		$this->service = (new \Niysu\Scope())->call(__NAMESPACE__.'\\InputJSONService');
	}

	/**
	 * @dataProvider isJSONContentTypeProvider
	 */
	public function testIsJSONContentType($contentType, $expected) {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => $contentType ]);

		$this->assertEquals($this->service->isJSONContentType($request), $expected);
	}

	public function isJSONContentTypeProvider() {
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
	public function testGetJSONInvalidData() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => 'application/json' ], '');
		$request->setRawData('wrong f,ormat { jso]n [');

		$parsed = $this->service->getJSONData($request);
	}

	public function testGetJSONData() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'Content-Type' => 'application/json' ], '');

		$request->setRawData('"hello world"');
		$parsed = $this->service->getJSONData($request);
		$this->assertEquals('hello world', $parsed);

		$request->setRawData('true');
		$parsed = $this->service->getJSONData($request);
		$this->assertTrue($parsed);

		$request->setRawData('54');
		$parsed = $this->service->getJSONData($request);
		$this->assertEquals(54, $parsed);

		$request->setRawData('[ "John Doe", 29, true, null ]');
		$parsed = $this->service->getJSONData($request);
		$this->assertNotNull($parsed);
		$this->assertEquals($parsed[0], 'John Doe');
		$this->assertEquals($parsed[1], 29);
		$this->assertTrue($parsed[2]);
		$this->assertNull($parsed[3]);

		$request->setRawData('{ "var1": "hello", "var2": "world" }');
		$parsed = $this->service->getJSONData($request);
		$this->assertNotNull($parsed);
		$this->assertNotNull($parsed->var1);
		$this->assertEquals($parsed->var1, 'hello');
		$this->assertNotNull($parsed->var2);
		$this->assertEquals($parsed->var2, 'world');
	}
};

?>