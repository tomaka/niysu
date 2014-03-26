Security 101
============


Never trust user input
----------------------

You must **always** validate user inputs.


Use SQL prepared statements
----------------------

If you use a database, **never** write something like this:

	$databaseService->query('SELECT col FROM table WHERE id = '.$id);

...except if you are **100% sure** that `$id` is a number and not a string.

Instead you should write this:

	$databaseService->query('SELECT col FROM table WHERE id = ?', [ $id ]);

Or if you have multiple parameters:

	$databaseService->query('SELECT col FROM table WHERE id = ? AND id2 = ?', [ $id, $id2 ]);

Or:

	$databaseService->query('SELECT col FROM table WHERE id = :param', [ ':param' => $id ]);
