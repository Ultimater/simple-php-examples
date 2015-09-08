<?php

// 01-Login.php 20140818 (C) 2014 Mark Constable <markc@renta.net> (AGPL-3.0)
// https://github.com/markc/simple-php-examples/blob/master/lib/md/01-Login.md

// index.php
const ROOT = __DIR__;
const DS = DIRECTORY_SEPARATOR;
session_start();

echo new page([
  'in' => [
    'o' => 'pages', // Object
    'm' => 'read',  // Method
    'i' => 0,       // Id
    'g' => 0,       // Group
  ],
  'out' => [
    'body' => '',
    'css' => '',
    'dbg' => '',
    'dtitle' => 'Login',
    'foot' => '<p>Copyright (C) 2014 Mark Constable (AGPL-3.0)</p>',
    'head' => '',
    'js' => '',
    'lhs' => '',
    'meta' => '',
    'msg' => '',
    'nav' => '',
    'navcolor' => 'inverse',
    'navtype' => 'static',
    'ntitle' => 'Login',
    'ptitle' => '',
    'self' => $_SERVER['PHP_SELF'],
  ],
  'ses' => [
    'navcolor' => 'inverse',
    'navtype' => 'static',
    'cnt' => '0',
  ],
  'nav' => [
    'non' => [
      'About' => ['fa fa-info-circle fa-fw', '?o=pages&m=about'],
      'Sign in' => ['fa fa-sign-in fa-fw', '?o=auth&m=signin'],
    ],
    'usr' => [
      'About' => ['fa fa-info-circle fa-fw', '?o=pages&m=about'],
      'Sign out' => ['fa fa-sign-out fa-fw', '?o=auth&m=signout'],
    ],
    'adm' => [
      'About' => ['fa fa-info-circle fa-fw', '?o=pages&m=about'],
      'Dashboard' => ['fa fa-gear fa-fw', '?o=admin&m=dash'], // TODO
      'Sign out' => ['fa fa-sign-out fa-fw', '?o=auth&m=signout'],
    ],
  ],
  'db' => [
    'host' => '127.0.0.1',
    'name' => 'spex', // (S)imple (P)hp (EX)amples
    'pass' => 'changeme', //ROOT.DS.'lib'.DS.'php'.DS.'.htpw.php',
    'path' => ROOT.DS.'lib'.DS.'sql'.DS.'.htsqlite.db',
    'port' => '3306',
    'sock' => '/run/mysqld/mysqld.sock', // or just ''
    'type' => 'mysql',
    'user' => 'root',
  ],
]);

// lib/php/db.php
class db extends \PDO
{
    public function __construct($dbgbl)
    {
        extract($dbgbl);
        $dsn = $type === 'mysql'
      ? 'mysql:'.($sock ? 'unix_socket='.$sock : 'host='.$host.';port='.$port).';dbname='.$name
      : 'sqlite:'.$path;
        $pass = file_exists($pass) ? include $pass : $pass;
        try {
            parent::__construct($dsn, $user, $pass, [
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
      ]);
        } catch (\PDOException $e) {
            die(__FILE__.' '.__LINE__."\n".$e->getMessage());
        }
    }

    public static function bvs($stm, $ary)
    {
        if (is_object($stm) && ($stm instanceof \PDOStatement)) {
            foreach ($ary as $k => $v) {
                if (is_numeric($v)) {
                    $p = \PDO::PARAM_INT;
                } elseif (is_bool($v)) {
                    $p = \PDO::PARAM_BOOL;
                } elseif (is_null($v)) {
                    $p = \PDO::PARAM_NULL;
                } elseif (is_string($v)) {
                    $p = \PDO::PARAM_STR;
                } else {
                    $p = false;
                }
                if ($p !== false) {
                    $stm->bindValue(":$k", $v, $p);
                }
            }
        }
    }

