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
	public function __construct($twigService, $response) {
		$response->addHeader('X-Powered-By', 'Niysu');
		$twigService->addPath(__DIR__.'/templates', 'niysuAdminSite');
	}

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
		$twigService->output('@niysuAdminSite/databaseAccess.htm');
	}


	/**
	 * @name niysu-adminsite-xhprof
	 * @url /xhprof
	 * @method GET
	 */
	public function xhProfPanel($twigService) {
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

		$twigService->output('@niysuAdminSite/xhprof-results.htm', [ 'data' => $data ]);
	}
}

?>