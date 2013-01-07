<?php
namespace Niysu;

class URLPatternTest extends \PHPUnit_Framework_TestCase {
	/**
	 */
	public function testTestURLConstant() {
		$pattern = new URLPattern('/test');
		
		$this->assertNotNull($pattern->testURL('/test'));
		$this->assertNull($pattern->testURL('/tes'));
		$this->assertNull($pattern->testURL('/testt'));
		$this->assertNull($pattern->testURL('/'));
		$this->assertNull($pattern->testURL('/test-test'));
		$this->assertNull($pattern->testURL('/test/test'));
	}

	/**
     * @depends testTestURLConstant
     */
	public function testTestURLSingle() {
		$pattern = new URLPattern('/{var}');
		
		$this->assertNotNull($pattern->testURL('/test'));
		$this->assertEquals(1, count($pattern->testURL('/test')));
		$this->assertEquals('test', $pattern->testURL('/test')['var']);

		$this->assertNotNull($pattern->testURL('/1'));
		$this->assertEquals(1, $pattern->testURL('/1')['var']);

		$this->assertNull($pattern->testURL('/'));
		$this->assertNull($pattern->testURL('/test-test'));
		$this->assertNull($pattern->testURL('/test/test'));
	}

	/**
     * @depends testTestURLSingle
     */
	public function testPatternMatchMultiple() {
		$pattern = new URLPattern('/{var1}/{var2}');

		$this->assertNotNull($pattern->testURL('/test/test'));
		$this->assertEquals(2, count($pattern->testURL('/test/test')));
		$this->assertEquals('a', $pattern->testURL('/a/b')['var1']);
		$this->assertEquals('b', $pattern->testURL('/a/b')['var2']);

		$this->assertNull($pattern->testURL('/'));
		$this->assertNull($pattern->testURL('//'));
		$this->assertNull($pattern->testURL('/test'));
		$this->assertNull($pattern->testURL('/test-test'));
		$this->assertNull($pattern->testURL('/test/test/test'));
	}

	/**
     * @expectedException LogicException
     */
	public function testPatternFunctionException() {
		$pattern = new URLPattern('/{var}');
		$pattern->pattern('test', '\\d+');
	}

	/**
     * @depends testTestURLSingle
     */
	public function testPatternFunction() {
		$pattern = new URLPattern('/{var}');
		$pattern->pattern('var', '\\d+');

		$this->assertNotNull($pattern->testURL('/187'));
		$this->assertEquals(1, count($pattern->testURL('/187')));
		$this->assertEquals(187, $pattern->testURL('/187')['var']);

		$this->assertNull($pattern->testURL('/test'));
		$this->assertNull($pattern->testURL('/1h2l1'));
	}

	/**
     * @depends testPatternFunction
     */
	public function testParenthesisInPattern() {
		$pattern = new URLPattern('/{var1}/{var2}');
		$pattern->pattern('var1', '(\\d)+');

		$this->assertNotNull($pattern->testURL('/187/test'));
		$this->assertEquals(2, count($pattern->testURL('/187/test')));
		$this->assertEquals(187, $pattern->testURL('/187/test')['var1']);
		$this->assertEquals('test', $pattern->testURL('/187/test')['var2']);
	}

	/**
	 */
	public function testGetOriginalPattern() {
		$pattern = new URLPattern('/{var1}/{var2}');
		$this->assertEquals('/{var1}/{var2}', $pattern->getOriginalPattern());
	}

	/**
	 */
	public function testGetURLRegex() {
		$pattern = new URLPattern('/{var1}/{var2}');
		$pattern->pattern('var1', '\\w+');
		$pattern->pattern('var2', '\\w+');
		$this->assertEquals('/^\\/(\\w+)\\/(\\w+)$/', $pattern->getURLRegex());
	}

	/**
	 */
	public function testGetURLConstant() {
		$pattern = new URLPattern('/const');

		$this->assertEquals('/const', $pattern->getURL([]));
	}

	/**
	 */
	public function testGetURL() {
		$pattern = new URLPattern('/{var1}/{var2}');

		$this->assertEquals($pattern->getURL([ 'var1' => 'a', 'var2' => 'b' ]), '/a/b');
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testGetURLException() {
		$pattern = new URLPattern('/{var1}/{var2}');
		$pattern->getURL([ 'var1' => 'a' ]);
	}
}

?>