Handling errors
===============

If you try to access an non-existing page of your website or if you get an error, you will get a blank page.
To circumvent that, you need to use the `ErrorPagesResponseFilter`

The first thing to do is to enable the filter. Add this to your global configuration:

	before: [
		...
		function($errorPagesResponseFilter) {},
		...
	]

Now you just need to name some routes like status codes.
For example if you name a route `404`, it will automatically be called instead of a 404 response.

	$server
		->register()
		->name('404')
		->handler(function($plainTextOutput) {
			$plainTextOutput->setText('Page not found!');
		});
