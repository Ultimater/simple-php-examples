<?php error_log("\n\n!!! START -> ".__FILE__."\n");
// pdoforum.php 20140604 (C) Mark Constable <markc@renta.net> (AGPL-3.0)

const ROOT = __DIR__;

error_log('REQUEST = '.var_export($_REQUEST,true));
session_start();
//error_log('SESSION = '.var_export($_SESSION,true));
//$_SESSION = []; // uncomment to reset the session vars for testing

$config = [
  'db' => [
    'host' => 'localhost',
    'name' => 'admin',
    'pass' => ROOT.'/.ht_pw.php',
    'path' => ROOT.'/.ht_forum.db',
    'port' => '3306',
    'sock' => '/run/mysqld/mysqld.sock',
    'type' => 'mysql',
    'user' => 'admin',
  ],
  'in' => [
    'm' => 'forum',      // (M)odule (class)
    'a' => 'index',      // (A)action (method)
    'i' => 0,           // (I)tem ID
  ],
  'acl' => [
    ['0', 'Disabled'],
    ['1', 'User'],
    ['127', 'Admin']
  ],
];

echo view(new forum($config));

class forum {

  private $buf = '';    // outgoing string buffer

  function __construct($config)
  {
error_log(__METHOD__);

    foreach($config['in'] as $k=>$v)
      $this->$k = isset($_REQUEST[$k])
        ? strip_tags(trim($_REQUEST[$k]))
        : (isset($_SESSION['forum'][$k]) ? $_SESSION['forum'][$k] : $v);

    $this->dbh = pdo($config['db']);

    foreach($config['in'] as $k=>$v)
      $_SESSION['forum'][$k] = isset($_REQUEST[$k]) ? $this->$k :
        (isset($_SESSION['forum'][$k]) ? $_SESSION['forum'][$k] : $v);

    $this->buf = $this->{$this->a}();
  }

  function post_reply()
  {
error_log(__METHOD__);

    $sql = "
 INSERT INTO posts(post_content, post_date, post_topic, post_by)
 VALUES (:content, :date, :topic, :userid)";

    $c = $t = $i = '';
    if (!empty($_POST)) {
      if (acl(1)) {
        $c = filter_var(trim($_POST['content']), FILTER_SANITIZE_STRING);
        $t = filter_var(trim($_POST['topic']), FILTER_SANITIZE_STRING);
        $i = filter_var(trim($_POST['userid']), FILTER_VALIDATE_INT);
        if ($c) {
          if ($t) {
error_log("YAY!");
          } else msg('The topic field must not be empty');
        } else msg('The content field must not be empty');
      } else msg('You must sign in to add topics');
    }
    return frm('Post a reply', '?a=post_reply', frm_reply([
      'c' => $c,
      't' => $t,
      'd' => date('Y-m-d H:i:s'),
      'i' => $_SESSION['user']['id'],
    ]));
  }

  function create_topic()
  {
error_log(__METHOD__);

    $sql = "
 INSERT INTO topics (topic_subject, topic_date, topic_cat, topic_by)
 VALUES (:subject, :date, :cat, :userid)";

    $s = $c = $i = '';
    if (!empty($_POST)) {
      if (acl(1)) {
        $s = filter_var(trim($_POST['subject']), FILTER_SANITIZE_STRING);
        $c = filter_var(trim($_POST['cat']), FILTER_SANITIZE_STRING);
        $i = filter_var(trim($_POST['userid']), FILTER_VALIDATE_INT);
        if ($s) {
          if ($c) {
error_log("YAY!");
          } else msg('The category name field must not be empty');
        } else msg('The subject field must not be empty');
      } else msg('You must sign in to add topics');
    }
    return frm('Create a topic', '?a=create_topic', frm_topic([
      's' => $s,
      'c' => $c,
      'd' => date('Y-m-d H:i:s'),
      'i' => $_SESSION['user']['id'],
    ]));
  }

