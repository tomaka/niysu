# README

**Niysu** is a light but flexible and easy to use PHP framework.
Take any PHP framework, remove the annoying stuff, and you get Niysu!

[![build status](https://secure.travis-ci.org/Tomaka17/niysu.png)](http://travis-ci.org/Tomaka17/niysu)

See also [the API](http://tomaka17.github.com/niysu/doc/).

## Installation

To install Niysu, the first to do is to install [composer](http://getcomposer.org/).

Then in the main directory of your website, write a `composer.json` containing:

```json
{
    "require": {
    	"tomaka17/niysu": "dev-master"
    }
}
```

Now open a shell, and type:
```composer install```

This will install Niysu and all its dependencies in the `vendor` directory.

If you want to use Niysu in a PHP script, simply add this line:
```php
require 'vendor/autoload.php';			// change the path if you are not in the main directory
```

## Usage

Niysu is a front controller.
Every single request made to your web server should be passed to a single PHP file which will invoke Niysu.

This PHP is in four steps:
 - Include `vendor/autoload.php` to have access to Niysu
 - Create a new instance of Niysu\Server
 - Register all the resources (pages, images, RESTful resources, etc.) towards the server (note: don't worry, this is not as annoying as it sounds)
 - Call `$server->handle()`

### Hello world

This is an example of a "Hello world" server.
Put this code in a PHP file named `index.php`.
```php
<?php
require 'vendor/autoload.php';

// create a new instance of the server
$server = new Niysu\Server();

// registering a route, ie. a resource that the client can potentially request
$server->register('/', 'get', function($response) {

	// this function will only be called if the client asks for '/'
	$response->setPlainTextData('Hello world!');

});

// asking the server to handle the client's request
$server->handle();

?>
```

Now you should tell your web server to redirect all requests to this single file.
If you just want to test and don't want to install a real web server, you can use PHP's built-in server.

Under Linux, create this small script:
```sh
#!/bin/sh
php -S localhost:80 index.php
```

Under Windows, you can create a .bat file:
```bat
C:\path\to\php.exe -S localhost:80 index.php
```

Now that your server is started, you can browse to `http://localhost` and you should see `Hello world!`.

_Note: under Opera you have to use IPv6 and write `http://[::1]` instead of `http://localhost`.
This is a bug related to PHP's built-in server and won't happen with a real server._

### The two ways to register routes

There are two ways to register routes.

You either call `$server->register()` for every single route on your server, like we did above.
This is useful for small websites or to test something.

You can also ask Niysu to parse a class.
It will then read all the doxygen-style comments and build all the routes described in them. See the API for details.

Example:

```php
<?php
require 'vendor/autoload.php';

/**
 * @prefix /foo
 */
class Foo {
	/**
	 * @url /home
	 */
	public function homePage($response) {
		$response->setPlainTextData('Hello world!');
	}
}

$server = new Niysu\Server();
$server->parseClass('Foo');
$server->handle();

?>
```

In this example, Niysu will build a route with the URL `/foo/home`.
If the client tries to access this URL, then the `Foo::homePage` function will be called.


### Services

Niysu provides a lot of useful services. A service is an object which allows to easily do most of the things that a web server usually does.
Examples of services: database, cookies, http authentication, twig, caching.

For example, let's say you want to send a cookie to the client.
First, your handler must ask for the cookies service. To do so, simply add a `$cookiesService` parameter to the handling function.

```php
$server->register('/', 'get', function($response, $cookiesService) {
	$response->setPlainTextData('The cookie\'s value is: '.$cookiesService->cookieName);

	$cookiesService->add('cookieName', 'value', '2 days');
});
```

The first time a client accesses this page, the server will show `The cookie's value is: ` and send back a cookie named `cookieName`.
If the client accesses this page again in the next two days (the cookie's lifetime), the server will read the cookie's value and show `The cookie's value is: value`.

Services are only created if you ask them. For example if you never use a database on your server, then the database service will never be created.


### Variable parts in URLs

Most of the time you want URLs to have variable parts. For example, you have a resource at URL `/users/*` (where `*` means "anything").
To do so just put some brackets around the variable part, and put a variable name inside the brackets. This variable will be accessible in the handler.

Example:
```php
// this will work for /users/john, /users/18, etc. but not /users/john/doe
$server->register('/users/{userID}', 'get', function($userID, $response) {
	$response->setPlainTextData('Hi, '.$userID.'!');
});
```

You can also change what the variable part will match by setting a regular expression.

Example:
```php
$route = $server->register('/users/{userID}', 'get', function($userID, $response) {
	$response->setPlainTextData('Hi, '.$userID.'!');
});

// now {userID} only accepts numbers
// if you try to access /users/john you will get a 404 error (if no other route matches this URL)
$route->pattern('userID', '\\d+');
```


### Before functions

It is possible to add functions that will be called before the handler of a route.

```php
$route = $server->register('/', 'get', function($response) {
	$response->appendData('I\'m the last one!'.PHP_EOL);
});

$route->before(function($response) {
	$response->appendData('I\'m called first!'.PHP_EOL);
});

$route->before(function($response) {
	$response->appendData('I\'m second!'.PHP_EOL);
});
```

The purpose of before functions is to easily share functions between multiple routes.

Before functions have access to services and variable parts of the URL, just like the handler.
Even better: they can pass values to the handler by requesting a value by reference.

```php
$route = $server->register('/', 'get', function($response, $value) {
	$response->setPlainTextData('Value is: '.$value);
});

$route->before(function(&$value) {
	$value = 5;
});
```

There are also two special variable names: `isRightResource` and `stopRoute`.

If one of the before functions sets the value of `isRightResource` to false, then Niysu will understand that this resource doesn't match the user's request and will try to find another route.

If one of the before functions sets the value of `stopRoute` to true, then Niysu will stop the route right after the function returns. The request will be considered as successfully handled (even if an error code is returned) and no other before functions nor the handler will be called.

Example:
```php
$route = $server->register('/private', 'get', function($response) {
	$response->setPlainTextData('Welcome to the private room!');
});

$route->before(function(&$stopRoute, $response) {
	if (...) {
		$response->setStatusCode(403);
		$response->setPlainTextData('You don\'t have the right to access this page');
		$stopRoute = true;		// the handler (and the following before functions if there were any) won't get called
	}
});
```


### Filters

Filters are like services except that their purpose is to directly interact with the request and responses.
A filter class must be a derivate of either HTTPRequestInterface or HTTPResponseInterface.

When a filter is invoked for the first time, it automatically replaces the current request or response. All inputs or outputs are then passed through it.

For example, to add an ETag to your HTTP response, all you have to do is invoke the ETagResponseFilter once.

```php
$server->before(function($etagResponseFilter) { });

$server->register('/', 'get', function($response) {
	var_dump($response instanceof Niysu\Filters\ETagResponseFilter);	// true
	
	$response->appendData('test');		// goes to ETagResponseFilter, which in turn redirects this to the HTTPResponseGlobal
});
```

Technically, filters and services work exactly the same.
The only difference is that if a filter is a derivate of HTTPRequestInterface, then it will automatically replace the current request when created.
And if it is a derivate of HTTPResponseInterface, then it will automatically replace the current response when created.

Example of filters: JSONRequestFilter, TidyResponseFilter, FormAnalyserResponseFilter, etc.


### JQuery-like syntax

Most of the functions of the `Route` class return the route itself.
This allows you to write jquery-like paths without using any intermediate variable.

Example:
```php
$server
	->register('/users/{userID}', 'get')
	->pattern('userID', '\\d+')
	->before(function() {
		...
	})
	->before(function() {
		...
	})
	->handler(function() {
		...
	});
```

### Route collections

It is possible to create groups of routes named collections. In fact the `Server` class contains a collection which is the main collection.
Every time you call `$server->register`, a new route is created in this main collection.

To create a new collection child of the main collection, just call `$server->buildCollection()`. If you want, you can even create a child of this child using `$childCollection->newChild()`.

You can add before functions to a collection using `$collection->before()`. All the routes in this collection and this collection's children will start by calling the function you register here.
To add a before function to the main collection, call `$server->before()`.

You can also add a prefix to all the URL of a collection. Just call `$collection->prefix('/pages')`. You can't modify the prefix of the main collection.

Example:
```php
$collection = $server->buildCollection();

$collection->prefix('/admin');

$collection->before(function($response) {
	$response->appendData('I\'m second!');
});

$collection
	->register('/main', 'get')
	->before(function($response) {
		$response->appendData('I\'m third!');
	});
	->handler(function($response) {
		$response->appendData('I\'m fourth!');
	});

$server->before(function($response) {
	$response->appendData('I\'m called first!');
});
```

In this example, we define a route whose URL is in fact '/admin/main'.
When accessed, the server will first call the before function of the main collection (the one we register with `$server->before`), then the collection's before handlers, then the route's before functions, and finally the route's handler.

The `parseClass` function that we saw earlier in fact creates and returns a route collection. Thus you can easily "customize" you classes:

```php
$adminSite = $server->parseClass('AdminSite');
// $adminSite is a collection, child of the main collection

$adminSite->prefix('/admin');

$adminSite->before(function(&stopRoute, $response) {
	if (!isUserAdmin()) {		// pseudo-code
		$response->setStatusCode(403);
		$stopRoute = true;
	}
});
```

## Misc

### Documentation

See [the API](http://tomaka17.github.com/niysu/doc/) and [the wiki](https://github.com/Tomaka17/niysu/wiki).

### License

All the code is under MIT license. See the `LICENSE` file.
