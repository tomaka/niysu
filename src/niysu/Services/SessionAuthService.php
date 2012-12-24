<?php
namespace Niysu\Services;

/**
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class SessionAuthService {
	public static function beforeMustBeAuthenticated($loginFormURL) {
		return function($sessionAuthService, $response, &$callHandler) {
			if ($sessionAuthService->login())
				return;
			$response->setStatusCode($statusOnFail);
			$response->setHeader('Location', 'Basic realm="'.$realm.'"');
			$callHandler = false;
		};
	}

	public function __construct($sessionService) {
		$this->sessionService = $sessionService;
	}

	public function login() {
		return isset($sessionService->sessionAuthService_login);
	}
	


	private $sessionService;
};

?>