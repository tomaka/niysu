<?php
namespace Niysu\Services;

class MaintenanceModeService {
	public static function beforeCheckMaintenance($path = null) {
		return function($maintenanceModeService, $response, &$stopRoute) use ($path) {
			if ($path)
				$maintenanceModeService->setPath($path);

			if ($maintenanceModeService->isMaintenanceMode()) {
				$response->setStatusCode(503);
				$stopRoute = true;

				$response->setHeader('Content-Type', 'text/html');
				$response->appendData('<h1>Under maintenance</h1><p>The site is currently under maintenance</p>');
			}
		};
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function isMaintenanceMode() {
		return file_exists($this->getFileName());
	}

	public function setMaintenanceMode() {
		touch($this->getFileName());
	}

	public function clearMaintenanceMode() {
		unlink($this->getFileName());
	}



	private function getFileName() {
		if (!$this->path)
			throw new \LogicException('Path is not set in MaintenanceModeService');
		if (substr($this->path, -1) == '/' || substr($this->path, -1) == '\\')
			$this->path = substr($this->path, 0, -1);

		return $this->path.DIRECTORY_SEPARATOR.'maintenance';
	}

	private $path = null;
};

?>