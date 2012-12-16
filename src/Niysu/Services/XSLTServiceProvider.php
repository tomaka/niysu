<?php
namespace Niysu\Services;

class XSLTServiceProvider {
	public function __invoke($scope) {
		return $scope->callFunction('Niysu\Services\XSLTService');
	}
};

?>