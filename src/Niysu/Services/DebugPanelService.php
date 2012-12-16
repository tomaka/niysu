<?php
namespace Niysu\Services;

class DebugPanelService {
	public function __construct(&$response) {
		if (!$response)
			throw new \LogicException('DebugPanelService can\'t be used outside of a route');

		$response = new \Niysu\HTTPResponseCustomFilter($response, function($content) {
			if (!$this->active)
				return $content;
			
			if ()
		});
	}

	public function activate() {
		$this->active = true;
	}

	public function deactivate() {
		$this->active = false;
	}


	private $active;
};

?>