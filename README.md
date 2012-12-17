**Niysu** is a light but flexible and easy to use PHP framework.
Take any PHP framework, remove the annoying stuff, and you get Niysu!

[![build status](https://secure.travis-ci.org/Tomaka17/niysu.png)](http://travis-ci.org/Tomaka17/niysu)

See [the wiki](https://github.com/Tomaka17/niysu/wiki) for documentation.

Examples
========

Hello world
-----------
```php
// create a new instance of the server ; this is where you will pass the configuration file
$server = new Niysu\Server();

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

Examples of available services: database, session, http basic auth, etc.

Before handlers
---------------
It is possible to add functions that will be called before the handler of a given route in executed.
This allows you to share code between multiple handlers.
```php
$server
	->register('/users/{userID}', 'get')
	->before(function($userID, &$user, $databaseService) {
		$user = $databaseService->users[['id' => $userID]];
	})
	->handler(function($user, $response) {
		$response->appendData($user->name);
	}));
```

Documentation
=============
See [the wiki](https://github.com/Tomaka17/niysu/wiki).


About
=====

License
-------
All the code is under MIT license. See the `LICENSE` file.
