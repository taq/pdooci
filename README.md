# PDOCI

Wrapping on PHP OCI functions to simulate a PDO object, using just pure PHP and the oci_* functions.

Let's face it. Installing PHP, PDO, Oracle drivers and PDO OCI is not a pleasant
task. Is more pleasant to insert bamboo sticks under your fingernails than make
all the voodoo needed to accomplish that task. And there are two big problems
with that:

1. If you install `pdo_oci` with `pecl` you'll get a version from 2005 (http://pecl.php.net/package/PDO_OCI). 
   Even Christian Bale is now far from the things from 2005, and wow, he had a cool suit and a very nice car. 
   And all came in black.

2. If you follow the official docs, you'll need to compile PHP and still get an
   *experimental* extension (http://www.php.net/manual/ref.pdo-oci.php). Come on. 
   We can't (yeah, we know how to do it!) compile PHP on every server we need and just for an experimental feature?

That's why I made `PDOOCI`.

## Installation

First install the Oracle drivers (I like the instant client versions) and the
`oci8` package (with `pecl`, this one seems to be updated often).

### With Composer

```
$ composer require taq/pdooci
```

```json
{
    "require": {
        "taq/pdooci": "^1.0"
    }
}
```

```php
<?php
require_once 'vendor/autoload.php';

$pdo = new PDOOCI\PDO("mydatabase", "user", "password");
```

### Without Composer

Why are you not using [composer](http://getcomposer.org/)? Download the `src` folder from the repo and rename it to `PDOOCI`, then require the `PDOOCI/PDO.php` file.

```php
require_once "PDOOCI/PDO.php";

$pdo = new PDOOCI\PDO("mydatabase", "user", "password");
```

Yeah, the rest should work exactly the same as if you were using a PDO object. :-)

## Testing

There is a test suite (using `PHPUnit` with a version bigger than 6.x) on the `test` directory. If you want to
test (you must test your code!), create a table called `people` with two
columns:

1. `name` as `varchar2(50)`
2. `email` as `varchar2(30)` 

And some environment variables:

1. `PDOOCI_user` with the database user name
2. `PDOOCI_pwd` with the database password
3. `PDOOCI_str` with the database connection string

And then go to the `test` dir and run `PHPUnit` like:

```
phpunit --colors .
```
