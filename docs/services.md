Services, contexts, filters, inputs and outputs
===============================================

Niysu provides a lot of useful services. A service is an object which allows to easily do most of the things that a web server usually does.
Examples of services: database, authentication, twig, caching.

To access a service, all you need to do is use its name as a parameter.

	$server->register('/', 'get', function($databaseService) {
		/* you can use the $databaseService here */
	});

Services are only created if you ask them. For example if you never use a database on your server, then the database service will never be created.
