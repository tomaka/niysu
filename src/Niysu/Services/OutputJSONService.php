<?php
namespace Niysu\Services;

/**
 * Allows to easily send JSON data to the response.
 */
class OutputJSONService {
	public function __construct(\Niysu\Scope $scope) {
		$this->scope = $scope;
	}

	/**
	 * Turns a JSON object into a string.
	 *
	 * The implementation simply calls json_encode, see php.net
	 *
	 * @param mixed 	$json 		Data representing JSON
	 * @return string
	 */
	public function toString($json) {
		return json_encode($json);
	}

	/**
	 * Turns a JSON object into a string and sends it to the response.
	 *
	 * @param mixed 	$json 		Data representing JSON
	 */
	public function output($json) {
		if (!isset($this->scope->response))
			throw new \LogicException('Cannot be called from outside a route');
			
		$this->scope->response->setHeader('Content-Type', 'application/json');
		$this->scope->response->appendData($this->toString($json));
	}


	private $scope;
};

?>