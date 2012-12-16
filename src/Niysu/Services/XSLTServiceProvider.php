<?php
namespace Niysu\Services;

class XSLTServiceProvider {
	public function __invoke($scope) {
		return $scope->call('Niysu\Services\XSLTService');
	}
};

?>