    public static function qry($dbh, $sql, $ary = [], $type = 'all')
    {
        try {
            $stm = $dbh->prepare($sql);
            if ($ary) {
                self::bvs($stm, $ary);
            }
            if ($stm->execute()) {
                if ($type === 'all') {
                    $res = $stm->fetchAll();
                } elseif ($type === 'one') {
                    $res = $stm->fetch();
                } elseif ($type === 'col') {
                    $res = $stm->fetchColumn();
                }
                $stm->closeCursor();

                return $res;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            die(__FILE__.' '.__LINE__."\n".$e->getMessage());
        }
    }

    public static function read($db, $table, $field, $where = '', $wval = '', $type = 'one')
    {
        $w = $where ? "
  WHERE $where = :wval" : '';
        $a = $wval ? ['wval' => $wval] : [];
        $sql = "
 SELECT $field
   FROM `$table`$w";

        return self::qry($db, $sql, $a, $type);
    }

    public static function update($db, $table, $field, $fval, $where, $wval)
    {
        $sql = "
 UPDATE `$table`
    SET $field = :fval
  WHERE $where = :wval";

        try {
            $stm = $db->prepare($sql);
            self::bvs($stm, ['fval' => $fval, 'wval' => $wval]);

            return $stm->execute();
        } catch (\PDOException $e) {
            die(__FILE__.' '.__LINE__."\n".$e->getMessage());
        }
    }
}

// lib/php/auth.php
class auth
{
    public $buf = '';
    private $db = null;
    private $tbl = 'auth';
    private $gbl = [];

    public function __construct(&$gbl)
    {
        $this->gbl = &$gbl;
        $gbl['in'] = page::esc(array_merge($gbl['in'], [
      'uid' => '',
      'webpw' => '',
      'remember' => '',
    ]));
        $this->db = new db($gbl['db']);
    }

    public function signin()
    {
        $u = $this->gbl['in']['uid'];
        $p = $this->gbl['in']['webpw'];
        $c = $this->gbl['in']['remember'];

        if ($u) {
            if ($usr = db::read($this->db, $this->tbl, 'id,acl,uid,webpw,cookie', 'uid', $u, 'one')) {
                if ($usr['acl']) {
                    //          if ($p === $usr['webpw']) { // for testing a clear text password
          if (password_verify($p, $usr['webpw'])) {
              $uniq = md5(uniqid());
              if ($c) {
                  db::update($this->db, $this->tbl, 'cookie', $uniq, 'uid', $u);
                  page::cookie_put('remember', $uniq, 60 * 60 * 24);
                  $tmp = $uniq;
              } else {
                  $tmp = '';
              }
              $_SESSION['usr'] = [$usr['id'], $usr['acl'], $u, $tmp];
              page::msg($usr['uid'].' is now logged in', 'success');
              if ($usr['acl'] == 1) {
                  $_SESSION['adm'] = $usr['id'];
              }
              header('Location: '.$this->gbl['out']['self']);
              exit();
          } else {
              page::msg('Incorrect password');
          }
                } else {
                    page::msg('Account is disabled, contact your System Administrator');
                }
            } else {
                page::msg('Username does not exist');
            }
        }
        $this->body = $this->signin_form($u);
    }

    public static function signout()
    {
        $u = $_SESSION['usr'][2];
        if ($_SESSION['usr'][1] == 1) {
            unset($_SESSION['adm']);
        }
        unset($_SESSION['usr']);
        page::cookie_del('remember');
        page::msg($u.' is now logged out', 'success');
        header('Location: '.$_SERVER['PHP_SELF']);
        exit();
    }

    public function forgotpw()
    {
        $u = $this->gbl['in']['uid'];

        if ($u) {
            if (filter_var($u, FILTER_VALIDATE_EMAIL)) {
                if ($usr = db::read($this->db, $this->tbl, 'id,acl,email', 'uid', $u, 'one')) {
                    if ($usr['acl']) {
                        $newpass = page::genpw();
                        if ($this->forgotpw_mail($u, $usr['email'], $newpass)) {
                            db::update($this->db, $this->tbl, 'webpw', password_hash($newpass, PASSWORD_DEFAULT), 'id', $usr['id']);
                            page::msg('Reset password for '.$u.' so check your mailbox for a new one.', 'success');
                        } else {
                            page::msg('Problem sending message to '.$usr['email'], 'danger');
                        }
                        header('Location: '.$this->gbl['out']['self']);
                        exit();
                    } else {
                        page::msg('Account is disabled, contact your System Administrator');
                    }
                } else {
                    page::msg('User does not exist');
                }
            } else {
                page::msg('You must provide a valid email address');
            }
        }
        $this->body = $this->forgotpw_form($u);
    }

