PHPCheck
========

PHPCheck is a static analyzer for PHP to assist developers in finding errors in PHP applications
before shipping them. It searches through all files and collects information about the types,
function calls and variables. Finally it analyzes this information and tries to find potential
errors in it.

It exists to overcome a problem of interpreted languages: There is no compile time. Therefore, you
will errors *only* until you run the application. Running it does always mean that exactly one code
path is taken. For example, if you have an if-else statement, it will always execute either the if
branch or the else branch, but never both. If the else branch is executed nearly never, the
probability that errors in it are not found is quite high.

Errors in this case mean for example:

- Call of not-existing function
- Instantiation of not-existing class
- Wrong parameter count
- Wrong parameter types
- Method call of an non object
- ...

So, semantic errors, that PHP would complain about, if it executed the affected code path. To give
developers the chance to find these semantic errors in PHP applications ahead of time, I wrote this
tool. Note however, that there will be false positives and false negatives due to the lack of
information at compile time. For example, variables that are initialized with a value from an
external source (GET, POST, ...). PHP makes it even more difficult for us by providing things like
variable variables, variable function calls, variable class instantiations and so on. And of course,
PHP is a dynamically typed language, so that we simply don't know the type in most cases.

In summary: Don't expect this tool to be perfect and always correct. It should give you **hints** to
things that **might** by wrong. Nothing more. So, **you** have to check the potential problems
pointed out by PHPCheck and verify whether there really is a problem. And, of course, if PHPCheck
does not find errors, it does **not** mean, that there are none ;)

Installation:
-------------

Just perform the following steps:

1. Retrieve FrameWorkSolution: `$ git submodule init && git submodule update`.
2. Create a directory named `cache` in the root directory.
3. Ensure that the webserver has write permissions for the `cache` directory.
4. Create a MySQL database and import the `install/structure.sql`.
5. Copy the config/mysql.php.sample to config/mysql.php and adjust it accordingly.
6. Check if `PC_PHP_EXEC` in `config/userdef.php` is defined correctly for your system.

How to use it:
--------------

Before you start, choose the project "-PHP builtin-" and parse the PHP manual. Afterwards, PHPCheck
knows the builtin functions and classes, so that it can tell you when you're using them in a wrong
way.

To analyze an application, use the following steps:

1. Create a project for your application.
2. Use the type scanner to collect classes, contants, fields, methods and functions.
3. Use the statement scanner to walk through the code, track variable assignments and collects
   function calls.
4. Finally, use the analyzer to search for potential errors within the collected data.

That means, after step 2, classes, functions and constants will be available. After the step 3,
calls and variables. Although step 4 detects most of the potential errors, the previous two steps
raise errors as well.

Important internals:
--------------------

The type of a variable relies heavily on information from PHPDoc tags and type hinting. The used
PHPDoc Tags are:

- `@var <type> ...`
- `@param <type> <paramName> ...`
- `@return <type> ...`

The supported types are:

- Integer: `integer`, `int`, `long`, `short` or `byte`
- Boolean: `bool` or `boolean`
- Float: `float` or `double`
- String: `string`, `str` or `char`
- Array: `array`
- Generic object: `object`
- Specific object: `<yourClassName>`
- Resource: `resource` or `res`
- Various types: `mixed`

Multiple types can be specified by separating them with '|'.

The more specific you describe your variables, the better the analysis results will get. That means,
you should avoid mixed and multiple types (or better: don't write such code).

PHPCheck treats all files given as included at all locations, i.e., it does not follow includes but
simply assumes that everything is present everywhere. For example, you should not define a class
twice and include sometimes the first and sometimes the second.

Note also, that some of the reported errors are not reported as errors by the PHP interpreter, but
is rather bad practice. For example, PHPCheck will complain about unknown variables or class fields,
although PHP permits to use variables without defining them before and lets you also create class
fields dynamically.

Similarly, ill-formed or missing PHPDoc comments are detected. That means, the tool will complain if
you don't document parameters or return values or if you document non-existent parameters. This
serves also the purpose of improving the quality of PHPChecks analysis. Because without relying on
PHPDoc comments, it is not possible in many cases (at least, in "real" applications) to determine
the type of a variable at compile time.

Limitations:
------------

Currently, most of the new language features (except anonymous functions) introduced since
PHP 5.2.0 are not yet supported.
