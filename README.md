README
======

**Niysu** is a light but powerful PHP framework.

Basic usage
-----------

Hello world:
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

You can add variable parts in the URL, and access them in the handling function:
```php
$server->register('/users/{userID}', 'get', function($userID, $response) {
	$response->setPlainTextData('Hi, '.$userID.'!');
});
```


Details
-------

