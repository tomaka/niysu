<?php
namespace Niysu\Services;
use org\bovigo\vfs\vfsStream;

class CacheServiceTest extends \PHPUnit_Framework_TestCase {
	private $root;
	private $cacheService;

	protected function setUp() {
		$this->root = vfsStream::setup('exampleDir');
		$this->cacheService = (new \Niysu\Scope())->call(__NAMESPACE__.'\\CacheService');
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
		$this->assertFalse($this->cacheService->exists('test'));
	}

	public function testStore() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'test');			// unexpected error, probably because of prefix vfsstream://

		$this->assertTrue($this->root->hasChildren());
		$this->assertTrue($this->cacheService->exists('test'));
	}

	/**
	 * @depends testStore
	 */
	public function testLoad() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));
		$serviceClone = clone $this->cacheService;

		$this->cacheService->store('test', 'value');

		$this->assertTrue($this->cacheService->exists('test'));
		$this->assertTrue($serviceClone->exists('test'));

		$this->assertEquals($this->cacheService->load('test'), 'value');
		$this->assertEquals($serviceClone->load('test'), 'value');
	}

	/**
	 * @depends testStore
	 */
	public function testClear() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'value');
		$this->cacheService->store('test2', 'value');
		$this->assertTrue($this->cacheService->exists('test'));
		$this->assertTrue($this->cacheService->exists('test2'));

		$this->cacheService->clear('test');
		$this->assertFalse($this->cacheService->exists('test'));
		$this->assertTrue($this->cacheService->exists('test2'));
	}

	/**
	 * @depends testStore
	 */
	public function testClearAll() {
		$this->cacheService->setCacheDirectory(vfsStream::url('exampleDir'));

		$this->cacheService->store('test', 'value');
		$this->cacheService->store('test2', 'value');
		$this->assertTrue($this->cacheService->exists('test'));
		$this->assertTrue($this->cacheService->exists('test2'));

		$this->cacheService->clearAll();
		$this->assertFalse($this->cacheService->exists('test'));
		$this->assertFalse($this->cacheService->exists('test2'));
	}
};

?>