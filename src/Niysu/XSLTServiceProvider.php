<?php
namespace Niysu;

class XSLTServiceProvider {
	public function __invoke($response) {
		return new XSLTService($response);
	}
};

?>