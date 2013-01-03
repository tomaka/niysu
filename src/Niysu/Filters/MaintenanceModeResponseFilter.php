<?php
namespace Niysu\Filters;

/**
 * Automatically sends back an error page if the website is under maintenance.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class MaintenanceModeResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;
	
	public function __construct(\Niysu\HTTPResponseInterface $response, $maintenanceModeService, &$stopRoute) {
		$this->outputResponse = $response;

		$this->maintenanceMode = $maintenanceModeService->isMaintenanceMode();

		if ($this->maintenanceMode) {
			$this->outputResponse->setStatusCode(503);
			$this->outputResponse->setHeader('Content-Type', 'text/html');
			$this->outputResponse->setHeader('Retry-After', 120);
			$this->outputResponse->appendData('<html><head><title>Maintenance</title></head><body><h1>Website under maintenance</h1></body></html>');
			$stopRoute = true;
		}
	}

	public function setStatusCode($code) {
		if (!$this->maintenanceMode)
			$this->outputResponse->setStatusCode($code);
	}

	public function setHeader($header, $value) {
		if (!$this->maintenanceMode)
			$this->outputResponse->setHeader($header, $value);
	}

	public function addHeader($header, $value) {
		if (!$this->maintenanceMode)
			$this->outputResponse->addHeader($header, $value);
	}

	public function removeHeader($header) {
		if (!$this->maintenanceMode)
			$this->outputResponse->removeHeader($header);
	}

	public function appendData($data) {
		if (!$this->maintenanceMode)
			$this->outputResponse->appendData($data);
	}

	public function isHeadersListSent() {
		return !$this->maintenanceMode && $this->outputResponse->isHeadersListSent();
	}


	private $maintenanceMode;
}

?>