  function create_cat()
  {
error_log(__METHOD__);

    $sql = "
 INSERT INTO categories (cat_name, cat_description)
 VALUES (:name, :desc)";

    $n = $d = '';
    if (!empty($_POST)) {
      if (acl(1)) {
        $n = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
        $d = filter_var(trim($_POST['desc']), FILTER_SANITIZE_STRING);
        if ($n) {
          if ($d) {
error_log("YAY!");
          } else msg('The category name field must not be empty');
        } else msg('The description field must not be empty');
      } else msg('You must sign in to add categories');
    }
    return frm('Create a category', '?a=create_cat', frm_cat(['n'=>$n, 'd'=>$d]));
  }

  function signout()
  {
error_log(__METHOD__);

    if (acl()) {
      $uid = $_SESSION['uid'];
      unset($_SESSION['user']);
      msg($uid.' has succesfully signed out, thank you for visiting.');
      header('Location: ?a=index');
      exit();
    } else {
      msg('You are not signed in.');
      header('Location: ?a=signin');
      exit();
    }
  }

  function signin()
  {
error_log(__METHOD__);

    $u = '';
    if (!empty($_POST)) {
      if (!acl(1)) {
        $u = filter_var(trim($_POST['uid']), FILTER_SANITIZE_STRING);
        $p = filter_var(trim($_POST['passwd']), FILTER_SANITIZE_STRING);
        if ($u) {
          if ($p) {
            if ($user = $this->read_user($u)) {
              if (password_verify($p, $user['passwd'])) {
                $_SESSION['user'] = $user;
                msg($user['uid'].' is now signed in');
                header('Location: ?a=index');
                exit();
              } else msg('Password does not match');
            } else msg('Username does not exist');
          } else msg('The password field must not be empty');
        } else msg('The username field must not be empty');
      } else msg('You are already signed in, you can <a href="?a=signout">sign out</a> if you want');
    }
    return frm('Please sign in', '?a=signin', frm_signin(['u' => $u]));
  }

  function signup()
  {
error_log(__METHOD__);

    $u = $e = $fn = $ln = $p1 = $p2 = '';

    if (!empty($_POST)) {
      $u = filter_var(trim($_POST['uid']), FILTER_SANITIZE_STRING);
      $e = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
      $fn = filter_var(trim($_POST['fname']), FILTER_SANITIZE_STRING);
      $ln = filter_var(trim($_POST['lname']), FILTER_SANITIZE_STRING);
      $p1 = filter_var(trim($_POST['passwd']), FILTER_SANITIZE_STRING);
      $p2 = filter_var(trim($_POST['passwd2']), FILTER_SANITIZE_STRING);
      if ($u) {
        if ($e) {
          $user = $this->read_user($u);
          if ($u !== $user['uid']) {
            if ($e !== $user['email']) {
              if (strlen($p1) > 9) {
                if (preg_match('/[0-9]+/', $p1)) {
                  if (preg_match('/[A-Z]+/', $p1)) {
                    if (preg_match('/[a-z]+/', $p1)) {
                      if ($p1 === $p2) {
                        if ($this->create_user([
                          'id' => 'NULL',
                          'acl' => 0,
                          'uid' => $u,
                          'fname' => $fn,
                          'lname' => $ln,
                          'email' => $e,
                          'pwkey' => substr(sha1(time()), rand(0, 31), 8),
                          'passwd' => password_hash($p1, PASSWORD_DEFAULT),
                          'updated' => date('Y-m-d H:i:s'),
                          'created' => date('Y-m-d H:i:s')])) {
                          msg('Added new user: '.$u);
                          header("Location: ".$_SERVER['PHP_SELF']);
                          exit();
                        } else msg('Problem adding user details, please contact the administrator', 'danger');
                      } else msg('Passwords do not match, please try again');
                    } else msg('Password must contains at least one lower case letter');
                  } else msg('Password must contains at least one captital letter');
                } else msg('Password must contains at least one number');
              } else msg('Passwords must be at least 10 characters');
            } else msg('Email address already exists, please user a different one');
          } else msg('Username already exists, please user a different one');
        } else msg('Please provide a valid email address');
      } else msg('Please provide a valid username using only alphanumeric characters');
    }
    return frm('Sign up form', '?a=signup', frm_signup([
      'u'   => $u,
      'e'   => $e,
      'fn'  => $fn,
      'ln'  => $ln,
      'p1'  => $p1,
      'p2'  => $p2,
    ]));
  }

