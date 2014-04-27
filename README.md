# [php]

A simple set of PHP scripts that use [PDO] for backend database
connectivity and tested for use with both [MySQL] and [SQLite]. There
are minimal PHP dependencies aside from PHP version 5.5, for the inbuilt
password hashing functions, and short array syntax (ie; [] instead of
array()) since 5.4. Most of the scripts are single file and
self-contained aside from [Bootstrap] and [jQuery] pulled from a [CDN].

## [pdologin.php]

A login script that uses PHP SESSIONS, modern password- hashing and
salting and provides most of the basic functions that a real login
system actually needs.

## [pdopager.php]

An example of SERVER SIDE paging, filtering and sorting a HTML table
from a SQL database table. Unlike client side Javascript solutions like
[datatables.net], the core of this script is around 10K in size. It
comes with an associated MySQL dump of 2 tables and hints how to auto
convert the MySQL database to SQLite.
