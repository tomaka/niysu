---
layout: usage
title: 'Cookies'
---

<h2>Cookies</h2>

<pre class="brush: php">
$server
	->register()
	->handler(function($cookiesContext) {
		$cookiesContext->cookie1 = 'test';

		$cookiesContext->add('pi', 3.14, '10 years');
	});
</pre>
