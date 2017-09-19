# HOW NOT TO CODE
https://github.com/Ultimater/simple-php-examples/blob/master/lib/php/pdologin.php#L100

---


# Simple PHP Exmaples

A simple set of PHP scripts that use [PDO] for backend database
connectivity and tested for use with both [MariaDB] and [SQLite]. There
are minimal PHP dependencies aside from PHP version 5.5, for the inbuilt
password hashing functions, and short array syntax (ie; [] instead of
array()) since 5.4. Most of the scripts are single file and
self-contained aside from [Bootstrap] and [jQuery] pulled from a [CDN].


## [pdoforum.php]

TODO


## [pdopager.php]

An example of SERVER SIDE paging, filtering and sorting a HTML table
from a SQL database table. Unlike client side Javascript solutions like
[datatables.net], the core of this script is around 10K in size. It
comes with an associated MySQL dump of 2 tables and hints how to auto
convert the MySQL database to SQLite.


## [pdoticket.php]

TODO

Originally based on: https://github.com/jwalanta/tit


## [pdousers.php]

A login script that uses PHP SESSIONS, modern password- hashing and
salting and provides most of the basic functions that a real login
system actually needs.


## Notes

Requires at least PHP 5.5

Use mysql2sqlite-sh to convert MySQL/MariaDB direct to SQLite

https://gist.github.com/esperlu/943776

The format of the hidden password file is...

    <?php return 'YOUR_PASSWORD';


[PDO]: http://php.net/PDO
[MariaDB]: http://mariadb.org
[SQLite]: http://sqlite.org
[Bootstrap]: http://getbootstrap.com
[jQuery]: http://jquery.com
[CDN]: http://bootstrapcdn.com

[pdoforum.php]: pdoforum.php
[pdopager.php]: pdopager.php
[pdoticket.php]: pdoticket.php
[pdousers.php]: pdousers.php
