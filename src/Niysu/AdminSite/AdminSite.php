<?php
namespace Niysu\AdminSite;

/**
 * 
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 *
 * @prefix 		/admin
 * @static 		assets
 */
class AdminSite {
	/**
	 * @name niysu-adminsite-home
	 * @url /
	 * @method GET
	 */
	public function mainPanel($twigService, $server, $scope) {
		$routes = [];
		foreach ($server->getRoutesList() as $r) {
			$pattern = [];
			for($i = 0; $i < $r->getURLsCount(); ++$i)
				$pattern[] = $r->getOriginalPattern($i);
			$routes[] = [ 'patterns' => $pattern, 'name' => $r->getName() ];
		}

		$maintenanceMode = false;
		try { $maintenanceMode = $scope->maintenanceModeService->isMaintenanceMode();
		} catch(\Exception $e) {}

		$twigService->addPath(__DIR__.'/templates', 'niysuAdminSite');
		$twigService->output('@niysuAdminSite/home.htm', [
			'routes' => $routes,
			'maintenanceMode' => $maintenanceMode
		]);
	}

	/**
	 * @name niysu-adminsite-ajaxtest
	 * @url /ajax-test
	 * @method GET
	 */
	public function ajaxTestPanel($twigService) {
		$twigService->addPath(__DIR__.'/templates', 'niysuAdminSite');
		$twigService->output('@niysuAdminSite/ajaxTest.htm', [
			'routes' => $routes
		]);
	}

	/**
	 * @name niysu-adminsite-database
	 * @url /database
	 * @method GET
	 */
	public function databasePanel($twigService) {
		$twigService->addPath(__DIR__.'/templates', 'niysuAdminSite');
		$twigService->output('@niysuAdminSite/databaseAccess.htm');
	}


	/**
	 * @name niysu-adminsite-xhprof
	 * @url /xhprof
	 * @method GET
	 */
	public function xhProfPanel($twigService) {
		$twigService->addPath(__DIR__.'/templates', 'niysuAdminSite');
		$twigService->output('@niysuAdminSite/xhprof.htm', [ 'extensionOk' => extension_loaded('xhprof') ]);
	}

	/**
	 * @name niysu-adminsite-xhprof-post
	 * @url /xhprof-handle
	 * @method POST
	 * @todo Profiling should include routes registration
	 */
	public function xhProfPost($twigService, $server, $postRequestFilter) {
		$request = new \Niysu\HTTPRequestCustom($postRequestFilter->url);
		$response = new \Niysu\HTTPResponseNull();

		xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
		$server->handle($request, $response);
		$data = xhprof_disable();

		$twigService->addPath(__DIR__.'/templates', 'niysuAdminSite');
		$twigService->output('@niysuAdminSite/xhprof-results.htm', [ 'data' => $data ]);
	}


	/**
	 * @name niysu-adminsite-xdebug
	 * @url /xdebug
	 * @method GET
	 */
	public function xDebugPanel($twigService, $response) {
		$filterOk = false;
		while ($response instanceof \Niysu\HTTPResponseFilterInterface) {
			if ($response instanceof XDebugProfilingFilter) {
				$filterOk = true;
				break;
			}

			$response = $response->getOutput();
		}

		$twigService->addPath(__DIR__.'/templates', 'niysuAdminSite');
		$twigService->output('@niysuAdminSite/xdebugBefore.htm', [
			'xdebugInstalled' => extension_loaded('xdebug'),
			'xdebugProfilerEnable' => ini_get('xdebug.profiler_enable'),
			'xdebugProfilerEnableTrigger' => ini_get('xdebug.profiler_enable_trigger'),
			'serverSoftware' => $_SERVER['SERVER_SOFTWARE'],
			'serverSoftwareOk' => !preg_match('/^PHP .* Development Server$/i', $_SERVER['SERVER_SOFTWARE']),
			'filterOk' => $filterOk
		]);
	}

	/**
	 * @name niysu-adminsite-xdebug-start
	 * @url /xdebug-start
	 * @method POST
	 */
	public function xDebugStart($postRequestFilter, $response) {
		// making the HTTP request to localhost
		$opts = ['http' => [ 'method'  => 'GET' ] ];
		file_get_contents('http://localhost:'.$_SERVER['SERVER_PORT'].'/'.ltrim($postRequestFilter->url, '/').'?XDEBUG_PROFILE', false, stream_context_create($opts));
		
		// 
		$filename = null;
		foreach ($http_response_header as $h) {
			if (preg_match('/^X-XDebugFilename:\\s*(.*)$/', $h, $matches)) {
				$filename = $matches[1];
				break;
			}
		}

		if ($filename == null)
			;

		var_dump($filename);
	}
}

?>