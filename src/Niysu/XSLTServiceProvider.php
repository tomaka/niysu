<?php
namespace Niysu;

class XSLTServiceProvider {
	public function __invoke($scope) {
		return $scope->callFunction('Niysu\XSLTService');
	}
};

?>