  function create_user($u)
  {
error_log(__METHOD__);

    $sql = "
 INSERT INTO `users` (id, acl, uid, fname, lname, email, pwkey, passwd, updated, created)
 VALUES (:id, :acl, :uid, :fname, :lname, :email, :pwkey, :passwd, :updated, :created)";

    try {
      $stm = $this->dbh->prepare($sql);
      bvs($stm, $u);
      $stm->execute();
      return $stm !== false ? $this->dbh->lastInsertId() : false;
    } catch(PDOException $e) {
      msg($e->getMessage(), 'danger');
    }
  }

  function read_user($uid)
  {
error_log(__METHOD__."($uid)");

    try {
      return $this->dbh->query("
 SELECT *
   FROM `users`
  WHERE id = '$uid'
     OR uid = '$uid'
     OR email = '$uid'")->fetch(\PDO::FETCH_ASSOC);
    } catch(\PDOException $err) {
      msg($err->getMessage(), 'danger');
    }
  }

  function index()
  {
error_log(__METHOD__);

    $buf1 = $buf2 = '';
    $sql1 = "
 SELECT categories.id,
        categories.cat_name,
        categories.cat_description,
        COUNT(topics.id) AS topics
   FROM categories
   LEFT JOIN topics
     ON topics.id = categories.id
  GROUP BY categories.cat_name,
        categories.cat_description,
        categories.id";

    $sql2 = "
 SELECT id, topic_subject, topic_date, topic_cat
   FROM topics
  WHERE topic_cat = :id
  ORDER BY topic_date DESC
  LIMIT 1";

    if (($res1 = qry($this->dbh, $sql1)) !== false) {
      if (!empty($res1)) {
        foreach($res1 as $r1) {
          if (($res2 = qry($this->dbh, $sql2, ['id' => $r1['id']])) !== false) {
            $buf2 = empty($res2) ? 'no topics' : '
                        <a href="?a=topic&i='.$res2[0]['id'].'">'.$res2[0]['topic_subject'].'</a>
                        at '.date('d-m-Y', strtotime($res2[0]['topic_date']));
          } else msg('Last topic for '.$r1['cat_name'].' could not be displayed');

          $buf1 .= tbl_row([
            'a' => 'category',
            'i' => $r1['id'],
            'n' => $r1['cat_name'],
            'd' => ' - '.$r1['cat_description'],
            't' => $buf2
          ]);
          $buf2 = '';
        }
      } else $buf1 .= '<tr><td width="75%">No categories defined yet</td><td></td></tr>';
    } else msg('The categories could not be displayed, please try again later');

    return tbl([
      'phead' => 'Forum Index',
      'thead' => tbl_thd(['Category', 'Last topic']),
      'tbody' => $buf1,
      'pfoot' => '',
    ]);
  }

  function topic()
  {
error_log(__METHOD__);

    $buf = $phead = $thead = $tbody = $pfoot = '';
    $sql = "
 SELECT id, topic_subject
   FROM topics
  WHERE topics.id = :tid";

    try {
      $stm = $this->dbh->prepare($sql);
      $stm->execute([':tid' => $this->i]);
      $result = $stm->fetchAll();
    } catch(PDOException $e) {
      msg($e->getMessage(), 'danger');
    }

    if ($result === false) {
       msg('The topic could not be displayed, please try again later.');
    } else {
      if (empty($result)) {
        msg('This topic doesn\'t exist.');
      } else {
        $postssql = "
 SELECT posts.post_topic,
        posts.post_content,
        posts.post_date,
        posts.post_by,
        users.id,
        users.uid
   FROM posts
   LEFT JOIN users
     ON posts.post_by = users.id
  WHERE posts.post_topic = :pid";

        try {
          $stm = $this->dbh->prepare($postssql);
          $stm->execute([':pid' => $result[0]['id']]);
          $postsresult = $stm->fetchAll();
        } catch(PDOException $e) {
          msg($e->getMessage(), 'danger');
        }

        if ($postsresult === false) {
          msg('The posts could not be displayed, please try again later');
        } else {
          foreach($postsresult as $postsrow) $tbody .= '
              <tr class="topic-post">
                <td class="user-post">'.$postsrow['uid'].'<br/>'.date('d-m-Y H:i', strtotime($postsrow['post_date'])).'</td>
                <td class="post-content">'.htmlentities(stripslashes($postsrow['post_content'])).'</td>
              </tr>';
        }
/*
            <tr>
              <td colspan="2">
                <h2>Reply:</h2>
                <form method="post" action="?a=reply&i='.$result[0]['id'].'">
                  <textarea name="reply-content"></textarea>
                  <br>
                  <br>
                  <input type="submit" value="Submit reply">
                </form>
              </td>
            </tr>
*/
        $pfoot = acl(1)
          ? frm('Reply','?a=post_reply',frm_reply(['c'=>'', 't'=>$result[0]['topic_subject'], 'i'=>$_SESSION['user']['id']]))
          : '
            <tr>
              <td colspan=2>
                You must be <a href="?a=signin">signed in</a> to reply.
                You can also <a href="?a=signup">sign up</a> for an account.
              </td>
            </tr>';

        $buf = tbl([
          'phead' => $result[0]['topic_subject'],
          'thead' => '',
          'tbody' => $tbody,
          'pfoot' => $pfoot,
        ]);
      }
    }
    return $buf;
  }

  function category()
  {
error_log(__METHOD__);

    $buf = $phead = $thead = $tbody = $pfoot = '';
    $cat = 'Categories';
    $sql = "
 SELECT cat_name
   FROM categories
  WHERE id = :id";

    try {
      $stm = $this->dbh->prepare($sql);
      $stm->execute([':id' => $this->i]);
      $result = $stm->fetchAll();
    } catch(PDOException $e) {
      msg($e->getMessage(), 'danger');
    }

    if ($result === false) {
      msg('The category could not be displayed, please try again later');
    } else {
      if (empty($result)) {
        $buf .=  'This category does not exist';
      } else {
        $cat = $result[0]['cat_name'];
        $sql = "
 SELECT id, topic_subject, topic_date, topic_cat
   FROM topics
  WHERE topic_cat = :id";

        try {
          $stm = $this->dbh->prepare($sql);
          $stm->execute([':id' => $this->i]);
          $topicsresult = $stm->fetchAll();
        } catch(PDOException $e) {
          msg($e->getMessage(), 'danger');
        }
        if ($topicsresult === false) {
          msg('The topics could not be displayed, please try again later');
        } else {
          if (empty($topicsresult)) {
            $tbody .= 'There are no topics in this category yet';
          } else {
            foreach($topicsresult as $topicrow) $tbody .= '
            <tr>
              <td width="75%">
                <a href="?a=topic&i='.$topicrow['id'].'"><b>'.$topicrow['topic_subject'].'</b></a>
              </td>
              <td>'.date('Y-m-d', strtotime($topicrow['topic_date'])).'
              </td>
            </tr>';
          }
        }
      }
    }
    return tbl([
      'phead' => $cat,
      'thead' => tbl_thd(['Topic', 'Created at']),
      'tbody' => $tbody,
      'pfoot' => $pfoot,
    ]);
  }

  function __toString()
  {
error_log(__METHOD__);

    return $this->buf;
  }

} // end forum class


// anon, pending, user, admin
function acl($acl=1)
{
error_log(__METHOD__."($acl)");

  $tmp = (isset($_SESSION['user']['acl']) && $_SESSION['user']['acl'] === $acl);
//error_log('acl tmp = '.var_export($tmp, true));
  return $tmp;
//  return isset($_SESSION['user']['acl']) and $_SESSION['user']['acl'] == $acl;
}

function pdo($dbconf)
{
error_log(__METHOD__);

  extract($dbconf);
  $dsn = $type === 'mysql'
    ? 'mysql:'.($sock ? 'unix_socket='.$sock : 'host='.$host.';port='.$port).';dbname='.$name
    : 'sqlite:'.$path;
    $pass = file_exists($pass) ? include $pass : $pass;
  try {
    return new \PDO($dsn, $user, $pass, [
      \PDO::ATTR_EMULATE_PREPARES => false,
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
  } catch(\PDOException $err) {
    msg($err->getMessage(), 'danger');
  }
}

function qry($dbh, $sql, $ary=[])
{
error_log(__METHOD__);

  try {
    $stm = $dbh->prepare($sql);
    if (!empty($ary)) bvs($stm, $ary);
    $stm->execute();
    return $stm->fetchAll();
  } catch(\PDOException $err) {
    msg($err->getMessage(), 'danger');
  }
}

function bvs($stm, $ary)
{
error_log(__METHOD__);

  if (is_object($stm) && ($stm instanceof \PDOStatement)) {
    foreach($ary as $k => $v) {
      if (is_int($v))       $p = \PDO::PARAM_INT;
      elseif(is_bool($v))   $p = \PDO::PARAM_BOOL;
      elseif(is_null($v))   $p = \PDO::PARAM_NULL;
      elseif(is_string($v)) $p = \PDO::PARAM_STR;
      else $p = false;
      if ($p) $stm->bindValue(":$k", $v, $p);
    }
  }
}

function msg($msg=null, $level=null)
{
error_log(__METHOD__."($msg)");

  $_SESSION['lvl'] = $level ? $level : 'success';

  if ($msg) {
    $_SESSION['msg'] = isset($_SESSION['msg']) ? $_SESSION['msg']."<br>\n".$msg : $msg;
  } else if (isset($_SESSION['msg']) and $_SESSION['msg']) {
    $msg = $_SESSION['msg']; unset($_SESSION['msg']);
    $lvl = $_SESSION['lvl']; unset($_SESSION['lvl']);
    return '
      <div class="row">
        <div class="col-md-12">
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

function nav()
{
error_log(__METHOD__);

  $buf = acl() ? '
            <li><a href="?a=signout">Sign out</a></li>' : '
            <li><a href="?a=signup">Sign up</a></li>
            <li><a href="?a=signin">Sign in</a></li>';
  return '
          <ul class="nav navbar-nav">
            <li><a href="?a=create_topic">Create a topic</a></li>
            <li><a href="?a=create_cat">Create a category</a></li>'.$buf.'
          </ul>';
}

function tbl($ary)
{
error_log(__METHOD__);

  extract($ary);
  return '
      <div class="row">
        <form class="form" role="form">
          <div class="col-md-12">
            <div class="panel panel-primary">
              <div class="panel-heading">'.$phead.'
              </div>
              <div class="table-responsive">
                <table class="table table-condensed">
                  <thead>'.$thead.'
                  </thead>
                  <tbody>'.$tbody.'
                  </tbody>
                </table>
              </div>
              <div class="panel-footer">'.$pfoot.'
              </div>
            </div>
          </div>
        </form>
      </div>';
}

function tbl_thd($ary)
{
error_log(__METHOD__);

  $buf = '';
  foreach($ary as $th) $buf .= '
                      <th>'.$th.'</th>';
  return '
                    <tr>'.$buf.'
                    </tr>';
}

function tbl_row($ary)
{
error_log(__METHOD__);

  extract($ary);
  return '
            <tr>
              <td width="75%">
                <a href="?a='.$a.'&i='.$i.'"><b>'.$n.'</b></a>
                <small>'.$d.'</small>
              </td>
              <td>'.$t.'
              </td>
            </tr>';
}

function frm($title='', $action='', $content='')
{
error_log(__METHOD__);

  return '
      <div class="row">
        <div class="col-md-4 col-md-offset-4">
          <form class="form" role="form" action="'.$action.'" method="post">
            <h2>'.$title.'</h2>'.$content.'
          </form>
        </div>
      </div>';
}

function frm_signin($ary)
{
error_log(__METHOD__);

  extract($ary);
  return '
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-user fa-fw"></span></span>
              <input type="text" name="uid" id="uid" class="form-control" placeholder="Login ID" value="'.$u.'" required autofocus>
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-key fa-fw"></span></span>
              <input type="password" name="passwd" id="passwd" class="form-control" placeholder="Password" required>
            </div>
            <br>
            <button class="btn btn-md btn-primary btn-block" type="submit">Sign in</button>';
}

function frm_signup($ary)
{
error_log(__METHOD__);

  extract($ary);
  return '
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-user fa-fw"></span></span>
              <input type="text" name="uid" id="uid" class="form-control" placeholder="Login ID" value="'.$u.'" required autofocus>
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-gear fa-fw"></span></span>
              <input type="text" name="fname" id="fname" class="form-control" placeholder="First Name" value="'.$fn.'">
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-gear fa-fw"></span></span>
              <input type="text" name="lname" id="lname" class="form-control" placeholder="Last Name" value="'.$ln.'" >
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-key fa-fw"></span></span>
              <input type="password" pattern=".+{10,32}" name="passwd" id="passwd" class="form-control" placeholder="Password" value="'.$p1.'" required>
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-key fa-fw"></span></span>
              <input type="password" pattern=".+{10,32}" name="passwd2" id="passwd2" class="form-control" placeholder="Confirm Password" value="'.$p2.'" required>
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-envelope fa-fw"></span></span>
              <input type="email" name="email" id="email" class="form-control" placeholder="Email Address" value="'.$e.'" required>
            </div>
            <br>
            <button class="btn btn-md btn-primary btn-block" type="submit">Sign up</button>';
}

function frm_cat($ary)
{
error_log(__METHOD__);

  extract($ary);
  return '
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-gear fa-fw"></span></span>
              <input type="text" name="name" id="name" class="form-control" placeholder="Category name" value="'.$n.'" required autofocus>
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-gear fa-fw"></span></span>
              <input type="text" name="desc" id="desc" class="form-control" placeholder="Category description" value="'.$d.'" required>
            </div>
            <br>
            <button class="btn btn-md btn-primary btn-block" type="submit">Add category</button>';
}

function frm_topic($ary)
{
error_log(__METHOD__);

  extract($ary);
  return '
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-gear fa-fw"></span></span>
              <input type="text" name="subject" id="subject" class="form-control" placeholder="Subject" value="'.$s.'" required autofocus>
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-gear fa-fw"></span></span>
              <input type="text" name="cat" id="cat" class="form-control" placeholder="Category" value="'.$c.'" required>
            </div>
            <br>
            <input type="hidden" name="userid" id="userid" value="'.$i.'">
            <button class="btn btn-md btn-primary btn-block" type="submit">Add topic</button>';
}

function frm_reply($ary)
{
error_log(__METHOD__);

  extract($ary);
  return '
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-gear fa-fw"></span></span>
              <input type="text" name="subject" id="subject" class="form-control" placeholder="Reply" value="'.$c.'" required autofocus>
            </div>
            <br>
            <div class="input-group">
              <span class="input-group-addon"><span class="fa fa-gear fa-fw"></span></span>
              <input type="text" name="cat" id="cat" class="form-control" placeholder="Topic" value="'.$t.'" required>
            </div>
            <br>
            <input type="hidden" name="userid" id="userid" value="'.$i.'">
            <button class="btn btn-md btn-primary btn-block" type="submit">Post reply</button>';
}

function view($buf)
{
error_log(__METHOD__);

  return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PDOForum</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">
    <style>
body { min-height: 1000px }
.form h2 { margin-top: 0px; }
    </style>
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
          <a class="navbar-brand" href="?a=index"><i class="fa fa-home fa-fw"></i> PDOForum</a>
        </div>
        <div class="navbar-collapse collapse">'.nav().'
        </div>
      </div>
    </header>
    <main class="container">'.msg().$buf.'
    </main>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
  </body>
</html>
';
}
