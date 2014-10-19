<!DOCTYPE html>
<html lang="en">
  <head>
    <title><?=$t['htitle']?></title>
    <meta charset="utf8">
    <link rel="stylesheet" href="/index.css">
  </head>
  <body>
    <h1><?=$t['btitle']?></h1>
    <div>
This is a simple note system, you can
<a href="<?=$t['createlink']?>" title="Create">create</a> a new note or
<a href="<?=$t['readlink']?>" title="Read">read</a> them at your leisure.
    </div>
    <br>
    <div id="content"><?=$t['content']?></div>
  </body>
</html>
