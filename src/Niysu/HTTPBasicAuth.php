<?php
namespace Niysu;

class HTTPBasicAuth {
	/// \brief Pass this function to the ->before() route
	/// \param $requiredPermission If the user doesn't have this permission, 
	/// \param &$authUserID ID of the logged user in the database
	/// \param &$authUserPermissions An array where keys are the permissions
	public static function authorizationRequired($requiredPermission, &$authUserID, &$authUserPermissions, $request, $response, $server, &$ignoreHandler) {
		$authHeader = $request->getHeader('Authorization');
		$statusOnFail = intval($request->getHeader('X-StatusOnLoginFail'));
		if (!$statusOnFail)
			$statusOnFail = 401;
		
	    if ($authHeader && preg_match('/\s*Basic\s+(.*)$/i', $authHeader, $matches)) {
            list($login, $password) = explode(':', base64_decode($matches[1]));
            
        	$authUserID = self::getUserID($server->getService('database'), $login, $password);
        	if ($authUserID === null) {
				$response->setStatusCode($statusOnFail);
				$response->setHeader('WWW-Authenticate', 'Basic realm="fdmjc67"');
				$response->setPlainTextData('Wrong login or password');
				$ignoreHandler = true;
        	}
        	
        } else {
			$response->setStatusCode($statusOnFail);
			$response->setHeader('WWW-Authenticate', 'Basic realm="fdmjc67"');
			$response->setPlainTextData('You are not logged in');
			$ignoreHandler = true;
        }
	}
	
	private static function getUserID($db, $login, $pass) {
	    $result = $db->querySingle('SELECT id FROM fdmjc_utilisateurs WHERE pass = MD5(:pass) AND (email = :login OR LOWER(CONCAT(prenom, \' \', nom)) LIKE LOWER(:login) OR LOWER(CONCAT(nom, \' \', prenom)) LIKE LOWER(:login)) LIMIT 1', [':login' => $login, ':pass' => $pass]);
	    if (!$result)
	        return null;
	    return $result[0];
	}
};

?>