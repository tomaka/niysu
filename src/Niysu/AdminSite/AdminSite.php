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
	public function mainPanel($twigService, $server) {
		$routes = [];
		foreach ($server->getRoutesList() as $r)
			$routes[] = [ 'route' => $r->getOriginalPattern(), 'name' => $r->getName(), 'regex' => $r->getURLRegex() ];

		$twigService->addPath(__DIR__);
		$twigService->output('home.adminsite.htm', [
			'routes' => $routes
		]);
	}

	/**
	 * @name niysu-adminsite-ajaxtest
	 * @url /ajax-test
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