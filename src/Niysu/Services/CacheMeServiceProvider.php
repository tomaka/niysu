<?php
namespace Niysu\Services;

class CacheMeServiceProvider {
	public function __invoke($scope) {
		return $scope->call('Niysu\Services\CacheMeService');
	}
};

?>