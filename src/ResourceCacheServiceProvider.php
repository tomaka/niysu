<?php
namespace Niysu;

class ResourceCacheServiceProvider {
	public function __invoke(HTTPRequestInterface $request, HTTPResponseInterface &$response, CacheService $cache, $log, $elapsedTime) {
		return new ResourceCacheService($request, $response, $cache, $log, $elapsedTime);
	}
};

?>