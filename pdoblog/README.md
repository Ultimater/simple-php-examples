# uBlog

An ultra simple micro blogging framework that can be used as a starting
point for other projects. It depends on PHP 5.5 and Bootstrap/jQuery and
was developed and tested on Archlinux and Ubuntu 14.04. It does not provide
any user management or any serious error checking to keep it very simple.

## Installation

To use, either clone this repo or download a zip file into your web root...

```bash
git clone https://github.com/markc/ublog.git
cd ublog
```

or...

```bash
wget https://github.com/markc/ublog/archive/master.zip
unzip master.zip
mv ublog-master ublog
cd ublog
```

## Database Setup

To set up the databases, edit `lib/sql/ublog-mysql.sql` and change the
`IDENTIFIED BY 'changeme'` password between the single quotes to something
that suits you...

```bash
cat lib/sql/ublog-mysql.sql | mysql -u root -p
cat lib/sql/ublog-sqlite.sql | sqlite3 lib/db/.ht_ublog.db
```

And make sure the web server has write permissions to the `lib/db` folder.

## Configuration

The `index.php` file acts as the main configuration file for the project
and all web requests go through it. There are no separate PHP scripts for
each kind of request, this is handled by including the required scripts to
handle the different web requests.

You need to edit index.php and change the `db[pass]` variable to the same
as whatever you changed the `IDENTIFIED BY` password to. If you are using
Archlinux or Ubuntu then leave the `db[sock]` as it is otherwise change it
to an empty string which will tell PDO to use the host and port settings
instead.

```php
  'db' => [
    'host'  => '127.0.0.1',
    'name'  => 'ublog',
    'pass'  => 'changeme', // ROOT.'.ht_pw.php',
    'path'  => ROOT.DS.'lib'.DS.'db'.DS.'.ht_ublog.db',
    'port'  => '3306',
    'sock'  => '/run/mysqld/mysqld.sock', // ''
    'type'  => 'sqlite',
    'user'  => 'ublog',
  ],
```

You can also create a `.ht_pw.php` in the root of the web folder where then
index.php resides and provide the MySQL password in that file so you don't
have to publish the password above. The `.gitignore` file is setup to ignore
this particular PHP file if you push this codebase back to Github. It just
needs to contain...

```php
<?php return 'YOUR_MYSQL_PASSWORD';
```

Then you can simply toggle the `db[type]` setting between `mysql` and `sqlite`
and use either or both databases.
