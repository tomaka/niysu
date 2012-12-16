<?php
namespace Niysu;

class CacheMeServiceProvider {
	public function __invoke($scope) {
		return $scope->callFunction('Niysu\CacheMeService');
	}
};

?>