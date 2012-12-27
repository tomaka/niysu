<?php
namespace Niysu\Filters;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class CacheResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	/*public static function beforeEnableCache($duration, $vary = []) {
		return function($cacheMeService, &$stopRoute) use ($duration, $vary) {
			$cacheMeService->setDuration($duration);
			foreach ($vary as $v)
				$cacheMeService->vary($v);
			if ($cacheMeService->load())
				$stopRoute = true;
		};
	}

	public static function beforeClearCache($urlToClear = null) {
		return function($cacheMeService, $scope) use ($urlToClear) {
			if (is_callable($urlToClear))
				$urlToClear = $scope->call($urlToClear);
			$cacheMeService->clear($urlToClear);
		};
	}*/
	
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\HTTPResponseInterface $response, $cacheService, \Monolog\Logger $log, $elapsedTime) {
		parent::__construct($response);

		$this->serverCacheResourceName = $this->requestToResourceName($request->getURL());

		$this->responseStorage = new \Niysu\HTTPResponseStorage();
		$this->cacheService = $cacheService;
		$this->log = $log;

		if ($cacheService->exists($this->serverCacheResourceName)) {
			$this->writeInCache = false;
			$data = $cacheService->load($this->serverCacheResourceName);
			file_put_contents(\Niysu\HTTPResponseStream::build($this->getOutput(), true), $data);
			parent::flush();
		}

		/*if ($request->getMethod() == 'GET')
			$this->setDuration(60);*/

	}

	public function flush() {
		if ($this->writeInCache) {
			$data = '';
			foreach ($this->responseStorage->getHeadersList() as $h => $v)
				$data .= $h.':'.$v."\r\n";
			$data .= "\r\n";
			$data .= $this->responseStorage->getData();

			$this->cacheService->store($this->serverCacheResourceName, $data, $this->duration);
		}

		parent::flush();
	}

	/// \param url If null, will clear the cache for the current resource ; if non-null, will clear the cache for the given URL
	/*public function clear($url = null) {
		$resource = $this->serverCacheResourceName;
		if (!$url)
			$resource = $this->requestToResourceName($url);
		$this->cacheService->clear($resource);
	}

	public function setDuration($seconds) {
		if (is_string($seconds))
			$seconds = \DateInterval::createFromDateString($seconds);
		if ($seconds instanceof DateInterval)
			$seconds = (((($seconds->y * 12 + $seconds->m) * 30.4 + $seconds->d) * 24 + $seconds->h) * 60 + $seconds->i) * 60 + $seconds->s;

		if (!is_numeric($seconds))
			throw new \LogicException('Wrong value for cache duration');

		$this->duration = $seconds;
		$this->refreshClientSide();
	}*/

	public function addHeader($header, $value) {
		if (!$this->writeInCache)
			return;

		$this->responseStorage->addHeader($header, $value);
		parent::addHeader($header, $value);
	}

	public function setHeader($header, $value) {
		if (!$this->writeInCache)
			return;

		$this->responseStorage->setHeader($header, $value);
		parent::setHeader($header, $value);
	}

	public function appendData($data) {
		if (!$this->writeInCache)
			return;

		$this->responseStorage->appendData($data);
		parent::appendData($data);
	}

	/**
	 * Returns true if the filter didn't load the resource from the cache.
	 *
	 * If this function returns false, anything sent to this filter will be discarded. So you can stop the route earlier.
	 *
	 * @return boolean
	 */
	public function isStale() {
		return $this->writeInCache;
	}






	private function requestToResourceName($requestURL) {
		return 'cacheResponseFilter/resources'.$requestURL;
	}

	private $writeInCache = true;
	private $responseStorage;					// stores the data that will be written in cache
	private $cacheService;
	private $serverCacheResourceName;			// identifier to pass to the cacheService
	private $log;
	private $duration = 60;						// cache duration in seconds that has been set
	private $clientSideEnabled = true;
};

?>