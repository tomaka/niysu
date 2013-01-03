<?php
namespace Niysu\Filters;
use org\bovigo\vfs\vfsStream;

class SessionFilterTest extends \PHPUnit_Framework_TestCase {
	public function testHasSessionLoaded() {
		$this->markTestIncomplete();

		$cookiesMock = $this->getMock('\\Niysu\\Filters\\CookiesFilter');
		$cookiesMock	->expects($this->once())
						->method('__get')
						->with($this->equalTo('session'))
						->will($this->returnValue('testID'));

		$sessionMock = $this->getMock('\\Niysu\\Services\\SessionService')
						->expects($this->once());

		$sessionFilter->setCookieName('session');
	}
};

?>