<?php
namespace Niysu\Services;

class CacheMeService {
	public static function beforeEnableCache($duration, $vary = []) {
		return function($cacheMeService, &$stopRoute) use ($duration, $vary) {
			$cacheMeService->setDuration($duration);
			foreach ($vary as $v)
				$cacheMeService->vary($v);
			if ($cacheMeService->load())
				$stopRoute = true;
		};
	}
	
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\HTTPResponseInterface &$response, $cacheService, $logService, $elapsedTime) {
		$this->cacheService = $cacheService;
		$this->logService = $logService;
		$this->elapsedTime = $elapsedTime;
		
		$this->serverCacheResourceName = $this->requestToResourceName($request->getURL());
		
		$response = new \Niysu\HTTPResponseCustomFilter($response, function($response) {
			$data = '';
			foreach ($response->getHeadersList() as $h => $v)
				$data .= $h.':'.$v."\r\n";
			$data .= "\r\n";
			$data .= $response->getData();

			$this->cacheService->store($this->serverCacheResourceName, $data, $this->duration);
		}, null);
		
		$this->responseFilter = $response;

		if ($request->getMethod() == 'GET')
			$this->setDuration(60);
	}

	/// \param url If null, will clear the cache for the current resource ; if non-null, will clear the cache for the given URL
	public function clear($url = null) {
		$resource = $this->serverCacheResourceName;
		if (!$url)
			$resource = $this->requestToResourceName($url);
		$this->cacheService->clear($resource);
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
		if (!$this->cacheService->exists($this->serverCacheResourceName)) {
			$this->logService->debug('Attempting to load resource from cache, not found: '.$this->serverCacheResourceName);
			return false;
		}

		$data = $this->cacheService->load($this->serverCacheResourceName);
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

		return true;
	}

	public function vary($header) {
		$this->responseFilter->addHeader('Vary', $header);
	}




	private function refreshClientSide() {
		if ($this->clientSideEnabled && $this->duration)
			$this->responseFilter->setHeader('Cache-Control', 'public; max-age='.$this->duration);
	}

	private function requestToResourceName($requestURL) {
		return 'cacheMe/resources'.$requestURL;
	}

	private $responseFilter;
	private $cacheService;
	private $logService;
	private $elapsedTime;
	private $serverCacheResourceName;
	private $duration = 0;						// cache duration in seconds that has been set
	private $clientSideEnabled = true;
};

?>