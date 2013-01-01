<?php
namespace Niysu\Filters;

/**
 * Utility filter that uses the ResourcesCacheService.
 *
 * The Vary header is automatically handled.
 * 
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class ServerCacheResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;
	
	/**
	 * Constructor.
	 *
	 * If the filter finds an existing cache entry for the given request, then it loads it and sets "stopRoute" to true.
	 * @todo Add \Niysu\Services\ResourcesCacheService  for third parameter
	 */
	public function __construct(\Niysu\HTTPRequestInterface $request, \Niysu\HTTPResponseInterface $response, $resourcesCacheService, &$stopRoute, \Monolog\Logger $log = null) {
		$this->outputResponse = $response;

		$this->request = $request;
		$this->responseStorage = new \Niysu\HTTPResponseStorage();
		$this->cacheService = $resourcesCacheService;
		$this->log = $log;


		if ($request->getMethod() == 'GET') {
			$this->writeInCache = true;
			$this->transmitToParent = true;
			$this->ttl = 300;	// 5mn

			// attempting to load from cache
			if ($loadedData = $resourcesCacheService->load($request->getURL(), $request->getHeadersList())) {
				$this->writeInCache = false;
				$this->transmitToParent = false;
				$fp = \Niysu\HTTPResponseStream::build($this->getOutput(), true);
				$fp->fwrite($loadedData);
				$stopRoute = true;
			}

		} else {
			$this->writeInCache = false;
			$this->transmitToParent = true;
		}
	}

	/**
	 * Sets the duration of the cache entry to be written.
	 * Note that this filter will not necessarly write an entry.
	 * @param integer 	$seconds 	Time to live of the cache entry
	 */
	public function setCacheDuration($seconds) {
		if (is_string($seconds))
			$seconds = \DateInterval::createFromDateString($seconds);
		if ($seconds instanceof \DateInterval)
			$seconds = (((($seconds->y * 12 + $seconds->m) * 30.4 + $seconds->d) * 24 + $seconds->h) * 60 + $seconds->i) * 60 + $seconds->s;

		if (!is_numeric($seconds))
			throw new \LogicException('Wrong value for cache duration');

		$this->ttl = $seconds;
	}

	/**
	 * Sets whether an entry will be written in cache.
	 * By default, it is true if the request is GET and the response status code is in range 200-399. False otherwise.
	 * @param boolean 	$write 		Whether this filter should add an entry to the cache
	 */
	public function setWriteInCache($write) {
		$this->writeInCache = $write;
		$this->writeInCacheAuthoritative = true;
	}


	public function flush() {
		if ($this->writeInCache) {
			// building the data to store
			$data = '';
			foreach ($this->responseStorage->getHeadersList() as $h => $v)
				$data .= $h.':'.$v."\r\n";
			$data .= "\r\n";
			$data .= $this->responseStorage->getData();

			// building the varying fields
			$varyingFields = [];
			if ($varyHeader = $this->responseStorage->getHeader('Vary')) {
				foreach (explode(',', $varyHeader) as $h) {
					$h = trim($h);
					$varyingFields[$h] = $this->request->getHeader($h);
				}
			}

			// storing
			$this->cacheService->store($this->request->getURL(), $data, $varyingFields, $this->ttl);
		}

		$this->outputResponse->flush();
	}

	public function setStatusCode($code) {
		if ($code >= 400 && !$this->writeInCacheAuthoritative)
			$this->writeInCache = false;

		$this->outputResponse->setStatusCode($code);
	}

	public function addHeader($header, $value) {
		if ($this->writeInCache)
			$this->responseStorage->addHeader($header, $value);

		if ($this->transmitToParent)
			$this->outputResponse->addHeader($header, $value);
	}

	public function setHeader($header, $value) {
		if ($this->writeInCache)
			$this->responseStorage->setHeader($header, $value);

		if ($this->transmitToParent)
		$this->outputResponse->setHeader($header, $value);
	}

	public function removeHeader($header) {
		if ($this->writeInCache)
			$this->responseStorage->removeHeader($header);

		if ($this->transmitToParent)
			$this->outputResponse->removeHeader($header);
	}

	public function appendData($data) {
		if ($this->writeInCache)
			$this->responseStorage->appendData($data);

		if ($this->transmitToParent)
			$this->outputResponse->appendData($data);
	}


	private $transmitToParent = true;				// true if this class must transmit all data/headers/etc. to the parent
	private $writeInCache = true;					// true if the entry must be written to cache
	private $writeInCacheAuthoritative = false;		// true if the user explicitly called "setWriteInCache", ie. the $writeInCache value should not be touched
	private $responseStorage;						// stores the data that will be written to cache if $writeInCache is true
	private $request;								// the HTTPRequestInterface
	private $cacheService;							// the Services\ResourcesCacheService
	private $log;									// the Monolog\Logger
	private $ttl = 60;								// TTL to transmit to the cacheService
};

?>