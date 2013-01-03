<?php
namespace Niysu\Filters;

class StatusCodeOverwriteResponseFilterTest extends \PHPUnit_Framework_TestCase {
	/**
	 *
	 */
	public function testStatusCodeSet() {
		$response = $this->getMock('\Niysu\HTTPResponseInterface');
		$response->expects($this->atLeastOnce())
				 ->method('setStatusCode')
				 ->with($this->equalTo(418));

		$filter = new StatusCodeOverwriteResponseFilter($response, 418);
		$filter->flush();
	}

	/**
	 * @depends testStatusCodeSet
	 */
	public function testStatusCodeCannotBeOverwritten() {
		$response = $this->getMock('\Niysu\HTTPResponseInterface');
		$response->expects($this->atLeastOnce())
				 ->method('setStatusCode')
				 ->with($this->equalTo(418));

		$filter = new StatusCodeOverwriteResponseFilter($response, 418);
		$filter->setStatusCode(200);
		$filter->setStatusCode(300);
		$filter->setStatusCode(400);
		$filter->setStatusCode(500);
		$filter->flush();
	}

	/**
	 *
	 */
	public function testHeadersAndDataGoThrough() {
		$response = $this->getMock('\Niysu\HTTPResponseInterface');
		$response->expects($this->atLeastOnce())
				 ->method('setStatusCode')
				 ->with($this->equalTo(418));

		$response->expects($this->once())
				 ->method('setHeader')
				 ->with($this->equalTo('test'), $this->equalTo('value'));

		$response->expects($this->once())
				 ->method('appendData')
				 ->with($this->equalTo('test'));

		$filter = new StatusCodeOverwriteResponseFilter($response, 418);
		$filter->setHeader('test', 'value');
		$filter->appendData('test');
		$filter->flush();
	}
};

?>