    private function signin_form($uid)
    {
        return page::form('
            <div class="form-group">
              <div class="col-md-12">
                <div class="input-group">
                  <span class="input-group-addon"><span class="fa fa-user fa-fw"></span></span>
                  <input type="text" name="uid" id="uid" class="form-control" placeholder="Email Address" value="'.$uid.'" required autofocus>
                </div>
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-12">
                <div class="input-group">
                  <span class="input-group-addon"><span class="fa fa-key fa-fw"></span></span>
                  <input type="password" name="webpw" id="webpw" class="form-control" placeholder="Password" required>
                </div>
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-12">
                <div class="checkbox">
                  <label>
                    <input type="checkbox" name="remember" id="remember" value="yes"> Remember me
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-6">
                <a class="btn btn-md btn-default btn-block" href="?o=auth&m=forgotpw">Forgot password</a>
              </div>
              <div class="col-md-6">
                <button class="btn btn-md btn-primary btn-block" type="submit">Sign in</button>
              </div>
            </div>', 'Please sign in', '?o=auth&m=signin');
    }

    private function forgotpw_form($uid)
    {
        return page::form('
            <div class="form-group">
              <div class="col-md-12">
                <div class="input-group">
                  <span class="input-group-addon"><span class="fa fa-envelope fa-fw"></span></span>
                  <input type="email" name="uid" id="uid" class="form-control" placeholder="Your Login Email Address" value="'.$uid.'" required autofocus>
                </div>
              </div>
            </div>
            <div class="text-center">
              You will receive an email with further instructions
            </div>
            <br>
            <div class="form-group">
              <div class="col-md-6">
                <a class="btn btn-md btn-default btn-block" href="?o=auth&m=signin">&laquo; Back</a>
              </div>
              <div class="col-md-6">
                <button class="btn btn-md btn-primary btn-block" type="submit">Send</button>
              </div>
            </div>', 'Forgotten Password', '?o=auth&m=forgotpw');
    }

    private function forgotpw_mail($uid, $email, $newpass, $headers = '')
    {
        return mail(
      $email,
      'Password reset from '.$_SERVER['HTTP_HOST'], '
Here is your new password. Please login as soon as possible and change it
to something your can remember with at least ten characters including one
uppercase and lowercase character plus a number.

Login ID: '.$uid.'
Password: '.$newpass.'
LoginURL: https://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?o=auth&m=signin',
      $headers
    );
    }
}

// lib/php/page.php
class page
{
    public function __construct($gbl)
    {
        $this->gbl = &$gbl;
        $gbl['in'] = self::esc($gbl['in']);
        self::ses($gbl['ses'], $gbl['out'], $gbl['db']);

        if (class_exists($gbl['in']['o'])) {
            $o = new $gbl['in']['o']($gbl);
            if (method_exists($o, $gbl['in']['m'])) {
                $o->{$gbl['in']['m']}();
                foreach ($gbl['out'] as $k => $v) {
                    $gbl['out'][$k] = isset($o->$k) ? $o->$k : $v;
                }
            } else {
                self::msg('Error: method does not exist');
            }
        } else {
            self::msg('Error: object does not exist');
        }

        $nav = isset($_SESSION['usr'])
      ? (isset($_SESSION['adm']) ? $gbl['nav']['adm'] : $gbl['nav']['usr'])
      : $gbl['nav']['non'];

        $gbl['out']['nav'] = self::nav($nav);
        $gbl['out']['msg'] = self::msg();
    }

    public function __destruct()
    {
        error_log(__FILE__.' ('.round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']), 4).' secs)');
    }

    public function __toString()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
      ? json_encode($this->gbl['out'])
      : self::layout($this->gbl['out']);
    }

    public static function nav($ary, $type = 'navbar-nav')
    {
        $buf = '';
        foreach ($ary as $k => $v) {
            $s = $v[1] === '?'.$_SERVER['QUERY_STRING'] ? ' class="active"' : '';
            $i = $v[0] ? '<i class="'.$v[0].'"></i>&nbsp;' : '';
            $buf .= '
            <li'.$s.'><a href="'.$v[1].'">'.$i.$k.'</a></li>';
        }

        return '
          <ul class="nav '.$type.'">'.$buf.'
          </ul>';
    }

    public static function msg($msg = '', $lvl = 'danger')
    {
        if ($msg) {
            $_SESSION['msg'] = $msg;
            $_SESSION['lvl'] = $lvl;
        } elseif (isset($_SESSION['msg']) and $_SESSION['msg']) {
            $msg = $_SESSION['msg'];
            unset($_SESSION['msg']);
            $lvl = $_SESSION['lvl'];
            unset($_SESSION['lvl']);

            return '
      <div class="row">
        <div class="col-md-6 col-md-offset-3">
          <div class="alert alert-'.$lvl.' alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            '.$msg.'
          </div>
        </div>
      </div>';
        } else {
            return '';
        }
    }

    public static function esc($ary)
    {
        $safe_ary = [];
        foreach ($ary as $k => $v) {
            $safe_ary[$k] = isset($_REQUEST[$k])
        ? htmlentities(trim($_REQUEST[$k]), ENT_QUOTES, 'UTF-8') : $v;
        }

        return $safe_ary;
    }

    public static function ses($in_ary, &$out_ary, $dbconf)
    {
        foreach ($in_ary as $k => $v) {
            if (isset($_SESSION[$k])) {
                $out_ary[$k] = $_SESSION[$k];
            } else {
                $_SESSION[$k] = $v;
            }
        }

        if (!isset($_SESSION['usr'])) {
            if ($c = self::cookie_get('remember')) {
                $d = new db($dbconf);
                if ($u = db::read($d, 'auth', 'id,acl,uid,cookie', 'cookie', $c, 'one')) {
                    $_SESSION['usr'] = $u;
                }
            }
        }
    }

    public static function cookie_get($name)
    {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : false;
    }

    public static function cookie_put($name, $value, $expiry)
    {
        if (setcookie($name, $value, time() + $expiry, '/')) {
            return true;
        }

        return false;
    }

    public static function cookie_del($name)
    {
        self::cookie_put($name, '', time() - 1);
    }

    public static function chkpw($pw, $pw2)
    {
        if (strlen($pw) > 9) {
            if (preg_match('/[0-9]+/', $pw)) {
                if (preg_match('/[A-Z]+/', $pw)) {
                    if (preg_match('/[a-z]+/', $pw)) {
                        if ($pw === $pw2) {
                            return true;
                        } else {
                            self::msg('Passwords do not match, please try again');
                        }
                    } else {
                        self::msg('Password must contains at least one lower case letter');
                    }
                } else {
                    self::msg('Password must contains at least one captital letter');
                }
            } else {
                self::msg('Password must contains at least one number');
            }
        } else {
            self::msg('Passwords must be at least 10 characters');
        }

        return false;
    }

    public static function genpw()
    {
        return substr(password_hash(time(), PASSWORD_DEFAULT), rand(10, 50), 10);
    }

    public static function form($content, $title = '', $action = '', $width = '4', $offset = '4')
    {
        $title = $title ? '
            <h2 class="ptitle">'.$title.'</h2>' : '';

        return '
      <div class="row">
        <div class="col-md-'.$width.' col-md-offset-'.$offset.'">
          <form class="form-horizontal" role="form" action="'.$action.'" method="post" enctype="multipart/form-data">'.$title.$content.'
          </form>
        </div>
      </div>';
    }

    private static function layout($ary)
    {
        extract($ary);

        if ($navtype == 'fixed') {
            $css .= '
    <style>
body { padding-top: 71px; }
    </style>';
        }

        return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">'.$meta.'
    <title>'.$dtitle.'</title>
    <link href="lib/img/favicon.ico" rel="icon">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
<!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
    <style>
body {
  min-height: 2000px;
}
.jumbotron {
  background-color: #7F7F7F;
  color: #FFFFFF;
  margin-top: -20px;
  padding: 0;
  text-shadow: 0 1px 1px #000000;
}
.ptitle {
  margin-top: 0px;
}
footer {
  font-size: 80%;
  color: #7F7F7F;
  font-family: serif;
  font-style: italic;
  margin-top: 1em;
  text-align: center;
}
    </style>'.$css.'
  </head>
  <body>
    <header class="navbar navbar-'.$navcolor.' navbar-'.$navtype.'-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="'.$self.'"><i class="fa fa-home fa-fw"></i> '.$ntitle.'</a>
        </div>
        <div class="navbar-collapse collapse">'.$nav.'
        </div>
      </div>
    </header>'.$head.'
    <main class="container">'.$msg.$ptitle.$body.'
    </main>
    <footer>
      '.$foot.'
    </footer>'.$dbg.'
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>'.$js.'
  </body>
</html>
';
    }
}

// lib/php/pages.php
class pages
{
    public function __construct(&$gbl)
    {
        $this->gbl = &$gbl;
    }

