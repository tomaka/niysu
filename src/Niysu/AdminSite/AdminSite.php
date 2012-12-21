<?php
namespace Niysu\AdminSite;

/**
 * 
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 *
 * @static 		assets
 */
class AdminSite {
	/**
	 * @name niysu-adminsite-home
	 * @url /admin
	 * @method GET
	 */
	public function mainPanel($twigService, $server) {
		$routes = [];
		foreach ($server->getRoutesList() as $r)
			$routes[] = [ 'route' => $r->getOriginalPattern(), 'name' => $r->getName() ];

		$twigService->addPath(__DIR__);
		$twigService->output('home.adminsite.htm', [
			'routes' => $routes
		]);
	}

	/**
	 * @name niysu-adminsite-ajaxtest
	 * @url /admin/ajax-test
	 * @method GET
	 */
	public function ajaxTestPanel($twigService) {
		$twigService->addPath(__DIR__);
		$twigService->output('ajaxTest.htm', [
			'routes' => $routes
		]);
	}
}

?>