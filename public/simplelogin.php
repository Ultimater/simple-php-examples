<?php

/**
 * A simple, clean and secure PHP Login Script
 *
 * SINGLE FILE FUNCTIONAL VERSION
 *
 * A simple PHP Login Script that uses PHP SESSIONS, modern password-hashing
 * and salting and gives the basic functions a proper login system needs.
 *
 * @package simplelogin
 * @author Mark Constable <markc@renta.net>
 * @link https://github.com/markc/php
 * @license http://opensource.org/licenses/AGPL-3.0
 */

if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    // wget https://raw.github.com/ircmaxell/password_compat/master/lib/password.php
    require("password.php");
}

session_start();
error_log('GET='.var_export($_GET, true));
error_log('POST='.var_export($_POST, true));
error_log('SESSION='.var_export($_SESSION, true));

echo view(init(cfg(array(
    'title'         => 'Simple PHP Login',
    'db'            => null,
    'dbconf'        => array(
        'host'      => 'localhost',
        'name'      => 'users',
        'pass'      => 'changeme',
        'path'      => 'users.db',
        'port'      => '3306',
        'type'      => 'mysql',
        'user'      => 'root'),
    'admin'         => array(
        'id'        => '1000',
        'uid'       => 'admin',
        'fname'     => 'System',
        'lname'     => 'Administrator',
        'email'     => 'admin@localhost.lan',
        'passwd'    => 'changeme',
        'updated'   => '',
        'created'   => ''),
    'views'         => array(
        'home',
        'about',
        'login',
        'logout',
        'register',
        'forgotpw',
        'profile',
        'install'
    )))));

function init($cfg)
{
    if (!empty($_POST)) cfg('db', db_init($cfg['dbconf']));
    $a = $_SESSION['a'] = isset($_REQUEST['a'])
        ? strtolower(str_replace(' ', '_', trim($_REQUEST['a'])))
        : 'home';
    return in_array($a, $cfg['views']) ? $a() : '<b>View does not exist</b>';
}

// public callable functions

function home()
{
    return '
    <p>TODO: This is the Home page.</p>';
}

function about()
{
    return '
    <p>TODO: This is the About page.</p>';
}

function profile()
{
    return isset($_SESSION['u']) ? '
    <p>
      Hello, '.$_SESSION['u']['name'].'. You are now logged in. Try to close
      this browser tab and open it again. Still logged in! ;)
    </p>
    <p>
      <a class="btn" href="?a=logout">Logout</a>
    </p>': login_form();
}

function logout()
{
    unset($_SESSION['u']);
    $_SESSION['m'] = "You are now logged out";
    header('Location: '.$_SERVER['PHP_SELF']);
    exit();
}

function login()
{
    if (!empty($_POST)) {
        $m = '';
        $u = read_user($_POST['u']['uid']);
        if (isset($u['uid'])) {
            if (password_verify($_POST['u']['passwd'], $u['passwd'])) {
                $_SESSION['u'] = $u;
                header('Location: '.$_SERVER['PHP_SELF']);
                exit();
            } else $m = 'Passwords do not match';
        } else $m = 'Username does not exist';
        $_SESSION['m'] = $m;
    }
    return login_form();
}

function register()
{
    if (!empty($_POST)) {
        $m = '';
        if ($_POST['user_name']) {
            if ($_POST['user_password_new']) {
                if ($_POST['user_password_new'] === $_POST['user_password_repeat']) {
                    if (strlen($_POST['user_password_new']) > 5) {
                        if (strlen($_POST['user_name']) < 65 && strlen($_POST['user_name']) > 1) {
                            if (preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
                                $user = read_user($_POST['user_name']);
                                if (!isset($user['user_name'])) {
                                    if ($_POST['user_email']) {
                                        if (strlen($_POST['user_email']) < 65) {
                                            if (filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
                                                create_user();
                                                $_SESSION['m'] = 'You are now registered so please login';
                                                header('Location: '.$_SERVER['PHP_SELF']);
                                                exit();
                                            } else $m = 'You must provide a valid email address';
                                        } else $m = 'Email must be less than 64 characters';
                                    } else $m = 'Email cannot be empty';
                                } else $m = 'Username already exists';
                            } else $m = 'Username must be only a-z, A-Z, 0-9';
                        } else $m = 'Username must be between 2 and 64 characters';
                    } else $m = 'Password must be at least 6 characters';
                } else $m = 'Passwords do not match';
            } else $m = 'Empty Password';
        } else $m = 'Empty Username';
        $_SESSION['m'] = $m;
    }
    return register_form();
}

