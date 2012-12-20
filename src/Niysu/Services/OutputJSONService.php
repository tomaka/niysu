<?php
namespace Niysu\Services;

/// \brief 
class OutputJSONService {
	public function __construct(\Niysu\Scope $scope) {
		$this->scope = $scope;
	}

	public function toString($json) {
		return json_encode($json);
	}

	public function output($json) {
		$this->scope->response->setHeader('Content-Type', 'application/json');
		$this->scope->response->appendData($this->toString($json));
	}


	private $scope;
};

?>