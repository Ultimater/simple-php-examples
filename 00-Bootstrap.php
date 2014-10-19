<?php
// 00-Bootstrap.php 20140818 (C) Mark Constable <markc@renta.net> (AGPL-3.0)
// https://github.com/markc/simple-php-examples/lib/md/00-Bootstrap.md

// index.php
const ROOT = __DIR__;
const DS = DIRECTORY_SEPARATOR;
session_start();

echo new init([
  'in' => [
    'm'         => 'pages', // (M)odule (class)
    'a'         => 'read',  // (A)action (method)
  ],
  'out' => [
    'buf'       => '',
    'css'       => '',
    'dbg'       => '',
    'dtitle'    => 'Bootstrap',
    'end'       => '<br><p class="text-center"><em><small>Copyright (C) 2014 Mark Constable</small></em><p>',
    'js'        => '',
    'menu'      => '',
    'meta'      => '',
    'msg'       => '',
    'nav'       => '',
    'ntitle'    => 'Bootstrap',
    'ptitle'    => '',
    'self'      => $_SERVER['PHP_SELF'],
    'top'       => '',
  ],
  'nav' => [
    'non' => [
      'About' => ['fa fa-info-circle fa-fw', '?m=pages&a=about'],
      'Contact' => ['fa fa-envelope fa-fw', '?m=pages&a=contact'],
    ],
  ],
]);

// lib/php/init.php
class init {

  public function __construct($cfg)
  {
    $this->cfg = &$cfg;

    foreach($cfg['in'] as $k=>$v)
      $this->cfg['in'][$k] = isset($_REQUEST[$k])
        ? htmlentities(trim($_REQUEST[$k]), ENT_QUOTES, 'UTF-8') : $v;

    if (class_exists($this->cfg['in']['m'])) {
      $m = new $this->cfg['in']['m']($this->cfg);
      if (method_exists($m, $this->cfg['in']['a'])) {
        $m->{$this->cfg['in']['a']}();
        foreach($this->cfg['out'] as $k=>$v)
          $this->cfg['out'][$k] = isset($m->$k) ? $m->$k : $v;
      } else self::msg('Error: action does not exist');
    } else self::msg('Error: module does not exist');

    $cfg['out']['nav'] = self::nav($cfg['nav']['non']);
    $cfg['out']['msg'] = self::msg();
  }

  public function __destruct()
  {
    error_log(__FILE__.' ('.round((microtime(true)-$_SERVER['REQUEST_TIME_FLOAT']), 4).' secs)');
  }

  public function __toString()
  {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
      ? json_encode($this->cfg['out'])
      : self::page($this->cfg['out']);
  }

  public static function nav($ary, $type='navbar-nav')
  {
    $buf = '';
    foreach($ary as $k => $v) {
      $s = $v[1] === '?'.$_SERVER['QUERY_STRING'] ? ' class="active"' : '';
      $i = $v[0] ? '<i class="'.$v[0].'"></i>&nbsp;' : '';
      $buf .= '
            <li'.$s.'><a href="'.$v[1].'">'.$i.$k.'</a></li>';
    }
    return '
          <ul class="nav '.$type.'">'.$buf.'
          </ul>';
  }

  public static function msg($msg='', $lvl='danger')
  {
    if ($msg) {
      $_SESSION['msg'] = $msg;
      $_SESSION['lvl'] = $lvl;
    } else if (isset($_SESSION['msg']) and $_SESSION['msg']) {
      $msg = $_SESSION['msg']; unset($_SESSION['msg']);
      $lvl = $_SESSION['lvl']; unset($_SESSION['lvl']);
      return '
      <div class="row">
        <div class="col-md-6 col-md-offset-3">
          <div class="alert alert-'.$lvl.' alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            '.$msg.'
          </div>
        </div>
      </div>';
    } else return '';
  }

  private static function page($ary)
  {
    extract($ary);
    return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">'.$meta.'
    <title>'.$dtitle.'</title>
    <link href="lib/img/favicon.ico" rel="icon">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">'.$css.'
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
    </style>
  </head>
  <body>
    <header class="navbar navbar-inverse navbar-static-top" role="navigation">
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
    </header>'.$top.'
    <main class="container">'.$msg.$ptitle.$buf.'
    </main>
    <footer>
      '.$end.'
    </footer>'.$dbg.'
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>'.$js.'
  </body>
</html>
';
  }
}

// lib/php/pages.php
class pages {

  public $buf = '';
  private $cfg = [];

  public function __construct(&$cfg)
  {
    $this->cfg = &$cfg;
  }

  function read()
  {
    $this->top = '
    <div class="jumbotron">
      <div class="container">
        <div class="row">
          <div class="col-md-6 col-md-offset-3">
            <h1>Bootstrap</h1>
            <p>
This example is a quick exercise to illustrate how the default, static
and fixed to top navbar work. It includes the responsive CSS and HTML,
so it also adapts to your viewport and device.
            </p>
            <p>
              <a class="btn btn-lg btn-primary" href="?m=pages&a=about" role="button">About this project &raquo;</a>
            </p>
            <br>
          </div>
        </div>
      </div>
    </div>';

    $this->buf = '
      <div class="row text-center">
        <div class="col-md-12">
          <p>
This project is sponsored by<br>
<h4><a href="https://renta.net">RentaNet</a></h4>
          </p>
        </div>
      </div>';
  }

  function about()
  {
    $this->buf = '
      <div class="row">
        <div class="col-md-6 col-md-offset-3">
          <h2 class="ptitle">About</h2>
          <p>
This is a simple set of PHP scripts that use
<a href="http://php.net/PDO">PDO</a> for backend database connectivity
and tested for use with both <a href="http://mariadb.org">MariaDB</a>
and <a href="http://sqlite.org">SQLite</a>. There are minimal PHP
dependencies aside from PHP version 5.5, for the inbuilt password hashing
functions, and short array syntax (ie; [] instead of array()) since 5.4.
Most of the scripts are single file and self-contained aside from
<a href="http://getbootstrap.com">Bootstrap</a> and
<a href="http://jquery.com">jQuery</a> pulled from a
<a href="http://bootstrapcdn.com">CDN</a>.
          </p>
          <p>
<a class="btn btn-primary" href="?m=pages&a=success">Successful Message Test</a>
<a class="btn btn-danger" href="?m=pages&a=error">Error Message Test</a>
          </p>
        </div>
      </div>';
  }

  function contact()
  {
    $this->buf = '
      <div class="row">
        <div class="col-md-6 col-md-offset-3">
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

  function success()
  {
    init::msg('This is a <b>Successful</b> message', 'success');
    $this->about();
  }

  function error()
  {
    init::msg('This is an <b>Error</b> message');
    $this->about();
  }
}