    public function read()
    {
        $this->head = '
    <div class="jumbotron">
      <div class="container">
        <div class="row">
          <div class="col-md-6 col-md-offset-3">
            <h1>Login</h1>
            <p>
This example extends 00-Bootstrap with simple PDO database and authentication
classes. The auth class only handles logging in and out, with an optional
cookie, and the Forgotten Password link on the sign in form.
            </p>
            <p>
              <a class="btn btn-lg btn-primary" href="?o=pages&m=about" role="button">About this project &raquo;</a>
            </p>
            <br>
          </div>
        </div>
      </div>
    </div>';

        $this->body = '
      <div class="row text-center">
        <div class="col-md-12">
          <p>
This project is sponsored by<br>
<h4><a href="https://renta.net">RentaNet</a></h4>
          </p>
        </div>
      </div>';
    }

    public function about()
    {
        ++$_SESSION['cnt'];

        $this->body = '
      <div class="row">
        <div class="col-md-6 col-md-offset-3">
          <h2 class="ptitle">About</h2>
          <p>
This is a simple set of PHP scripts that use
<a href="http://php.net/PDO">PDO</a> for backend database connectivity
and tested for use with both <a href="http://mariadb.org">MariaDB</a> and
<a href="http://sqlite.org">SQLite</a>. The minimum PHP dependency is PHP
version 5.5 for the inbuilt password hashing functions and short array
syntax (ie; [] instead of array() since 5.4.)
           </p>
           <p>
Most of the simpler scripts are single file and self-contained aside from
<a href="http://getbootstrap.com">Bootstrap</a> and
<a href="http://jquery.com">jQuery</a> pulled from a
<a href="http://bootstrapcdn.com">CDN</a>. Tested on Ubuntu 14.10 (Oct 2014)
with nginx 1.7.6, php5-fpm 5.6.2, mariadb 5.5.39 and sqlite3 3.8.6.
          </p>
          <p>
<a class="btn btn-success" href="?o=pages&m=success">Successful Message Test</a>
<a class="btn btn-danger" href="?o=pages&m=error">Error Message Test</a>
<a class="btn btn-warning" href="?o=pages&m=reset">Reset Counter: '.$_SESSION['cnt'].'</a>
          </p>
          <p>
<a class="btn btn-primary" href="?o=pages&m=navbar&i=1">Static Navbar</a>
<a class="btn btn-primary" href="?o=pages&m=navbar&i=2">Fixed Navbar</a>
<a class="btn btn-primary" href="?o=pages&m=navbar&i=3">Default Navbar</a>
<a class="btn btn-primary" href="?o=pages&m=navbar&i=4">Inverse Navbar</a>
          </p>
        </div>
      </div>';
    }

  // For testing purposes only

  public function success()
  {
      page::msg('This is a <b>Successful</b> test message', 'success');
      $this->about();
  }

    public function error()
    {
        page::msg('This is an <b>Error</b> test message');
        $this->about();
    }

    public function reset()
    {
        $_SESSION['cnt'] = 0;
        $this->about();
    }

    public function navbar()
    {
        switch ($this->gbl['in']['i']) {
      case '1': $_SESSION['navtype'] = $this->navtype = 'static';  break;
      case '2': $_SESSION['navtype'] = $this->navtype = 'fixed';   break;
      case '3': $_SESSION['navcolor'] = $this->navcolor = 'default'; break;
      case '4': $_SESSION['navcolor'] = $this->navcolor = 'inverse'; break;
    }
        $this->about();
    }
}
