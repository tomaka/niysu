<?php
namespace Niysu\Services;
use org\bovigo\vfs\vfsStream;

class SessionServiceTest extends \PHPUnit_Framework_TestCase {
	public function testGenerateSessionID() {
		$this->markTestIncomplete();
	}

	public function testOffsetExists() {
		$cacheMock = $this->getMock('\\Niysu\\Services\\CacheService');
		$cacheMock	->expects($this->once())
					->method('load')
					->with($this->anything(), $this->equalTo('mycattest'))
					->will($this->returnValue(null));

		$sessionService = new SessionService($cacheMock);
		$sessionService->setCategory('mycattest');

		$this->assertFalse(isset($sessionService['testID']));
	}

	/**
	 * @expectedException Exception
	 */
	public function testLoadNonExisting() {
		vfsStream::setup('exampleDir');
		$cacheService = new CacheService(null);
		$cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$sessionService1 = new SessionService($cacheService);
		$a = $sessionService1['testID'];
	}

	public function testStoreThenLoad() {
		vfsStream::setup('exampleDir');
		$cacheService = new CacheService(null);
		$cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$sessionService1 = new SessionService($cacheService);
		$sessionService1['testID'] = [ 'a' => 2, 'b' => 12 ];

		$sessionService2 = new SessionService($cacheService);
		$this->assertTrue(isset($sessionService2['testID']));
		$this->assertEquals(2, $sessionService2['testID']['a']);
		$this->assertEquals(12, $sessionService2['testID']['b']);
	}

	public function testUnset() {
		vfsStream::setup('exampleDir');
		$cacheService = new CacheService(null);
		$cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$sessionService1 = new SessionService($cacheService);
		$sessionService1['testID'] = [ 'a' => 2, 'b' => 12 ];
		$this->assertTrue(isset($sessionService1['testID']));

		$sessionService2 = new SessionService($cacheService);
		$this->assertTrue(isset($sessionService2['testID']));
		unset($sessionService2['testID']);

		$this->assertFalse(isset($sessionService2['testID']));
		$this->assertFalse(isset($sessionService1['testID']));
	}
};

?>