<?php
namespace Niysu\Services;

class CacheMeService {
	public static function beforeEnableCache($duration, $vary = []) {
		return function($cacheMeService, &$callHandler) use ($duration, $vary) {
			$cacheMeService->setDuration($duration);
			foreach ($vary as $v)
				$cacheMeService->vary($v);
			if ($cacheMeService->load())
				$callHandler = false;
		};
	}
	
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\HTTPResponseInterface &$response, $cacheService, $logService, $elapsedTime) {
		$this->cache = $cacheService;
		$this->logService = $logService;
		$this->elapsedTime = $elapsedTime;
		
		$serverCacheResourceName = 'cacheMe/resources/'.$request->getURL();
		$this->serverCacheResourceName = $serverCacheResourceName;
		
		$response = new \Niysu\HTTPResponseCustomFilter($response, \Closure::bind(function($response) use ($cacheService, $serverCacheResourceName) {
			$data = '';
			foreach ($response->getHeadersList() as $h => $v)
				$data .= $h.':'.$v."\r\n";
			$data .= "\r\n";
			$data .= $response->getData();

			$cacheService->store($serverCacheResourceName, $data);
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
			$this->logService->debug('Attempting to load resource from cache, not found: '.$this->serverCacheResourceName);
			return false;
		}

		$data = $this->cache->load($this->serverCacheResourceName);
		$this->logService->debug('Loading resource from cache: '.$this->serverCacheResourceName);

		$this->responseFilter->setContentCallback(\Closure::bind(function($response) use ($data) {
			$dataParts = explode("\r\n\r\n", $data, 2);
			$headers = explode("\r\n", $dataParts[0]);
			foreach ($headers as $h) {
				$val = explode(':', $h, 2);
				$response->addHeader($val[0], $val[1]);
			}

			$response->setData($dataParts[1]);
		}, null));

		$this->responseFilter->flush();

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
	private $logService;
	private $elapsedTime;
	private $serverCacheResourceName;
	private $duration = 0;						// cache duration in seconds that has been set
	private $clientSideEnabled = true;
};

?>