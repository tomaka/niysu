---
layout: usage
title: 'How to register routes'
---

<h2>How to register routes</h2>

<p>There are two methods available to you in order to give the Niysu server a list of routes.</p>

<h3>Direct registration</h3>

<p></p>

<h3>Parsing classes</h3>

<p>The alternative way to register routes is to ask Niysu to parse PHP classes.</p>

<p>First, you need to write a PHP class like this:</p>

<pre class="brush: php">
class Foo {
	/**
	 * @url /test
	 */
	public function testRoute($plainTextOutput) {
		$plainTextOutput->setText('hello world');
	}
}
</pre>

<p>Now just register the class to the server:</p>

<pre class="brush: php">
$server->parseClass('Foo');
</pre>

<p>Here is the list of supported parameters for routes, ie. member functions:</p>
<ul>
<li>@before <em>...</em> : see description below</li>
<li>@disabled : if set, then this route will not be created</li>
<li>@method &lt;regex&gt; : regex that the HTTP method must match, by default <code>GET</code></li>
<li>@name &lt;name&gt; : name of the route</li>
<li>@pattern &lt;variable&gt; &lt;regex&gt; : if the URL contains variables, you can set an regex that the variable must match</li>
<li>@url &lt;url&gt; : regex that the URL must match for this route to be executed</li>
<li>@uri &lt;url&gt; : alias of @url</li>
</ul>

<p>Here is the list of supported parameters for classes:</p>
<ul>
<li>@before <em>...</em> : see description below</li>
<li>@prefix &lt;prefix&gt; : sets the prefix that all URLs of routes in this class will use ; can be overwritten by calling RoutesCollection->prefix()</li>
<li>@static &lt;path&gt; : adds a path of static resources that will be served by the server ; path is relative to the class location</li>
</ul>

<p>The <code>@before</code> parameter accepts several different syntaxes:</p>
<ul><li>@before &lt;global_function&gt; where global_function is the name of a global function to be called before the handler ; its arguments will be taken from the scope</li>
<li>@before &lt;method&gt; where method is the name of a method of the current class to be called before the handler ; its arguments will be taken from the scope</li>
<li>@before &lt;class&gt; where class is a class name ; the before function will simply invoke the given class and do nothing</li>
<li>@before {class}::{method} {params}
where {class} is a class name, {method} is the name of a method of the class, and {params} can be eval'd as a PHP array
the before function will try to find in the scope an object whose type is the class, and call the method on it</li>
<li>@before function(&lt;params&gt;) { <em>...php code...</em> } the function and code will be eval'ed and called</li>
<li>@before onlyif &lt;anything&gt; where &lt;anything&gt; can be any of the other syntaxes above, and &lt;code&gt; is a status code ; if the "anything part" returns false, then the route is considered not to match and another route will be tried (as if <code>isRightResource</code> was set to false from within the route)</li>
<li>@before validate &lt;code&gt; &lt;anything&gt; where &lt;anything&gt; can be any of the other syntaxes above, and &lt;code&gt; is a status code if the "anything part" returns false, then the route is stopped and the status code is set to the response (as if stopRoute was set to true from within the route)</li>
<li>@before $<em>varName</em> = <em>anything</em> where <em>anything</em> can be any of the other syntaxes above ; the variable $<em>varName</em> of the scope will be set to the return value of the "anything part"</li>
</ul>
