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
	public function mainPanel($twigOutput, $server, $scope) {
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

		$twigOutput->setTemplate('@niysuAdminSite/home.htm');
		$twigOutput->setVariables([
			'routes' => $routes,
			'maintenanceMode' => $maintenanceMode
		]);
	}

	/**
	 * @url /ajax-test
	 * @method GET
	 */
	public function ajaxTestPanel($twigOutput) {
		$twigOutput->setTemplate('@niysuAdminSite/ajaxTest.htm');
		$twigOutput->setVariables([
			'routes' => $routes
		]);
	}

	/**
	 * @url /database
	 * @method GET
	 */
	public function databasePanel($twigOutput) {
		$twigOutput->setTemplate('@niysuAdminSite/databaseAccess.htm');
	}


	/**
	 * @url /routes
	 * @method GET
	 */
	public function routesList(\Niysu\Server $server, $twigOutput) {
		$twigOutput->setTemplate('@niysuAdminSite/routesList.htm');
		$twigOutput->setVariables([ 'routes' => $server->getRoutesList() ]);
	}


	/**
	 * @url /routes/{routeID}
	 * @pattern routeID .*
	 * @method GET
	 */
	public function routeAnalysis($routeID, \Niysu\Server $server, $twigOutput) {
		$route = null;
		if (is_numeric($routeID))
			$route = $server->getRoutesList()[$routeID];
		if (!$route)
			try { $route = $server->getRouteByName($routeID); } catch(\Exception $e) {}

		$twigOutput->setTemplate('@niysuAdminSite/routeAnalysis.htm');
		$twigOutput->setVariables([ 'route' => $route ]);
	}


	/**
	 * @url /git
	 * @method GET
	 */
	public function gitPanel($twigOutput) {
		$twigOutput->setTemplate('@niysuAdminSite/git.htm');
		
		$dir = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
		
		$gitBranches = null;
		$gitLog = null;
		
		if (is_dir($dir.DIRECTORY_SEPARATOR.'.git')) {
			$prevDir = getcwd();
			chdir($dir);
			exec('git branch', $gitBranches);
			exec('git log', $gitLog);
			chdir($prevDir);
		}
		
		$twigOutput->setVariables([ 'branches' => $gitBranches, 'log' => $gitLog ]);
	}

	/**
	 * @url /git/composer-install
	 * @method POST
	 */
	public function composerInstall($redirectionOutput) {
		$dir = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));

		$process = new \Symfony\Component\Process\Process('composer install', $dir);
		$process->setTimeout(60);
		$process->run();

		$redirectOutput->setLocationToRoute(get_class().'::gitPanel');
	}

	/**
	 * @url /git/git-pull
	 * @method POST
	 */
	public function gitPull($redirectionOutput) {
		$dir = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));

		$process = new \Symfony\Component\Process\Process('git pull', $dir);
		$process->setTimeout(60);
		$process->run();

		$redirectOutput->setLocationToRoute(get_class().'::gitPanel');
	}


	/**
	 * @url /xhprof
	 * @method GET
	 */
	public function xhProfPanel($twigOutput) {
		$twigOutput->setTemplate('@niysuAdminSite/xhprof.htm');
		$twigOutput->setVariables([ 'extensionOk' => extension_loaded('xhprof') ]);
	}

	/**
	 * @url /xhprof-handle
	 * @method POST
	 * @todo Profiling should include routes registration
	 */
	public function xhProfPost($twigOutput, $server, $postRequestFilter) {
		$request = new \Niysu\HTTPRequestCustom($postRequestFilter->url);
		$response = new \Niysu\HTTPResponseNull();

		xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
		$server->handle($request, $response);
		$data = xhprof_disable();

		$twigOutput->setTemplate('@niysuAdminSite/xhprof-results.htm');
		$twigOutput->setVariables([ 'data' => $data ]);
	}
}
