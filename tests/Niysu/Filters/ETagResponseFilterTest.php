<?php
namespace Niysu\Filters;

class ETagResponseFilterTest extends \PHPUnit_Framework_TestCase {
	/**
	 *
	 */
	public function testETagCalculated() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', []);

		$response = $this->getMock('\Niysu\HTTPResponseInterface');

		$response->expects($this->once())
				 ->method('setHeader')
				 ->with($this->equalTo('ETag'), $this->anything());

		$response->expects($this->once())
				 ->method('appendData')
				 ->with($this->equalTo('test'));

		$stopRoute = false;
		$filter = new ETagResponseFilter($response, $request, $stopRoute);
		$filter->appendData('test');
		$filter->flush();
	}

	/**
	 *
	 */
	public function testIfNoneMatchWithGoodETagSet() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'If-None-Match' => 'abcdefgh1234' ]);

		$response = $this->getMock('\Niysu\HTTPResponseInterface');

		$response->expects($this->once())
				 ->method('setHeader')
				 ->with($this->equalTo('ETag'), $this->equalTo('abcdefgh1234'));

		$response->expects($this->once())
				 ->method('setStatusCode')
				 ->with($this->equalTo(304));

		$response->expects($this->never())
				 ->method('appendData');

		$stopRoute = false;
		$filter = new ETagResponseFilter($response, $request, $stopRoute);
		$filter->setETag('abcdefgh1234');
		$filter->appendData('test');
		$filter->flush();

		$this->assertTrue($stopRoute);
	}

	/**
	 *
	 */
	public function testIfNoneMatchWithWrongETagSet() {
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'If-None-Match' => 'wrongetag' ]);

		$response = $this->getMock('\Niysu\HTTPResponseInterface');

		$response->expects($this->once())
				 ->method('setHeader')
				 ->with($this->equalTo('ETag'), $this->equalTo('abcdefgh1234'));

		$response->expects($this->never())
				 ->method('setStatusCode');

		$response->expects($this->once())
				 ->method('appendData')
				 ->with($this->equalTo('test'));

		$stopRoute = false;
		$filter = new ETagResponseFilter($response, $request, $stopRoute);
		$filter->setETag('abcdefgh1234');
		$filter->appendData('test');
		$filter->flush();

		$this->assertFalse($stopRoute);
	}

	/**
	 * @depends testETagCalculated
	 */
	public function testIfNoneMatchWithCalculatedEtag() {
		$stopRoute = false;

		$request = new \Niysu\HTTPRequestCustom('/', 'GET', []);
		$response = new \Niysu\HTTPResponseStorage();
		$filter = new ETagResponseFilter($response, $request, $stopRoute);
		$filter->appendData('test');
		$filter->flush();
		$this->assertFalse($stopRoute);

		$etag = $response->getHeader('ETag');
		$this->assertEquals('test', $response->getData());
		$request = new \Niysu\HTTPRequestCustom('/', 'GET', [ 'If-None-Match' => $etag ]);

		$response = $this->getMock('\Niysu\HTTPResponseInterface');
		$response->expects($this->once())
				 ->method('setHeader')
				 ->with($this->equalTo('ETag'), $this->equalTo($etag));
		$response->expects($this->once())
				 ->method('setStatusCode')
				 ->with($this->equalTo(304));
		$response->expects($this->never())
				 ->method('appendData');

		$filter = new ETagResponseFilter($response, $request, $stopRoute);
		$this->assertFalse($stopRoute);
		$filter->appendData('test');
		$filter->flush();

		$this->assertFalse($stopRoute);
	}
};

?>