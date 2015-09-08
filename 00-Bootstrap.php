<?php

// 00-Bootstrap.php 20140818 (C) 2014 Mark Constable <markc@renta.net> (AGPL-3.0)
// https://github.com/markc/simple-php-examples/blob/master/lib/md/00-Bootstrap.md

// index.php
const ROOT = __DIR__;
const DS = DIRECTORY_SEPARATOR;
session_start();

echo new page([
    'in' => [
        'o' => 'pages', // Object
        'm' => 'read',    // Method
        'i' => 0,             // Id
        'g' => 0,             // Group
    ],
    'out' => [
        'body' => '',
        'css' => '',
        'dbg' => '',
        'dtitle' => 'Bootstrap',
        'foot' => '<p>Copyright (C) 2014 Mark Constable (AGPL-3.0)</p>',
        'head' => '',
        'js' => '',
        'lhs' => '',
        'meta' => '',
        'msg' => '',
        'nav' => '',
        'navcolor' => 'inverse',
        'navtype' => 'static',
        'ntitle' => 'Bootstrap',
        'ptitle' => '',
        'self' => $_SERVER['PHP_SELF'],
    ],
    'ses' => [
        'navcolor' => 'inverse',
        'navtype' => 'static',
    ],
    'nav' => [
        'non' => [
            'About' => ['fa fa-info-circle fa-fw', '?o=pages&amp;m=about'],
            'Contact' => ['fa fa-envelope fa-fw', '?o=pages&amp;m=contact'],
        ],
    ],
]);

// lib/php/page.php
class page
{
    public function __construct($gbl)
    {
        $this->gbl = &$gbl;
        $gbl['in'] = self::esc($gbl['in']);
        self::ses($gbl['ses'], $gbl['out']);

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

        $gbl['out']['nav'] = self::nav($gbl['nav']['non']);
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
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>'.$msg.'
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

    public static function ses($in_ary, &$out_ary)
    {
        foreach ($in_ary as $k => $v) {
            if (isset($_SESSION[$k])) {
                $out_ary[$k] = $_SESSION[$k];
            } else {
                $_SESSION[$k] = $v;
            }
        }
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
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
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
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>'.$js.'
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
          <div class="col-md-8 col-md-offset-2">
            <h1>Bootstrap</h1>
            <p>
This example is a quick exercise to illustrate how the default, static
and fixed to top navbar work. It includes the responsive CSS and HTML,
so it also adapts to your viewport and device.
            </p>
            <p>
              <a class="btn btn-lg btn-primary" href="?o=pages&amp;m=about" role="button">About this project &raquo;</a>
            </p>
            <br>
          </div>
        </div>
      </div>
    </div>';

        $this->body = '
      <div class="row text-center">
        <div class="col-md-12">
          <p>This project is sponsored by</p>
          <h4><a href="https://renta.net">RentaNet</a></h4>
        </div>
      </div>';
    }

    public function about()
    {
        ++$_SESSION['cnt'];

        $this->body = '
      <div class="row">
        <div class="col-md-8 col-md-offset-2">
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
<a href="http://bootstrapcdn.com">CDN</a>. Tested on Ubuntu 15.10 (Oct 2014)
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

    public function contact()
    {
        $this->body = '
      <div class="row">
        <div class="col-md-8 col-md-offset-2">
          <h2 class="ptitle">Contact</h2>
          <form id="contact-send" class="form-horizontal" role="form" method="post" onsubmit="return mailform(this);">
            <div class="form-group">
              <label for="subject" class="col-sm-2 control-label">Subject</label>
              <div class="col-sm-10">
                <input id="subject" class="form-control" placeholder="Your Subject" required="" type="text">
              </div>
            </div>
            <div class="form-group">
              <label for="message" class="col-sm-2 control-label">Message</label>
              <div class="col-sm-10">
                <textarea id="message" class="form-control" rows="9" placeholder="Your Message" required=""></textarea>
              </div>
            </div>
            <div class="form-group">
              <div class="col-sm-12">
                <input type="submit" id="send" class="btn btn-primary pull-right" value="Send">
              </div>
            </div>
          </form>
        </div>
      </div>
      <script>
function mailform(form) {
  location.href = "mailto:markc@renta.net"
    + "?subject=" + encodeURIComponent(form.subject.value)
    + "&body=" + encodeURIComponent(form.message.value);
  form.subject.value = "";
  form.message.value = "";
  alert("Thank you for your message. We will get back to you as soon as possible.");
  return false;
}
      </script>';
    }

    public function success()
    {
        page::msg('This is a <b>Successful</b> message', 'success');
        $this->about();
    }

    public function error()
    {
        page::msg('This is an <b>Error</b> message');
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
