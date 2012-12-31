<?php
namespace Niysu\Filters;

class ETagResponseFilterTest extends \PHPUnit_Framework_TestCase {
	/**
	 *
	 */
	public function testETagSet() {
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
	 * @depends testETagSet
	 */
	public function testIfNoneMatch() {
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
	}
};

?>