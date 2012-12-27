<?php
namespace Niysu\Filters;

/**
 * Automatically sends back an error page if the website is under maintenance.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class MaintenanceModeResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $response, $maintenanceModeService, &$stopRoute) {
		parent::__construct($response);

		$this->maintenanceMode = $maintenanceModeService->isMaintenanceMode();

		if ($this->maintenanceMode) {
			parent::setStatusCode(503);
			parent::setHeader('Content-Type', 'text/html');
			parent::appendData('<html><head><title>Maintenance</title></head><body><h1>Website under maintenance</h1></body></html>');
			$stopRoute = true;
		}
	}

	public function setStatusCode($code) {
		if (!$this->maintenanceMode)
			parent::setStatusCode($code);
	}

	public function setHeader($header, $value) {
		if (!$this->maintenanceMode)
			parent::setHeader($header, $value);
	}

	public function addHeader($header, $value) {
		if (!$this->maintenanceMode)
			parent::addHeader($header, $value);
	}

	public function appendData($data) {
		if (!$this->maintenanceMode)
			parent::appendData($data);
	}

	public function isHeadersListSent() {
		return !$this->maintenanceMode && parent::isHeadersListSent();
	}


	private $maintenanceMode;
}

?>