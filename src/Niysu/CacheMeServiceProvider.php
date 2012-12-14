<?php
namespace Niysu;

class CacheMeServiceProvider {
	public function __invoke(HTTPRequestInterface $request, HTTPResponseInterface &$response, CacheService $cache, $log, $elapsedTime) {
		return new CacheMeService($request, $response, $cache, $log, $elapsedTime);
	}
};

?>