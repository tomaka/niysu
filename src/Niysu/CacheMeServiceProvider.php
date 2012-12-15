<?php
namespace Niysu;

class CacheMeServiceProvider {
	public function __invoke(HTTPRequestInterface $request, HTTPResponseInterface &$response, $cacheService, $logService, $elapsedTime) {
		return new CacheMeService($request, $response, $cacheService, $logService, $elapsedTime);
	}
};

?>