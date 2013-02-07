<?php
namespace Niysu\Contexts;
use org\bovigo\vfs\vfsStream;

class SessionContextTest extends \PHPUnit_Framework_TestCase {
	private $sessionService;
	private $cookiesService;

	protected function setUp() {
		$this->sessionService = $this->getMockBuilder('\\Niysu\\Services\\SessionService')->disableOriginalConstructor()->getMock();
		$this->cookiesService = $this->getMockBuilder('\\Niysu\\Contexts\\CookiesContext')->disableOriginalConstructor()->getMock();
	}

	/**
	 */
	public function testHasSessionLoadedFalseWithNoCookie() {
		$this->cookiesService	->expects($this->atLeastOnce())
								->method('__isset')
								->with($this->equalTo('session'))
								->will($this->returnValue(false));

		$sessionContext = new SessionContext($this->sessionService, $this->cookiesService, null);
		$sessionContext->setCookieName('session');

		$this->assertFalse($sessionContext->hasSessionLoaded());
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

		$sessionContext = new SessionContext($this->sessionService, $this->cookiesService, null);
		$sessionContext->setCookieName('session');

		$sessionContext->test;
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

		$sessionContext = new SessionContext($this->sessionService, $this->cookiesService, null);
		$sessionContext->setCookieName('session');

		$this->assertFalse($sessionContext->hasSessionLoaded());
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

		$sessionContext = new SessionContext($this->sessionService, $this->cookiesService, null);
		$sessionContext->setCookieName('session');

		$sessionContext->test;
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

		$sessionContext = new SessionContext($this->sessionService, $this->cookiesService, null);
		$sessionContext->setCookieName('session');

		$this->assertTrue($sessionContext->hasSessionLoaded());
		$this->assertEquals('testID', $sessionContext->getSessionID());
		$this->assertEquals(2, $sessionContext->a);
	}

	/**
	 */
	public function testSessionStart() {
		$this->markTestIncomplete();
	}
};

?>