function install()
{
    $dbc = cfg('dbconf');
    $dbh = cfg('db', db_init($dbc));
    $pri = $dbc['type'] === 'mysql'
        ? 'int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT'
        : 'INTEGER PRIMARY KEY';
    $ind = $dbc['type'] === 'mysql'
        ? 'ALTER TABLE `uid` ADD UNIQUE (`uid`); ALTER TABLE `email` ADD UNIQUE (`email`);'
        : 'CREATE UNIQUE INDEX `uid_UNIQUE` ON `users` (`uid` ASC); CREATE UNIQUE INDEX `email_UNIQUE` ON `users` (`email` ASC);';

    // uncomment below to reinstall tables while testing
    $dbh->exec("DROP TABLE IF EXISTS `users`;");

    try {
        $dbh->exec("
 CREATE TABLE IF NOT EXISTS `users` (
        `id` $pri,
        `uid` varchar(63),
        `fname` varchar(31),
        `lname` varchar(31),
        `email` varchar(63),
        `passwd` varchar(255),
        `updated` datetime,
        `created` datetime
); $ind");
    } catch (PDOException $e) { die($e->getMessage()); }
    create_user(cfg('admin'));
    unset($_SESSION['u']);
    $_SESSION['m'] = 'Database and default user are now installed, please login';
    header('Location: '.$_SERVER['PHP_SELF']);
    exit();
}

// private support functions

function cfg($k = NULL, $v = NULL)
{
    static $stash = array();
    if (empty($k)) return $stash;
    if (is_array($k)) return $stash = array_merge($stash, $k);
    if ($v) $stash[$k] = $v;
    return isset($stash[$k]) ? $stash[$k] : NULL;
}

function view($content)
{
    $m = isset($_SESSION['m']) ? '
    <p class="m">'.$_SESSION['m'].'</p>' : '';
    unset($_SESSION['m']);

    return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>'.cfg('title').'</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
body { margin: 0 auto; width: 42em; }
h1, form, .m { text-align: center; }
label { display: inline-block; width: 10em; text-align: right;  }
a { text-decoration: none; }
.m, .btn { border: 1px solid #CFCFCF; padding: 0.25em 1em 0.5em 1em; border-radius: 0.3em; }
.m { background-color: #FFEFEF; color: #DF0000; font-weight: bold; }
.btn { background-color: #EFEFEF; font-size: 75%; }
.btn:hover { background-color: #DFDFDF; }
    </style>
  </head>
  <body>
    <h1>'.cfg('title').'</h1>'.$m.$content.'
  </body>
</html>
';
}

function login_form()
{
    $uid = isset($_POST['u']['uid']) ? $_POST['u']['uid'] : '';
    return '
    <form method="post" action="?a=login">
      <label>Login ID</label>
      <input type="text" name="u[uid]" value="' . $uid . '" required>
      <br>
      <label>Password</label>
      <input type="password" name="u[passwd]" autocomplete="off" required>
      <br>
      <br>
      <input type="submit" value="Login" />
      <br>
      <br>
      <a class="btn" href="?a=register">Register New Account</a>
    </form>';

}

function register_form()
{
    $uid = isset($_POST['u']['uid']) ? $_POST['u']['uid'] : '';
    $fname = isset($_POST['u']['fname']) ? $_POST['u']['fname'] : '';
    $lname = isset($_POST['u']['lname']) ? $_POST['u']['lname'] : '';
    $email = isset($_POST['u']['email']) ? $_POST['u']['email'] : '';
    return '
    <form method="post" action="?a=register">
      <p>All fields are required. Username must be only letters and numbers from<br>
      2 to 64 characters long and the password has to be at least 6 characters.</p>
      <label>Username</label>
      <input type="text" pattern="[a-zA-Z0-9]{2,64}" name="u[uid]" value="' . $uid . '" required>
      <br>
      <label>First Name</label>
      <input type="text" pattern="[a-zA-Z0-9]{2,32}" name="u[fname]" value="' . $fname . '" required>
      <br>
      <label>Last Name</label>
      <input type="text" pattern="[a-zA-Z0-9]{2,32}" name="u[lname]" value="' . $lname . '" required>
      <br>
      <label>Email Address</label>
      <input type="email" name="u[email]" value="'.$email.'" required>
      <br>
      <label>Password</label>
      <input type="password" name="u[pass1]" pattern=".{6,}" required autocomplete="off">
      <br>
      <label>Confirm Password</label>
      <input type="password" name="u[pass2]" pattern=".{6,}" required autocomplete="off">
      <br>
      <br>
      <input type="submit" value="Register">
      <br>
      <br>
      <a class="btn" href="?a=login">&laquo; Back to Login Page</a>
    </form>';

}

// CRUD/database functions

function db_init($dbconf)
{
    extract($dbconf);
    $dsn = $type === 'mysql'
        ? 'mysql:host='.$host.';port='.$port.';dbname='.$name
        : 'sqlite:'.$path;
    try {
        $db = new PDO($dsn, $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        die('DB Connection failed: '.$e->getMessage());
    }
}

function create_user($u)
{
  /*
error_log(var_export($u,true));
    $q = cfg('db')->exec("
 INSERT INTO users (id, uid, fname, lname, email, passwd, `updated`, created)
 VALUES ($u[id], '$u[uid]', '$u[fname]', '$u[lname]', '$u[email]', '$u[passwd]', '$u[updated]', '$u[created]')");
error_log(var_export($q,true));
*/

    $q = cfg('db')->prepare("
 INSERT INTO users (id, uid, fname, lname, email, passwd, updated, created)
 VALUES (:id, :uid, :fname, :lname, :email, :passwd, :updated, :created)");

    $q->bindValue(':id', $u['id'], PDO::PARAM_INT);
    $q->bindValue(':uid', $u['uid']);
    $q->bindValue(':fname', $u['fname']);
    $q->bindValue(':lname', $u['lname']);
    $q->bindValue(':email', $u['email']);
    $q->bindValue(':passwd', password_hash($u['passwd'], PASSWORD_DEFAULT));
    $q->bindValue(':updated', date('Y-m-d H:i:s'));
    $q->bindValue(':created', date('Y-m-d H:i:s'));
    if (!$q->execute()) throw new Exception(die($q->errorInfo()));
    $q->closeCursor();
}

function read_user($uid)
{
    return cfg('db')->query("
 SELECT *
   FROM users
  WHERE id = '$uid'
     OR uid = '$uid'
     OR email = '$uid'")->fetch(PDO::FETCH_ASSOC);
}

function update_user() {}
function delete_user() {}
