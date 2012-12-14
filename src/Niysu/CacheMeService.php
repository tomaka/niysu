<?php
namespace Niysu;

class CacheMeService {
	public static function before($duration) {
		return function($cacheMeService, &$callHandler) use ($duration) {
			$cacheMeService->setDuration($duration);
			if ($cacheMeService->load())
				$callHandler = false;
		};
	}
	
	public function __construct(HTTPRequestInterface $request, HTTPResponseInterface &$response, CacheService $cache, $log, $elapsedTime) {
		$this->cache = $cache;
		$this->log = $log;
		$this->elapsedTime = $elapsedTime;

		$serverCacheResourceName = 'resource at '.$request->getURL();
		$this->serverCacheResourceName = $serverCacheResourceName;

		//$response = new HTTPResponseETagFilter($response);
		$response = new HTTPResponseCustomFilter($response, Closure::bind(function($data) use ($cache, $serverCacheResourceName) {
			$cache->store($serverCacheResourceName, $data);
			return $data;
		}, null));
		
		$this->responseFilter = $response;

		if ($request->getMethod() == 'GET')
			$this->setDuration(60);
	}
	
	public function setDuration($seconds) {
		if (is_string($seconds))
			$seconds = new DateInterval($seconds);
		if ($seconds instanceof DateInterval)
			$seconds = (((($seconds->y * 12 + $seconds->m) * 30.4 + $seconds->d) * 24 + $seconds->h) * 60 + $seconds->i) * 60 + $seconds->s;

		if (!is_numeric($seconds))
			throw new \LogicException('Wrong value for cache duration');

		$this->duration = $seconds;
		$this->refreshClientSide();
	}
	
	public function load() {
		if (!$this->cache->exists($this->serverCacheResourceName)) {
			$this->log->debug('Attempting to load resource from cache, not found: '.$this->serverCacheResourceName);
			return false;
		}

		$data = $this->cache->load($this->serverCacheResourceName);
		$e = $this->elapsedTime;
		$this->log->debug('Loading resource from cache ('.$e().'ms): '.$this->serverCacheResourceName);

		$newCB = function() use ($data) { return $data; };
		$newCB = $newCB->bindTo(null);
		$this->responseFilter->setContentCallback($newCB);

		return true;
	}

	public function vary($header) {
		$this->responseFilter->addHeader('Vary', $header);
	}


	private function refreshClientSide() {
		if ($this->clientSideEnabled && $this->duration)
			$this->responseFilter->setHeader('Cache-Control', 'public; max-age='.$this->duration);
	}

	private $responseFilter;
	private $cache;
	private $log;
	private $elapsedTime;
	private $serverCacheResourceName;
	private $duration = 0;						// cache duration in seconds that has been set
	private $clientSideEnabled = true;
};

?>