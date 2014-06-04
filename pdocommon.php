<?php error_log(__FILE);
// pdocommon.php 20140604 (C) Mark Constable <markc@renta.net> (AGPL-3.0)

session_start();
error_log('GET='.var_export($_GET, true));
error_log('POST='.var_export($_POST, true));
error_log('SESSION='.var_export($_SESSION, true));
//$_SESSION = []; // uncomment to reset the session vars for testing

function page($ary)
{
error_log(__METHOD__);

  extract($ary);
  return <<< EOS
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>$dtitle</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">
    <link href="/lib/css/pdocommon.css" rel="stylesheet">
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
          <a class="navbar-brand" href="$self/"><i class="fa fa-home fa-fw"></i> $ntitle</a>
        </div>
        <div class="navbar-collapse collapse">$nav
        </div>
      </div>
    </header>
    <main class="container">$msg$buf
    </main>
    <footer>$footer
    </footer>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script src="/lib/js/pdocommon.js"></script>
  </body>
</html>
EOS;
}
