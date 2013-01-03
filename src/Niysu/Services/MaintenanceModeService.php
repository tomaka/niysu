<?php
namespace Niysu\Services;

/**
 * This service allows to check whether a maintenance file exists on the file system.
 * 
 * Often you may want to set your website on maintenance, for example when updating.
 * You can now write a shell script that will create a file named "maintenance", then update your website, and then delete this file.
 * Meanwhile, all requests to your site will return a "503 Unavailable" status code thanks to this service.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class MaintenanceModeService {
	/**
	 * Returns a before function that stops the route and returns a 503 if the maintenance file exists.
	 *
	 * This function returns a function that is supposed to be configured as a "before function" of a route.
	 *
	 * @param string 	$file 	If non-null, the service will be configured to this file before the check
	 * @example $server->before(Niysu\Services\MaintenanceModeService::beforeCheckMaintenance(__DIR__.'/maintenance'));
	 * @return callable
	 * @deprecated Use MaintenanceModeResponseFilter
	 */
	public static function beforeCheckMaintenance($file = null) {
		return function($maintenanceModeService, $response, &$stopRoute) use ($file) {
			if ($file)
				$maintenanceModeService->setFile($file);

			if ($maintenanceModeService->isMaintenanceMode()) {
				$response->setStatusCode(503);
				$stopRoute = true;

				$response->setHeader('Content-Type', 'text/html');
				$response->appendData('<h1>Under maintenance</h1><p>The site is currently under maintenance</p>');
			}
		};
	}

	/**
	 * Configures the file that will be checked.
	 *
	 * @param string 	$file 	Name of the file whose presence indicates maintenance
	 */
	public function setFile($file) {
		$this->file = $file;
	}

	/**
	 * Returns true if the previously configured maintenance file exists.
	 *
	 * @return boolean
	 * @throws LogicException If no file has been configured
	 */
	public function isMaintenanceMode() {
		if (!$this->file)
			throw new \LogicException('File is not set in MaintenanceModeService');
		return file_exists($this->file);
	}

	/**
	 * Creates the previously configured maintenance file, so the site is set to maintenance mode
	 *
	 * @throws LogicException If no file has been configured
	 */
	public function setMaintenanceMode() {
		if (!$this->file)
			throw new \LogicException('File is not set in MaintenanceModeService');
		touch($this->file);
	}

	/**
	 * Deletes the previously configured maintenance file, so the site gets out of maintenance mode
	 *
	 * @throws LogicException If no file has been configured
	 */
	public function clearMaintenanceMode() {
		if (!$this->file)
			throw new \LogicException('File is not set in MaintenanceModeService');
		unlink($this->file);
	}


	private $file = null;
};

?>