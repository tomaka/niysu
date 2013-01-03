<?php
namespace Niysu\Filters;
use org\bovigo\vfs\vfsStream;

class SessionFilterTest extends \PHPUnit_Framework_TestCase {
	private $request;
	private $response;
	private $sessionService;
	private $cookiesService;

	protected function setUp() {
		$this->request = $this->getMock('\\Niysu\\HTTPRequestInterface');
		$this->response = $this->getMock('\\Niysu\\HTTPResponseInterface');
		$this->sessionService = $this->getMockBuilder('\\Niysu\\Services\\SessionService')->disableOriginalConstructor()->getMock();
		$this->cookiesService = $this->getMockBuilder('\\Niysu\\Filters\\CookiesFilter')->disableOriginalConstructor()->getMock();
	}

	/**
	 */
	public function testHasSessionLoadedFalseWithNoCookie() {
		$this->cookiesService	->expects($this->atLeastOnce())
								->method('__isset')
								->with($this->equalTo('session'))
								->will($this->returnValue(false));

		$sessionFilter = new SessionFilter($this->request, $this->response, $this->sessionService, $this->cookiesService, null);
		$sessionFilter->setCookieName('session');

		$this->assertFalse($sessionFilter->hasSessionLoaded());
	}

	/**
	 * @depends testHasSessionLoadedFalseWithNoCookie
	 * @expectedException Exception
	 */
	public function testGetThrowsWithNoCookie() {
		$this->cookiesService	->expects($this->atLeastOnce())
								->method('__isset')
								->with($this->equalTo('session'))
								->will($this->returnValue(false));

		$sessionFilter = new SessionFilter($this->request, $this->response, $this->sessionService, $this->cookiesService, null);
		$sessionFilter->setCookieName('session');

		$sessionFilter->test;
	}

	/**
	 */
	public function testHasSessionLoadedFalseWithCookieWithWrongID() {
		$this->cookiesService	->expects($this->any())
								->method('__isset')
								->with($this->equalTo('session'))
								->will($this->returnValue(true));

		$this->cookiesService	->expects($this->any())
								->method('__get')
								->with($this->equalTo('session'))
								->will($this->returnValue('testID'));

		$this->sessionService	->expects($this->atLeastOnce())
								->method('offsetExists')
								->with($this->equalTo('testID'))
								->will($this->returnValue(false));

		$sessionFilter = new SessionFilter($this->request, $this->response, $this->sessionService, $this->cookiesService, null);
		$sessionFilter->setCookieName('session');

		$this->assertFalse($sessionFilter->hasSessionLoaded());
	}

	/**
	 * @depends testHasSessionLoadedFalseWithCookieWithWrongID
	 * @expectedException Exception
	 */
	public function testGetThrowsWithCookieWithWrongID() {
		$this->cookiesService	->expects($this->any())
								->method('__isset')
								->with($this->equalTo('session'))
								->will($this->returnValue(true));

		$this->cookiesService	->expects($this->any())
								->method('__get')
								->with($this->equalTo('session'))
								->will($this->returnValue('testID'));

		$this->sessionService	->expects($this->atLeastOnce())
								->method('offsetExists')
								->with($this->equalTo('testID'))
								->will($this->returnValue(false));

		$sessionFilter = new SessionFilter($this->request, $this->response, $this->sessionService, $this->cookiesService, null);
		$sessionFilter->setCookieName('session');

		$sessionFilter->test;
	}

	/**
	 */
	public function testSessionOk() {
		$this->cookiesService	->expects($this->any())
								->method('__isset')
								->with($this->equalTo('session'))
								->will($this->returnValue(true));

		$this->cookiesService	->expects($this->any())
								->method('__get')
								->with($this->equalTo('session'))
								->will($this->returnValue('testID'));

		$this->sessionService	->expects($this->any())
								->method('offsetExists')
								->with($this->equalTo('testID'))
								->will($this->returnValue(true));

		$this->sessionService	->expects($this->any())
								->method('offsetGet')
								->with($this->equalTo('testID'))
								->will($this->returnValue([ 'a' => 2 ]));

		$sessionFilter = new SessionFilter($this->request, $this->response, $this->sessionService, $this->cookiesService, null);
		$sessionFilter->setCookieName('session');

		$this->assertTrue($sessionFilter->hasSessionLoaded());
		$this->assertEquals('testID', $sessionFilter->getSessionID());
		$this->assertEquals(2, $sessionFilter->a);
	}

	/**
	 */
	public function testSessionStart() {
		$this->markTestIncomplete();
	}
};

?>