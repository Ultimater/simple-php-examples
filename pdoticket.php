<?php error_log("\n\n!!! START -> ".__FILE__."\n");
// pdoticket.php 20140604 (C) Mark Constable <markc@renta.net> (AGPL-3.0)

const ROOT = __DIR__;

session_start();
error_log('GET='.var_export($_GET, true));
error_log('POST='.var_export($_POST, true));
error_log('SESSION='.var_export($_SESSION, true));
//$_SESSION = []; // uncomment to reset the session vars for testing

echo page(init(cfg(array(
  'title'    => 'My Project',
  'email'    => 'noreply@tit.lan',
  'issue'    => array(),
  'issues'   => array(),
  'comments' => array(),
  'status'   => 0,
  'mode'     => 'list',
  'db'       => null,
  'dbconf' => array(
    'host' => 'localhost',
    'name' => 'admin',
    'pass' => ROOT.'/.ht_pw.php',
    'path' => ROOT.'/.ht_ticket.db',
    'port' => '3306',
    'sock' => '/run/mysqld/mysqld.sock',
    'type' => 'sqlite',
    'user' => 'admin'
  ),
  'users' => array(
    'admin' => array(
      'username' => 'admin',
      'password' => md5('changeme'),
      'email'    => 'admin@tit.lan',
      'admin'    => true
    ),
    'markc' => array(
      'username' => 'user1',
      'password' => md5('changeme'),
      'email'    => 'user1@tit.lan'
    )
  ),
  'notify' => array(
    'issue_create'   => true,
    'issue_edit'     => true,
    'issue_delete'   => true,
    'issue_status'   => true,
    'issue_priority' => true,
    'comment_create' => true
  ),
  'priority_ary' => array(
    1 => 'High',
    2 => 'Medium',
    3 => 'Low'
  ),
  'statuses' => array(
    0 => 'Active',
    1 => 'Resolved'
  ),
))));

function init($cfg)
{
error_log(__METHOD__);

  if (isset($_GET['logout'])) {
    logout();
  } elseif (isset($_POST['login'])) {
    login();
  } elseif (!isset($_SESSION['tit']['username'])) {
    echo login_form();
    exit;
  }

  cfg('db', db_init($cfg['dbconf']));
  if (isset($_GET["install"])) {
    install($cfg['dbconf']['type']);
  }
  get_issue();

  if (isset($_POST["createissue"])) {
    createissue();
  } else if (isset($_GET["deleteissue"])) {
    deleteissue();
  } else if (isset($_POST["createcomment"])) {
    createcomment();
  } elseif (isset($_GET["deletecomment"])){
    deletecomment();
  } elseif (isset($_POST["watch"])) {
    watch();
  } elseif (isset($_POST["unwatch"])) {
    unwatch();
  } elseif (isset($_GET["changepriority"])) {
    changepriority();
  } elseif (isset($_GET["changestatus"])) {
    changestatus();
  }
}

