<?php
// init.php 20140621 (C) Mark Constable <markc@renta.net> (AGPL-3.0)
// https://github.com/markc/ublog

class init {

  function __construct($cfg)
  {
    foreach($cfg as $k=>$v) $this->$k = $v;
    foreach($this->in as $k=>$v)
      $this->in[$k] = isset($_REQUEST[$k])
        ? htmlspecialchars(trim($_REQUEST[$k])) : $v;

    if (class_exists($this->in['m'])) {
      $m = new $this->in['m']($this->in, $this->db);
      if (method_exists($m, $this->in['a'])) {
        $m->{$this->in['a']}();
        foreach($this->out as $k=>$v)
          $this->out[$k] = isset($m->$k) ? $m->$k : $v;
      } else die('Error: action does not exist');
    } else die('Error: module does not exist');

    $this->out['nav'] = $this->nav();
  }

  public function __toString()
  {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
      ? json_encode($this->out)
      : self::html($this->out);
  }

  private function nav()
  {
    $buf = '';
    foreach($this->nav['lhs'] as $k=>$v) { // TODO: handle nav[rhs] as well
      $s = $v[0] === '?'.$_SERVER['QUERY_STRING'] ? ' class="active"' : '';
      $i = isset($v[2]) ? '<i class="fa fa-'.$v[2].' fa-fw"></i> ' : '';
      $buf .= '
              <li'.$s.'><a href="'.$v[0].'">'.$i.$v[1].'</a></li>';
    }
    return '
            <ul class="nav navbar-nav">'.$buf.'
            </ul>';
  }

  private static function html($ary)
  {
    extract($ary);
    return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">'.$meta.'
    <title>'.$doc.'</title>
    <link rel="shortcut icon" href="lib/img/favicon.ico">
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">'.$css.'
  </head>
  <body>
    <header class="navbar navbar-inverse navbar-static-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="'.$self.'"><i class="fa fa-home fa-fw"></i> '.$title.'</a>
        </div>
        <div class="navbar-collapse collapse">'.$nav.'
        </div>
      </div>
    </header>'.$top.$msg.'
    <main class="container">'.$buf.'
    </main>
    <footer class="text-center">'.$end.'
    </footer>'.$dbg.'
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>'.$js.'
  </body>
</html>
';
  }

  public static function form($title, $action, $content)
  {
    return '
      <div class="row">
        <div class="col-md-6 col-md-offset-3">
          <form class="form" role="form" action="'.$action.'" method="post">
            <h2>'.$title.'</h2>'.$content.'
          </form>
        </div>
      </div>';
  }
}
