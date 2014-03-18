PDOCI
=====

Wrapping on PHP OCI functions to simulate a PDO object, since PDO support for OCI is very confuse and slow.

Let's face it. Installing PHP, PDO, Oracle drivers and PDO OCI is not a pleasant
task. Is more pleasant to insert bamboo sticks under your fingernails than make
all the voodoo needed to accomplish that task. And there are two big problems
with that:

1. If you install `pdo_oci` with `pecl` you'll get a version from 2005. Even
   Christian Bale is now far from the things from 2005, and wow, he had a cool
   costume and a very nice car.

2. If you follow the official docs, you'll need to compile PHP and still get an
   *experimental* feature. Come on. We can't (yeah, we know how to do it!)
   compile PHP on every server we need and just for an experimental feature?

That's why I made `PDOOCI`.

What is needed
--------------

Just install the Oracle drivers (I like the instant client versions) and the
`oci8` package (with `pecl`, this one seems to be updated often). Then insert
the `pdooci.php` file and change some code like

```
$pdo = new PDO("oci:dbname=mydatabase;charset=utf8", "user", "password");
```

to 

```
$pdo = new PDOOCI\PDO("oci:dbname=mydatabase;charset=utf8", "user", "password");
```

Yeah, the rest should work. :-)
