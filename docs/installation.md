Installation
============

Installing composer
-------------------

To install Niysu, the first thing to do is to install [composer](http://getcomposer.org/).
**Composer** is a small PHP script which allows you to easily install and upgrade any PHP library you depend upon.

Simply follow [these instructions](http://getcomposer.org/doc/00-intro.md#installation-nix) in order to install composer.

Downloading and installing Niysu
--------------------------------

Now we will tell composer that we want to use Niysu, and it will download it for us. To do so, write a `composer.json` file in the main directory of your website and put this inside:

	{
	    "require": {
	    	"tomaka17/niysu": "dev-master"
	    }
	}

Now open a shell, and type:

	composer install

This will install Niysu and all its dependencies in the `vendor` directory.

Writing your first script
-------------------------

Niysu is a **front controller**, which means that every single request must pass through it (except static resources if you wish so, for performances reasons).
This means that you must write a file (for example `index.php`) which will handle every requests received by your server, and which will dispatch it to the right controller.

Here is an example `index.php`:

	<?php
	require 'vendor/autoload.php';

	$server = new \Niysu\Server();

	$server->register('/', 'get', function($plainTextOutput) {
		$plainTextOutput->setText('Hello world!');
	});

	$server->handle();


Setting up the web server
-------------------------

You need to set up your web server so that it redirects every single request to your `index.php`.

PHP test server

Since version 5.4, PHP comes with an internal web server for testing purposes.

Just type this is a shell in the directory of your `index.php`, and it will start the server:

	php -S localhost:80 index.php
