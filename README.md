**Niysu** is a light but powerful PHP framework.

Examples
========

Hello world
-----------
```php
use Niysu\Server;

// create a new instance of the server ; this is where you will pass the configuration file
$server = new Server();

// registering a route, ie. a resource that the client can potentially request
$server->register('/', 'get', function($response) {
	// this function will only be called if the client asks for '/'
	$response->setPlainTextData('Hello world!');
});

// asking the server to handle the request given as parameter,
//   or by default the client's request
$server->handle();
```

Variable parts
--------------
You can add variable parts in the URL, and access them in the handling function:
```php
// this will work for /users/john, /users/18, etc. but not /users/john/doe
$server->register('/users/{userID}', 'get', function($userID, $response) {
	$response->setPlainTextData('Hi, '.$userID.'!');
});
```

Services
--------
The library provides a services system. For example, you can access the "logService" like this:
```php
$server->register('/echo', 'get', function($request, $response, $logService) {
	$logService->debug('echo!');
	$response->appendData($request->getRawData());
});
```

Example of available services: database, session, etc.

Before handlers
---------------
It is possible to add functions that will be called before the handler of a given route in executed.
This allows you to share code between multiple handlers.
```php
$server->register('/users/{userID}', 'get', function($user, $response) {
	$response->appendData($user->getName());

})->before(function($userID, &$user, $databaseService) {
	$user = new User($userID, $databaseService);
});
```
