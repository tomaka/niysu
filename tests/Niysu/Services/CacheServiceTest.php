<?php
namespace Niysu\Services;
use org\bovigo\vfs\vfsStream;

class CacheServiceTest extends \PHPUnit_Framework_TestCase {
	private $root;
	private $cacheService;

	protected function setUp() {
		$this->root = vfsStream::setup('exampleDir');
		$this->cacheService = new CacheService(null);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testDirectoryCheck() {
		$this->cacheService->setCacheDirectory(vfsStream::url('nonexistingDirectory'));
	}

	public function testDeactivate() {
		$this->cacheService->deactivate();
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'test');

		$this->assertFalse($this->root->hasChildren());
		$this->assertNull($this->cacheService->load('test'));
	}

	public function testStore() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'test');

		$this->assertTrue($this->root->hasChildren());
		$this->assertNotNull($this->cacheService->load('test'));
	}

	/**
	 * @depends testStore
	 */
	public function testLoad() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));
		$serviceClone = clone $this->cacheService;

		$this->cacheService->store('test', 'value');

		$this->assertEquals($this->cacheService->load('test'), 'value');
		$this->assertEquals($serviceClone->load('test'), 'value');
	}

	/**
	 * @depends testLoad
	 */
	public function testLoadCategory() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'test', null, 'cat');

		$this->assertTrue($this->root->hasChildren());
		$this->assertNull($this->cacheService->load('test'));
		$this->assertNotNull($this->cacheService->load('test', 'cat'));
	}

	/**
	 * @depends testLoad
	 */
	public function testLoadMatch() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test/hello', 'value');

		$this->assertEquals($this->cacheService->loadMatch('/test\\/.*/'), 'value');
		$this->assertEquals($this->cacheService->loadMatch('/\w{4}\\/\w{5}/'), 'value');
	}

	/**
	 * @depends testStore
	 * @depends testLoad
	 */
	public function testTTL() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'value', 2);

		$this->assertTrue($this->root->hasChildren());
		$this->assertNotNull($this->cacheService->load('test'));

		sleep(1);
		$this->assertNotNull($this->cacheService->load('test'));

		sleep(2);
		$this->assertNull($this->cacheService->load('test'));
		$this->assertFalse($this->root->hasChildren());
	}

	/**
	 * @depends testStore
	 */
	public function testClear() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'value');
		$this->cacheService->store('test2', 'value');
		$this->assertNotNull($this->cacheService->load('test'));
		$this->assertNotNull($this->cacheService->load('test2'));

		$this->cacheService->clear('test');
		$this->assertNull($this->cacheService->load('test'));
		$this->assertNotNull($this->cacheService->load('test2'));
	}

	/**
	 * @depends testStore
	 */
	public function testClearAll() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'value');
		$this->cacheService->store('test2', 'value');
		$this->assertNotNull($this->cacheService->load('test'));
		$this->assertNotNull($this->cacheService->load('test2'));

		$this->cacheService->clearAll();
		$this->assertNull($this->cacheService->load('test'));
		$this->assertNull($this->cacheService->load('test2'));
	}
};

?>