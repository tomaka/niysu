<?php
namespace Niysu\AdminSite;

/**
 * 
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 *
 * @prefix 		/niysu-admin
 * @static 		assets
 */
class AdminSite {
	public function __construct($twigService, $response) {
		$response->addHeader('X-Powered-By', 'Niysu');
		$twigService->addPath(__DIR__.'/templates', 'niysuAdminSite');
	}

	/**
	 * @url /
	 * @method GET
	 */
	public function mainPanel($twigResponseFilter, $server, $scope) {
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

		$twigResponseFilter->setTemplate('@niysuAdminSite/home.htm');
		$twigResponseFilter->setVariables([
			'routes' => $routes,
			'maintenanceMode' => $maintenanceMode
		]);
	}

	/**
	 * @url /ajax-test
	 * @method GET
	 */
	public function ajaxTestPanel($twigResponseFilter) {
		$twigResponseFilter->setTemplate('@niysuAdminSite/ajaxTest.htm');
		$twigResponseFilter->setVariables([
			'routes' => $routes
		]);
	}

	/**
	 * @url /database
	 * @method GET
	 */
	public function databasePanel($twigResponseFilter) {
		$twigResponseFilter->setTemplate('@niysuAdminSite/databaseAccess.htm');
	}


	/**
	 * @url /xhprof
	 * @method GET
	 */
	public function xhProfPanel($twigResponseFilter) {
		$twigResponseFilter->setTemplate('@niysuAdminSite/xhprof.htm');
		$twigResponseFilter->setVariables([ 'extensionOk' => extension_loaded('xhprof') ]);
	}

	/**
	 * @url /xhprof-handle
	 * @method POST
	 * @todo Profiling should include routes registration
	 */
	public function xhProfPost($twigResponseFilter, $server, $postRequestFilter) {
		$request = new \Niysu\HTTPRequestCustom($postRequestFilter->url);
		$response = new \Niysu\HTTPResponseNull();

		xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
		$server->handle($request, $response);
		$data = xhprof_disable();

		$twigResponseFilter->setTemplate('@niysuAdminSite/xhprof-results.htm');
		$twigResponseFilter->setVariables([ 'data' => $data ]);
	}
}

?>