function install($type)
{
error_log(__METHOD__);

  $db = cfg('db');
  $pri = $type == 'mysql' ? 'int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT' : 'INTEGER PRIMARY KEY';

  // uncomment below to reinstall tables while testing
  //$db->exec("DROP TABLE IF EXISTS comments;DROP TABLE IF EXISTS issues;");

  $db->exec("
CREATE TABLE IF NOT EXISTS comments (
      id ".$pri.",
      issue_id INTEGER,
      user TEXT,
      description TEXT,
      entrytime DATETIME);
INSERT INTO comments VALUES
      (NULL, 1, 'admin', 'First comment.', '2013-05-23 01:22:07'),
      (NULL, 1, 'admin', 'Second comment', '2013-05-24 23:00:30');
CREATE TABLE IF NOT EXISTS issues (
      id ".$pri.",
      title TEXT,
      description TEXT,
      user TEXT,
      status INTEGER NOT NULL DEFAULT '0',
      priority INTEGER,
      notify_emails TEXT,
      entrytime DATETIME);
INSERT INTO issues VALUES
      (NULL, 'New Issue 1', 'Just a test 1', 'admin', 0, 1, 'admin@tit.lan', '2013-05-22 00:00:00'),
      (NULL, 'New Issue 2', 'Just a test 2', 'admin', 1, 1, '', '2013-05-22 16:51:37');");
}

// static
function db_init($dbconf)
{
error_log(__METHOD__);

  extract($dbconf);
  $dsn = $type === 'mysql'
    ? 'mysql:'.($sock ? 'unix_socket='.$sock : 'host='.$host.';port='.$port).';dbname='.$name
    : 'sqlite:'.$path;
  $pass = file_exists($pass) ? include $pass : $pass;
  try {
    return new PDO($dsn, $user, $pass);
  } catch (PDOException $e) {
    die('DB Connection failed: '.$e->getMessage());
  }
}

function changestatus()
{
error_log(__METHOD__);

  $db = cfg('db');
  $title = cfg('title');
  $notify = cfg('notify');
  $statuses = cfg('statuses');
  $id = (int)$_GET['id'];
  $status = (int)$_GET['status'];
  $db->exec("
UPDATE issues SET status='$status'
WHERE id = $id");
  if ($notify['issue_status'])
    notify($id, '['.$title.'] Issue Marked as '.$statuses[$status],
      'Issue marked as '.$statuses[$status].' by '.$_SESSION['tit']['username']."\r\n".'Title: '.get_col($id, 'issues', 'title')."\r\n".'URL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?id='.$id);
  header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
}

function changepriority()
{
error_log(__METHOD__);

  $db = cfg('db');
  $title = cfg('title');
  $notify = cfg('notify');
  $id = (int)$_GET['id'];
  $priority =(int)$_GET['priority'];
  if ($priority >= 1 and $priority <= 3)
    $db->exec("
UPDATE issues SET priority='$priority'
WHERE id = $id");
  if ($notify['issue_priority'])
    notify($id, '['.$title.'] Issue Priority Changed',
      'Issue Priority changed by '.$_SESSION['tit']['username']."\r\n".'Title: '.get_col($id, 'issues', 'title')."\r\n".'URL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?id='.$id);
  header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
}

function unwatch()
{
error_log(__METHOD__);

  $id = (int)$_POST['id'];
  setWatch($id, false);
  header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
}

function watch()
{
  $id = (int)$_POST['id'];
  setWatch($id, true);
  header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
}

function deletecomment()
{
error_log(__METHOD__);

  $db = cfg('db');
  $id = (int)$_GET['id'];
  $cid = (int)$_GET['cid'];
  if ((isset($_SESSION['tit']['admin']) and $_SESSION['tit']['admin']) || $_SESSION['tit']['username'] == get_col($cid, 'comments', 'user'))
    $db->exec("
DELETE FROM comments
WHERE id = $cid");
  header('Location: '.$_SERVER['PHP_SELF'].'?id='.$id);
}

function createcomment()
{
error_log(__METHOD__);

  $db = cfg('db');
  $title = cfg('title');
  $notify = cfg('notify');
  $id = (int)$_GET['id'];
  $issue_id = (int)$_POST['issue_id'];
  $description = pdo_escape_string($_POST['description']);
  $user = $_SESSION['tit']['username'];
  $now = date("Y-m-d H:i:s");
  if (trim($description) != '') {
    $query = "
INSERT INTO comments (issue_id, description, user, entrytime)
VALUES ('$issue_id','$description','$user','$now')";
    $db->exec($query);
  }
  if ($notify['comment_create'])
    notify($id, '['.$title.'] New Comment Posted',
      'New comment posted by '.$user."\r\n".'Title: '.get_col($id, 'issues', 'title')."\r\n".'URL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?id='.$issue_id);
  header('Location: '.$_SERVER['PHP_SELF'].'?id='.$issue_id);
}

function deleteissue()
{
error_log(__METHOD__);

  $db = cfg('db');
  $title = cfg('title');
  $notify = cfg('notify');
  $id = (int)$_GET['id'];
  $title = get_col($id, 'issues', 'title');
  if ((isset($_SESSION['tit']['admin']) and $_SESSION['tit']['admin']) || $_SESSION['tit']['username'] == get_col($id, 'issues', 'user')) {
    $db->exec("
DELETE FROM issues
WHERE id = $id");
    $db->exec("
DELETE FROM comments
WHERE issue_id = $id");
    if ($notify['issue_delete']) {
      notify($id, '['.$title.'] Issue Deleted',
        'Issue deleted by '.$_SESSION['tit']['username']."\r\n".'Title:'.$title);
    }
  }
  header('Location: '.$_SERVER['PHP_SELF']);
}

function createissue()
{
error_log(__METHOD__);

  $db = cfg('db');
  $title = cfg('title');
  $users = cfg('users');
  $notify = cfg('notify');
  $id = (int) $_POST['id'];
  $title = pdo_escape_string($_POST['title']);
  $description = pdo_escape_string($_POST['description']);
  $priority = pdo_escape_string($_POST['priority']);
  $user = pdo_escape_string($_SESSION['tit']['username']);
  $now = date("Y-m-d H:i:s");
  $emails = array();
  for ($i = 0; $i < count($users); $i++) {
    if ($users[$i]['email'] != '') $emails[] = $users[$i]['email'];
  }
  $notify_emails = implode(',', $emails);
  if ($id == '')
    $query = "
INSERT INTO issues (title, description, user, priority, notify_emails, entrytime)
VALUES ('$title', '$description', '$user', '$priority', '$notify_emails', '$now')";
  else
    $query = "
UPDATE issues SET title='$title', description='$description'
WHERE id = $id";
  if (trim($title) != '') {
    $db->exec($query);
    if ($id == '') {
      $id = $db->lastInsertId();
      if ($notify['issue_create'])
        notify($id, '['.$title.'] New Issue Created',
          'New Issue Created by '.$user."\r\n".'Title: '.$title."\r\n".'URL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?id='.$id);
    } else {
      if ($notify['issue_edit'])
        notify($id, '['.$title.'] Issue Edited',
          'Issue edited by '.$user."\r\n".'Title: '.$title."\r\n".'URL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?id='.$id);
    }
  }
  header('Location: '.$_SERVER['PHP_SELF']);
}

function get_issue()
{
error_log(__METHOD__);

  $db = cfg('db');
  $issue = array();
  $comments = array();
  if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $issue = $db->query("
SELECT id, title, description, user, status, priority, notify_emails, entrytime
  FROM issues
WHERE id = $id")->fetchAll(PDO::FETCH_ASSOC);
    $comments = $db->query("
SELECT id, user, description, entrytime
  FROM comments WHERE issue_id = $id
ORDER BY entrytime ASC")->fetchAll(PDO::FETCH_ASSOC);
  }
  if (count($issue) == 0) {
    $status = isset($_GET['status']) ? (int)$_GET['status'] : 0;
    $issues = $db->query("
SELECT id, title, description, user, status, priority, notify_emails, entrytime, comment_user, comment_time
FROM issues
LEFT JOIN (
      SELECT user AS comment_user, entrytime AS comment_time, issue_id
        FROM comments
        ORDER BY entrytime DESC
      ) AS c ON c.issue_id = issues.id
WHERE status=".((int)$status > 0 ? $status : "0 or status is null")."
GROUP BY id ORDER BY priority, entrytime DESC")->fetchAll(PDO::FETCH_ASSOC);
    cfg('comments', $comments);
    cfg('issues', $issues);
    cfg('issue', $issue);
    cfg('mode', 'list');
  } else {
    cfg('comments', $comments);
    cfg('issues', array());
    cfg('issue', $issue[0]);
    cfg('mode', 'issue');
  }
}

function login()
{
error_log(__METHOD__);

  if (check_credentials($_POST['u'], md5($_POST['p']))) {
    $users = cfg('users');
    $_SESSION['tit'] = $users[$_POST['u']];
    header('Location: '.$_SERVER['PHP_SELF']);
  } else header('Location: '.$_SERVER['PHP_SELF'].'?loginerror');
}

function login_form()
{
error_log(__METHOD__);

  return '
<html>
<head>
  <title>Tiny Issue Tracker</title>
  <style>
body, input { font-family: sans-serif; font-size: 11px; }
label { display: block; }
  </style>
</head>
<body>
  <h2>'.cfg('title').' - Issue Tracker</h2>
  <p>'.(isset($_GET['loginerror']) ? 'Invalid username or password' : '').'</p>
  <form method="POST">
    <label>Username</label><input type="text" name="u">
    <label>Password</label><input type="password" name="p">
    <label></label><input type="submit" name="login" value="Login">
  </form>
</body>
</html>
';
}

function logout()
{
error_log(__METHOD__);

  $_SESSION['tit'] = array();
  header('Location: '.$_SERVER['PHP_SELF']);
}

// original support functions

function pdo_escape_string($str)
{
error_log(__METHOD__);

  $db = cfg('db');
  $quoted = $db->quote($str);
  return ($db->quote("") == "''") ? substr($quoted, 1, strlen($quoted) - 2) : $quoted;
}

function check_credentials($u, $p)
{
error_log(__METHOD__);

  $users = cfg('users');
  return $users[$u]['password'] == $p ? true : false;
}

function get_col($id, $table, $col)
{
error_log(__METHOD__);

  $db = cfg('db');
  $result = $db->query("
SELECT $col FROM $table
WHERE id='$id'")->fetchAll();
  return $result[0][$col];
}

function notify($id, $subject, $body)
{
error_log(__METHOD__);

  $db = cfg('db');
  $result = $db->query("
SELECT notify_emails
  FROM issues
WHERE id='$id'")->fetchAll();
  $to = isset($result[0]) ? $result[0]['notify_emails'] : '';
  if ($to != '') {
    $headers = 'From: '.cfg('email')."\r\n".'X-Mailer: PHP/'.phpversion();
    mail($to, $subject, $body, $headers);
  }
}

function watchFilterCallback($email)
{
error_log(__METHOD__);

  return $email != $_SESSION['tit']['email'];
}

function setWatch($id, $addToWatch)
{
error_log(__METHOD__);

  $db = cfg('db');
  if ($_SESSION['tit']['email'] == '') return;
  $result = $db->query("
SELECT notify_emails
  FROM issues
WHERE id = $id")->fetchAll();
  $notify_emails = $result[0]['notify_emails'];
  $emails = $notify_emails ? explode(',', $notify_emails) : array();

  if ($addToWatch) $emails[] = $_SESSION['tit']['email'];
  else $emails = array_filter($emails, 'watchFilterCallback');

  $emails = array_unique($emails);
  $notify_emails = implode(',', $emails);
  $db->exec("
UPDATE issues SET notify_emails='$notify_emails'
WHERE id = $id");
}

// markc added cfg(), dropdown() and template functions

// static
function cfg($k = NULL, $v = NULL)
{
error_log(__METHOD__);

  static $stash = array();
  if (empty($k)) return $stash;
  if (is_array($k)) return $stash = array_merge($stash, $k);
  if ($v) $stash[$k] = $v;
  return isset($stash[$k]) ? $stash[$k] : NULL;
}

// static
function dropdown($ary, $name, $sel = '', $extra = '')
{
error_log(__METHOD__);

  $buf = '';
  foreach($ary as $k=>$v) $buf .= '
            <option value="'.$k.'"'.($sel == "$k" ? ' selected' : '').'>'.$v.'</option>';
  return '
          <select name="'.$name.'"'.$extra.'>'.$buf.'
          </select>';
}

function menu()
{
error_log(__METHOD__);

  $issue = cfg('issue');
  $statuses = cfg('statuses');
  $status = isset($_GET['status']) ? $_GET['status'] : (isset($issue['status']) ? $issue['status'] : 0);
  $buf = '';
  foreach($statuses as $k => $v) {
    $style = $status == $k ? ' style="font-weight:bold;"' : '';
    $buf .= '
    <a href="'.$_SERVER['PHP_SELF'].'?status='.$k.'" alt="'.$v.' Issues"'.$style.'>'.$v.' Issues</a> | ';
  }
  $u = (isset($_SESSION['tit']['username'])) ? $_SESSION['tit']['username'] : '';
  return '
    <div id="menu">'.$buf.'
      <a href="'.$_SERVER['PHP_SELF'].'?logout" alt="Logout">Logout ['.$u.']</a>
    </div>';
}

function ttitle()
{
error_log(__METHOD__);

  $issue = cfg('issue');
  $onclick = "document.getElementById('create').className='';document.getElementById('title').focus();";
  $create_edit = (!isset($issue['id']) or $issue['id'] == '') ? 'Create' : 'Edit';
  $issue_id = isset($issue['id']) ? $issue['id'] : '';
  return '
      <a href="#" onclick="'.$onclick.'">'.$create_edit.' Issue '.$issue_id.'</a>';
}

function editor()
{
error_log(__METHOD__);

  $issue = cfg('issue');
  $editissue = isset($_GET['editissue']) ? '' : 'hide';
  $issue_id = isset($issue['id']) ? $issue['id'] : '';
  $issue_title = isset($issue['title']) ? htmlentities($issue['title']) : '';
  $issue_desc = isset($issue['description']) ? htmlentities($issue['description']) : '';
  $create_edit = (!isset($issue['id']) or $issue['id'] == '') ? 'Create' : 'Edit';
  return '
    <div id="create" class="'.$editissue.'">
      <a href="#" onclick="document.getElementById(\'create\').className=\'hide\';" style="float: right;">[Close]</a>
      <form method="POST">
        <input type="hidden" name="id" value="'.$issue_id.'" />
        <label>Title</label><input type="text" size="50" name="title" id="title" value="'.$issue_title.'" />
        <label>Description</label><textarea name="description" rows="5" cols="50">'.$issue_desc.'</textarea>
        <label></label><input type="submit" name="createissue" value="'.$create_edit.'" />
        Priority
          <select name="priority">
            <option value="1">High</option>
            <option selected value="2">Medium</option>
            <option value="3">Low</option>
          </select>
      </form>
    </div>';
}

function mode_list()
{
error_log(__METHOD__);

  $issues = cfg('issues');
  $statuses = cfg('statuses');
  $hdr = isset($_GET['status']) ? $statuses[$_GET['status']].' ' : '';
  return '
    <div id="list">
      <h2>'.$hdr.'Issues</h2>
      <table border=1 cellpadding=5 width="100%">
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Created by</th>
          <th>Date</th>
          <th><acronym title="Watching issue?">W</acronym></th>
          <th>Last Comment</th>
          <th>Actions</th>
        </tr>'.mode_list_issues($issues).'
      </table>
    </div>';
}

// static
function mode_list_issues($issues)
{
error_log(__METHOD__);

  $buf = '';
  $count = 1;
  foreach ($issues as $issue) {
    $count++;
    $buf .= '
        <tr class="p'.$issue['priority'].'">
          <td>'.$issue['id'].'</a></td>
          <td><a href="?id='.$issue['id'].'">'.htmlentities($issue['title'], ENT_COMPAT, 'UTF-8').'</a></td>
          <td>'.$issue['user'].'</td>
          <td>'.$issue['entrytime'].'</td>
          <td>'.((isset($_SESSION['tit']['email']) and $_SESSION['tit']['email']) and strpos($issue['notify_emails'], $_SESSION['tit']['email']) !== FALSE ? '&#10003;' : '').'</td>
          <td>'.($issue['comment_user'] ? date('M j',strtotime($issue['comment_time'])).' ('.$issue['comment_user'].')' : '').'</td>
          <td><a href="?editissue&id='.$issue['id'].'">Edit</a>';

            if ((isset($_SESSION['tit']['admin']) and $_SESSION['tit']['admin'] == 1) or (isset($_SESSION['tit']['username']) and $_SESSION['tit']['username'] == $issue['user']))
              $buf .= ' | <a href="?deleteissue&id='.$issue['id'].'" onclick="return confirm(\"Are you sure? All comments will be deleted too.\");">Delete</a>';
            $buf .= '
          </td>
        </tr>';
  }
  return $buf;
}

function mode_issue()
{
error_log(__METHOD__);

  $issue = cfg('issue');
  $statuses = cfg('statuses');
  $comments = cfg('comments');
  $priority_ary = cfg('priority_ary');
  $priority_str = dropdown($priority_ary, 'priority', $issue['priority'],
    ' onchange="location=\''.$_SERVER['PHP_SELF'].'?changepriority&id='.$issue['id'].'&priority=\'+this.value"');
  $title = htmlentities($issue['title'], ENT_COMPAT, 'UTF-8');
  $description = nl2br(preg_replace("/([a-z]+:\/\/\S+)/","<a href='$1'>$1</a>", htmlentities($issue['description'], ENT_COMPAT, 'UTF-8')));
  $statuses_str = dropdown($statuses, 'status', $issue['status'],
    ' onchange="location=\''.$_SERVER['PHP_SELF'].'?changestatus&id='.$issue['id'].'&status=\'+this.value"');
  $watch = ($_SESSION['tit']['email'] && strpos($issue['notify_emails'], $_SESSION['tit']['email']) === false)
    ? '<input type="submit" name="watch" value="Watch">'
    : '<input type="submit" name="unwatch" value="Unwatch">';
  return '
    <div id="show">
      <div class="issue">
        <h2>'.$title.'</h2>
        <p>'.$description.'</p>
      </div>
      <div class="left">
        Priority'.$priority_str.'
        Status'.$statuses_str.'
      </div>
      <div class="left">
        <form method="POST">
          <input type="hidden" name="id" value="'.$issue['id'].'">'.$watch.'
        </form>
      </div>
      <div class="clear"></div>
      <div id="comments">'.mode_issue_comments($issue, $comments).'
        <div id="comment-create">
          <h4>Post a comment</h4>
          <form method="POST">
            <input type="hidden" name="issue_id" value="'.$issue['id'].'">
            <textarea name="description" rows="5" cols="50"></textarea>
            <label></label>
            <input type="submit" name="createcomment" value="Comment">
          </form>
        </div>
      </div>
    </div>';
}

// static
function mode_issue_comments($issue, $comments)
{
error_log(__METHOD__);

  $buf = '';
  if (count($comments) > 0) $buf .= '
    <h3>Comments</h3>';
  foreach($comments as $comment) {
    $buf .=  "
    <div class='comment'><p>".nl2br(preg_replace("/([a-z]+:\/\/\S+)/","<a href='$1'>$1</a>", htmlentities($comment['description'], ENT_COMPAT, 'UTF-8') ) ).'</p>';
    $buf .= "
      <div class='comment-meta'><em>{$comment['user']}</em> on <em>{$comment['entrytime']}</em> ";
    if ((isset($_SESSION['tit']['admin']) and $_SESSION['tit']['admin']) || $_SESSION['tit']['username']==$comment['user'])
      $buf .= "
        <span class='right'><a href='{$_SERVER['PHP_SELF']}?deletecomment&id={$issue['id']}&cid={$comment['id']}' onclick='return confirm(\"Are you sure?\");'>Delete</a></span>";
    $buf .= '
      </div>
    </div>';
  }
  return $buf;
}

function list_mode()
{
error_log(__METHOD__);

  $mode = cfg('mode');
  if ($mode == 'list') return mode_list();
  elseif ($mode == 'issue') return mode_issue();
  else return "WTF!";
}

function page()
{
error_log(__METHOD__);

  return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>'.cfg('title').' - Issue Tracker</title>
    <!-- <link rel="stylesheet" type="text/css" href="tit.css"> -->
    <style>
html { overflow-y: scroll; }
body { font-family: sans-serif; font-size: 11px; background-color: #aaa; }
a, a:visited { color: #004989; text-decoration: none; }
a:hover { color: #666; text-decoration: underline; }
label { display: block; font-weight: bold; }
table { border-collapse: collapse; }
th { text-align: left; background-color: #f2f2f2; }
tr:hover { background-color: #f0f0f0; }
#menu { float: right; }
#container { width: 760px; margin: 0 auto; padding: 20px; background-color: #fff; }
#footer { padding:10px 0 0 0; margin-top: 20px; text-align: center; border-top: 1px solid #ccc; }
#create { padding: 15px; background-color: #f2f2f2; }
.issue { padding:10px 20px; margin: 10px 0; background-color: #f2f2f2; }
.comment { padding:5px 10px 10px 10px; margin: 10px 0; border: 1px solid #ccc; }
.comment-meta { color: #666; }
.p1, .p1 a { color: red; }
.p3, .p3 a { color: #666; }
.hide { display: none; }
.left { float: left; }
.right { float: right; }
.clear { clear: both; }
    </style>
  </head>
  <body>
    <div id="container">'.menu().'
      <h1>'.cfg('title').'</h1>
      <h2>'.ttitle().'</h2>'.editor().list_mode().'
      <div id="footer">
        Powered by <a href="https://github.com/markc/tit" alt="Tiny Issue Tracker" target="_blank">Tiny Issue Tracker (markc)</a>
      </div>
    </div>
  </body>
</html>
';
}

