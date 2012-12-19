<?php
namespace Niysu\Services;

class MaintenanceModeService {
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

	public function setFile($file) {
		$this->file = $file;
	}

	public function isMaintenanceMode() {
		if (!$this->file)
			throw new \LogicException('File is not set in MaintenanceModeService');
		return file_exists($this->file);
	}

	public function setMaintenanceMode() {
		if (!$this->file)
			throw new \LogicException('File is not set in MaintenanceModeService');
		touch($this->file);
	}

	public function clearMaintenanceMode() {
		if (!$this->file)
			throw new \LogicException('File is not set in MaintenanceModeService');
		unlink($this->file);
	}


	private $file = null;
};

?>