<?php error_log("\n\n!!! START -> ".__FILE__."\n");
// pdousers.php 20140604 (C) Mark Constable <markc@renta.net> (AGPL-3.0)

const ROOT = __DIR__;

session_start();
error_log('GET='.var_export($_GET, true));
error_log('POST='.var_export($_POST, true));
error_log('SESSION='.var_export($_SESSION, true));
//$_SESSION = []; // uncomment to reset the session vars for testing

echo view(init(cfg([
  'title'     => 'PDO Login',
  'footer'    => '&copy; 2013',
  'sefurl'    => false,
  'db'        => null,
  'dtable'    => 'login',
  'pagelen'   => 2,
  'orderby'   => 'updated',
  'ascdesc'   => 'DESC',
  'dbconf'    => [
    'host'    => 'localhost',
    'name'    => 'admin',
    'pass'    => ROOT.'/.ht_pw.php',
    'path'    => ROOT.'/.ht_users.db',
    'port'    => '3306',
    'sock'    => '/run/mysqld/mysqld.sock',
    'type'    => 'mysql',
    'user'    => 'admin'
  ],
  'admin'     => [
    'id'      => '1000',
    'uid'     => 'admin',
    'acl'     => 127,
    'fname'   => 'System',
    'lname'   => 'Administrator',
    'email'   => 'admin@localhost.lan',
    'passwd'  => 'changeme',
    'updated' => '',
    'created' => ''
  ],
  'adm'       => false,
  'acl'       => [
    ['0', 'Disabled'],
    ['1', 'User'],
    ['127', 'Admin']
  ],
  'views'     => [
    'home',
    'about',
    'login',
    'logout',
    'admin',
    'register',
    'forgotpw',
    'changepw',
    'profile',
    'newuser',
    'deluser',
    'change_password',
    'reset_password',
    'update_profile',
    'delete_account',
    'add_new_user',
    'register_new_account',
    'forgotten_password',
    'activate',
    'install'
  ]])));

// function aliases

function add_new_user()      { return register(); }
function register_new_account()  { return register(); }
function delete_account()    { return delete_user(); }
function forgotten_password()  { return forgotpw(); }
function change_password()     { return changepw(); }
function reset_password()    { return forgotpw(); }
function update_profile()    { return profile(); }

// private support functions

function cfg($k = NULL, $v = NULL)
{
error_log(__METHOD__);

  static $stash = [];
  if (empty($k)) return $stash;
  if (is_array($k)) return $stash = array_merge($stash, $k);
  if ($v) $stash[$k] = $v;
  return isset($stash[$k]) ? $stash[$k] : NULL;
}

function init($cfg)
{
error_log(__METHOD__);

  if (!empty($_POST)) cfg('db', pdo_init($cfg['dbconf']));
  $a = $_SESSION['a'] = isset($_REQUEST['a'])
    ? strtolower(str_replace(' ', '_', trim($_REQUEST['a'])))
    : 'home';
  $_SESSION['p'] = isset($_REQUEST['p']) ? (int)$_REQUEST['p'] : 1;
  return in_array($a, $cfg['views']) ? $a() : '<b>View does not exist</b>';
}

function lhs_nav($ary = [])
{
error_log(__METHOD__);

  static $nav = [];
  if (empty($ary)) {
    $pre = [['about', 'About']];
    $usr = is_user() ? [] : [];
    $adm = is_admin() ? [] : [];
    $nav = array_merge($pre, $nav, $adm, $usr);
    $buf = '';
    foreach($nav as $k => $v) {
      $s = $v[0] == $_SESSION['a'] ? ' class="active"' : '';
      $buf .= '
            <li><a'.$s.' href="?a='.$v[0].'">'.$v[1].'</a></li>';
    }
    return '
          <ul class="nav navbar-nav">'.$buf.'
          </ul>';
  } else {
    $nav = array_merge($nav, $ary);
  }
}

function rhs_nav($ary = [])
{
error_log(__METHOD__);

  static $nav = [];
  $uid = isset($_REQUEST['u']['uid']) ? $_REQUEST['u']['uid'] : '';
  $login_form = '
          <form class="navbar-form navbar-right" role="form" method="post">
            <div class="form-group">
              <input type="email" name="u[uid]" placeholder="Email" class="form-control" value="'.$uid.'" required>
            </div>
            <div class="form-group">
              <input type="password" name="u[passwd]" placeholder="Password" class="form-control" required>
            </div>
            <button type="submit" name="a" value="login" class="btn btn-success">Sign in</button>
            <button type="submit" name="a" value="register" class="btn btn-primary">Sign up</button>
          </form>';

  if (empty($ary)) {
    if (!is_user()) return $login_form;
    $pre = [];
    $usr = is_user() ? [['profile', 'Profile'], ['logout', 'Logout']] : [['login', 'Login']];
    $adm = is_admin() ? [['admin', 'Admin']] : [];
    $nav = array_merge($pre, $nav, $adm, $usr);
    $buf = '';
    foreach($nav as $k => $v) {
      $s = $v[0] == $_SESSION['a'] ? ' class="active"' : '';
      $buf .= '
                <li><a'.$s.' href="?a='.$v[0].'">'.$v[1].'</a></li>';
    }
    return '
          <ul class="nav navbar-nav navbar-right">
            <li class="dropdown">
              <a href="" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-book"></i> Account <b class="caret"></b></a>
              <ul class="dropdown-menu">'.$buf.'
              </ul>
            </li>
          </ul>';
  } else {
    $nav = array_merge($nav, $ary);
  }
}
function login_form()
{
error_log(__METHOD__);

  $uid = isset($_REQUEST['u']['uid']) ? $_REQUEST['u']['uid'] : '';
  return '
  <form method="post" action="'.$_SERVER['PHP_SELF'].'">
    <label>Login ID</label>
    <input type="text" name="u[uid]" value="'.$uid.'" required>
    <br>
    <label>Password</label>
    <input type="password" name="u[passwd]" autocomplete="off" required>
    <br>
    <br>
    <div class="rhs">
    <input type="submit" name="a" value="Login">
    </div>
  </form>
  <br>
  <div class="mid">
    <a class="btn" href="?a=register">Register New Account</a>
    <a class="btn" href="?a=forgotpw">Forgotten Password</a>
  </div>';
}


function pager()
{
error_log(__METHOD__);

  $pages = ceil(read_numitems() / cfg('pagelen'));
  if ($pages > 1) {
    $buf = '';
    for ($i = 1; $i <= $pages; $i++) {
      $s = $i == $_SESSION['p'] ? ' class="active"' : '';
      $buf .= '
    <a'.$s.' href="'.sef("?a=".$_SESSION['a']."&p=$i").'" title="Page '.$i.'">'.$i.'</a> ';
    }
    return '
  <nav>Page:&nbsp;'.$buf.'
  </nav>';
  }
}

function sef($url)
{
error_log(__METHOD__);

  return cfg('sefurl')
    ? preg_replace('/[\&].=/', '/', preg_replace('/[\?].=/', '', $url))
    : $url;
}

function dropdown($ary, $name, $sel='', $extra='')
{
error_log(__METHOD__);

  $buf = '';
error_log('SESSION='.var_export($ary, true));
  foreach($ary as $k => $v) {
    $s = $sel == "$v[0]" ? ' selected' : '';
    $buf .= '
    <option value="'.$v[0].'"'.$s.'>'.$v[1].'</option>';
  }
  return '
  <select class="select" name="'.$name.'"'.$extra.'>'.$buf.'
  </select>';
}

function is_user()
{
error_log(__METHOD__);

  return (isset($_SESSION['u']) and $_SESSION['u']['acl'] > 0) ? true : false;
}

function is_admin()
{
error_log(__METHOD__);

  return (
    isset($_SESSION['u']) and
    isset($_SESSION['adm']) and
    $_SESSION['adm'] == true
  ) ? true : false;
}

// public callable functions

function home()
{
error_log(__METHOD__);

  return '
    <div class="jumbotron">
      <div class="container">
        <h1>Home</h1>
        <p>This is a template for a simple marketing or informational website. It includes a large callout called a jumbotron and three supporting pieces of content. Use it as a starting point to create something more unique.</p>
        <p><a class="btn btn-primary btn-lg" role="button">Learn more &raquo;</a></p>
      </div>
    </div>';
}

function about()
{
error_log(__METHOD__);

  return '
    <div class="jumbotron">
      <div class="container">
        <h1>About</h1>
        <p>This is a template for a simple marketing or informational website. It includes a large callout called a jumbotron and three supporting pieces of content. Use it as a starting point to create something more unique.</p>
        <p><a class="btn btn-primary btn-lg" role="button">Learn more &raquo;</a></p>
      </div>
    </div>';
}

function logout()
{
error_log(__METHOD__);

  $_SESSION['m'] = $_SESSION['u']['uid'].' is now logged out';
  //if ($_SESSION['u']['acl'] == 127) $_SESSION['adm'] = false;
  unset($_SESSION['u']);
  header('Location: '.$_SERVER['PHP_SELF']);
  exit();
}

function login()
{
error_log(__METHOD__);

  if (!empty($_POST)) {
    $m = '';
    $u = read_user($_POST['u']['uid']);
    if (isset($u['uid'])) {
      if ($u['acl']) {
        if (password_verify($_POST['u']['passwd'], $u['passwd'])) {
          $_SESSION['u'] = $u;
          $_SESSION['m'] = $u['uid'].' is now logged in';
          if ($u['acl'] == 127) $_SESSION['adm'] = true;
          header('Location: '.$_SERVER['PHP_SELF']);
          exit();
        } else $m = 'Password does not match';
      } else $m = 'Account is disabled, contact your System Administrator';
    } else $m = 'Username does not exist';
    $_SESSION['m'] = $m;
  }
  return login_form();
}

function admin()
{
error_log(__METHOD__);

  return admin_form();
}

function register()
{
error_log(__METHOD__);

  if (!empty($_POST)) {
    $m = '';
    if ($_POST['u']['passwd'] === $_POST['u']['passwd2']) {
      if (strlen($_POST['u']['passwd']) > 7) {
        if (preg_match('/^[a-z\d]{2,64}$/i', $_POST['u']['uid'])) {
          $u = read_user($_POST['u']['uid']);
          if (!isset($u['uid'])) {
            if (!isset($u['email'])) {
              if (filter_var($_POST['u']['email'], FILTER_VALIDATE_EMAIL)) {
                $pwkey = $_POST['u']['pwkey'] = substr(sha1(time()), rand(0, 31), 8);
                $id = create_user($_POST['u']);
                if (is_admin()) {
                  $_SESSION['m'] = 'Added new user: '.$_POST['u']['uid'];
                  return admin();
                } else {
                  if (mail_activate($_POST['u']['uid'], $_POST['u']['email'], $pwkey)) {
                    $_SESSION['m'] = 'A message has been sent to your email address with further instructions';
                    unset($_SESSION['u']);
                    header('Location: '.$_SERVER['PHP_SELF']);
                    exit();
                  } else $m = 'Problem sending message to '.$_POST['u']['email'];
                }
              } else $m = 'You must provide a valid email address';
            } else $m = 'Email address already exists';
          } else $m = 'Username already exists';
        } else $m = 'Username must be only a-z, A-Z, 0-9';
      } else $m = 'Password must be at least 8 characters';
    } else $m = 'Passwords do not match';
    $_SESSION['m'] = $m;
  }
  return register_form();
}

function forgotpw()
{
error_log(__METHOD__);

  if (!empty($_POST)) {
    $m = '';
    if (filter_var($_POST['u']['email'], FILTER_VALIDATE_EMAIL)) {
      $u = read_user($_POST['u']['email']);
      if (isset($u['uid'])) {
        if ($u['acl']) {
          $newpass = substr(sha1(time()), rand(0, 31), 8);
          if (mail_forgotpw($u['uid'], $u['email'], $newpass)) {
            update_password($u['id'], $newpass);
            $_SESSION['m'] = 'Reset password for '.$u['uid'].'<br>Check your mailbox';
          } else {
            $_SESSION['m'] = 'Problem sending message to '.$u['email'];
          }
          header('Location: '.$_SERVER['PHP_SELF']);
          exit();
        } else $m = 'Account is disabled, contact your System Administrator';
       } else $m = 'User does not exist';
    } else $m = 'You must provide a valid email address';
    $_SESSION['m'] = $m;
  }
  return forgotpw_form();
}

function changepw()
{
error_log(__METHOD__);

  if (!empty($_POST)) {
    $m = '';
    if (strlen($_POST['u']['passwd']) > 7) {
      if ($_POST['u']['passwd'] === $_POST['u']['passwd2']) {
        $u = read_user($_SESSION['u']['id']);
        if (isset($u['id'])) {
          update_password($u['id'], $_POST['u']['passwd']);
          $_SESSION['m'] = 'Updated password for '.$_SESSION['u']['uid'];
          header('Location: '.$_SERVER['PHP_SELF']);
          exit();
        } else $m = 'Invalid account';
      } else $m = 'New Passwords do not match';
    } else $m = 'New password must be at least 8 characters';
    $_SESSION['m'] = $m;
  }
  return changepw_form();
}

function profile()
{
error_log(__METHOD__);

  if (!isset($_SESSION['u'])) {
    return login_form();
  } else {
    $id = isset($_REQUEST['id']) ? $_REQUEST['id']
    : (isset($_POST['u']['id']) ?  $_POST['u']['id'] : $_SESSION['u']['id']);
    if (!empty($_POST)) {
      $m = '';
      if (filter_var($_POST['u']['email'], FILTER_VALIDATE_EMAIL)) {
        update_user($_POST['u']);
        if (!is_admin()) $_SESSION['u'] = read_user($id);
        $_SESSION['m'] = 'Profile updated for '.$_POST['u']['uid'];
        return profile_form($_POST['u']);
      } else $m = 'You must provide a valid email address';
      $_SESSION['m'] = $m;
    }
    cfg('db', pdo_init(cfg('dbconf')));
    return profile_form(read_user($id));
  }
}

function newuser()
{
error_log(__METHOD__);

  return isset($_SESSION['u']) ? register_form() : login_form();
}

function deluser()
{
error_log(__METHOD__);

  if (!isset($_SESSION['u'])) {
    return login_form();
  } else {
    if (empty($_POST)) {
      $_SESSION['m'] = 'Do you really want to delete '.$_SESSION['u']['uid'].'?';
      return confirm_form();
    } else {
      if (isset($_POST['confirm']) and $_POST['confirm'] === 'Yes') {
        delete_user($_SESSION['u']['id']);
        $_SESSION['m'] = 'Removed user account for '.$_SESSION['u']['uid'];
        unset($_SESSION['u']);
        header('Location: '.$_SERVER['PHP_SELF']);
        exit();
      }
    }
    return profile_form($_SESSION['u']);
  }
}

function activate()
{
error_log(__METHOD__);

  // TODO filter incoming GET var
  $key = isset($_GET['key']) ? $_GET['key'] : 'dudkey';
  cfg('db', pdo_init(cfg('dbconf')));
  $id = read_pwkey($key);
  if ($id) {
    activate_user($id);
    $_SESSION['m'] = 'Your account is now activated, please log in';
  } else $_SESSION['m'] = 'Incorrect activation, please try again';
  header('Location: '.$_SERVER['PHP_SELF']);
  exit();
}

function install()
{
error_log(__METHOD__);

  $dbh = cfg('db', pdo_init(cfg('dbconf')));
  $tbl = cfg('dtable');
  $pri = cfg('dbconf')['type'] === 'mysql' ?
    "int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT" :
    "INTEGER PRIMARY KEY";
  $ind = cfg('dbconf')['type'] === 'mysql' ? "
  ALTER TABLE `".$tbl."` ADD UNIQUE (`uid`);
  ALTER TABLE `".$tbl."` ADD UNIQUE (`email`);" : "
 CREATE UNIQUE INDEX `uid_UNIQUE` ON `".$tbl."` (`uid` ASC);
 CREATE UNIQUE INDEX `email_UNIQUE` ON `".$tbl."` (`email` ASC);";

  // uncomment below to reinstall tables while testing
  //$dbh->exec("DROP TABLE IF EXISTS `".$tbl."`;");

  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
  if (!$dbh->query("SELECT count(*) FROM $tbl")) {
    try {
      $dbh->exec("
 CREATE TABLE `".$tbl."` (
    `id` $pri,
    `acl` tinyint(1) default 0,
    `uid` varchar(31),
    `fname` varchar(31),
    `lname` varchar(31),
    `email` varchar(63),
    `pwkey` varchar(8),
    `passwd` varchar(255),
    `updated` datetime,
    `created` datetime
); $ind");
    } catch (PDOException $e) { die($e->getMessage()); }
    create_user(cfg('admin'));
    unset($_SESSION['u']);
    $_SESSION['m'] = 'Database and default user are now installed, please login';
  } else $_SESSION['m'] = 'Database table already exists';
  header('Location: '.$_SERVER['PHP_SELF']);
  exit();
}

// CRUD/database functions

function create_user($u)
{
error_log(__METHOD__);

  $q = cfg('db')->prepare("
 INSERT INTO `".cfg('dtable')."` (id, acl, uid, fname, lname, email, pwkey, passwd, updated, created)
 VALUES (:id, :acl, :uid, :fname, :lname, :email, :pwkey, :passwd, :updated, :created)");

  $q->bindValue(':id', $u['id'], PDO::PARAM_INT);
  $q->bindValue(':acl', $u['acl']);
  $q->bindValue(':uid', $u['uid']);
  $q->bindValue(':fname', $u['fname']);
  $q->bindValue(':lname', $u['lname']);
  $q->bindValue(':email', $u['email']);
  $q->bindValue(':pwkey', isset($u['pwkey']) ? $u['pwkey'] : '');
  $q->bindValue(':passwd', password_hash(trim($u['passwd']), PASSWORD_DEFAULT));
  $q->bindValue(':updated', date('Y-m-d H:i:s'));
  $q->bindValue(':created', date('Y-m-d H:i:s'));
  if (!$q->execute()) throw new Exception(die($q->errorInfo()));
  $q->closeCursor();
  return cfg('db')->lastInsertId();
}

function read_users()
{
error_log(__METHOD__);

  $q = cfg('db')->prepare("
 SELECT *
   FROM `".cfg('dtable')."`
  ORDER BY ".cfg('orderby')." ".cfg('ascdesc')."
  LIMIT :start, :itemsPerPage");

  $q->bindValue(':start', ($_SESSION['p'] - 1) * cfg('pagelen'), PDO::PARAM_INT);
  $q->bindValue(':itemsPerPage', cfg('pagelen'), PDO::PARAM_INT);
  if (!$q->execute()) throw new Exception(die($q->errorInfo()));
  return $q->fetchAll(PDO::FETCH_ASSOC);
}

function read_user($iue)
{
error_log(__METHOD__);

error_log('read_user(iue) = '.$iue);
  return cfg('db')->query("
 SELECT *
   FROM `".cfg('dtable')."`
  WHERE id = '$iue'
     OR uid = '$iue'
     OR email = '$iue'")->fetch(PDO::FETCH_ASSOC);
}

function update_user($u)
{
error_log(__METHOD__);

  $adm = is_admin() ? ', acl = :acl' : '';
  $q = cfg('db')->prepare("
 UPDATE `".cfg('dtable')."`
    SET fname = :fname, lname = :lname, email = :email, updated = :updated".$adm."
  WHERE id = :id");

  extract($u);
  $q->bindValue(':id', $id, PDO::PARAM_INT);
  $q->bindValue(':fname', $fname);
  $q->bindValue(':lname', $lname);
  $q->bindValue(':email', $email);
  $q->bindValue(':updated', date('Y-m-d H:i:s'));
  if ($adm) $q->bindValue(':acl', $acl);
  if (!$q->execute()) throw new Exception(die($q->errorInfo()));
  $q->closeCursor();
}

function delete_user($id)
{
error_log(__METHOD__);

  $q = cfg('db')->prepare("
 DELETE FROM `".cfg('dtable')."`
  WHERE id = :id");

  $q->bindValue(':id', $id, PDO::PARAM_INT);
  if (!$q->execute()) throw new Exception(die($q->errorInfo()));
  $q->closeCursor();
}

function update_password($id, $pw)
{
error_log(__METHOD__);

  $q = cfg('db')->prepare("
 UPDATE `".cfg('dtable')."`
    SET passwd = :passwd, updated = :updated
  WHERE id = :id");

  $q->bindValue(':id', $id, PDO::PARAM_INT);
  $q->bindValue(':passwd', password_hash($pw, PASSWORD_DEFAULT));
  $q->bindValue(':updated', date('Y-m-d H:i:s'));
  if (!$q->execute()) throw new Exception(die($q->errorInfo()));
  $q->closeCursor();
}

function read_numitems()
{
error_log(__METHOD__);

  return cfg('db')->query("
 SELECT COUNT(*) FROM `".cfg('dtable')."`")->fetchColumn();
}

function read_pwkey($key)
{
error_log(__METHOD__);

  return cfg('db')->query("
 SELECT id
   FROM `".cfg('dtable')."`
  WHERE pwkey = '$key'")->fetchColumn();
}

function activate_user($id, $acl = 1)
{
error_log(__METHOD__);

  $q = cfg('db')->prepare("
 UPDATE `".cfg('dtable')."`
    SET acl = :acl, pwkey = :pwkey, updated = :updated
  WHERE id = :id");

  $q->bindValue(':id', $id, PDO::PARAM_INT);
  $q->bindValue(':acl', $acl, PDO::PARAM_INT);
  $q->bindValue(':pwkey', '');
  $q->bindValue(':updated', date('Y-m-d H:i:s'));
  if (!$q->execute()) throw new Exception(die($q->errorInfo()));
  $q->closeCursor();
}

// HTML template functions

function register_form()
{
error_log(__METHOD__);

  $uid = isset($_POST['u']['uid']) ? $_POST['u']['uid'] : '';
  $fname = isset($_POST['u']['fname']) ? $_POST['u']['fname'] : '';
  $lname = isset($_POST['u']['lname']) ? $_POST['u']['lname'] : '';
  $email = isset($_POST['u']['email']) ? $_POST['u']['email'] : '';
  $acl = is_admin() ? '
    <br>
    <label>ACL</label>'.dropdown(cfg('acl'), 'u[acl]', $_SESSION['u']['acl'])
    : '
    <input type="hidden" name="u[acl]" value="0">';
  $adm = is_admin() ? 'Add New User' : 'Register';
  return '
  <form method="post" action="'.$_SERVER['PHP_SELF'].'">
    <input type="hidden" name="u[id]" value="null">
    <label>Username</label>
    <input type="text" pattern="[a-zA-Z0-9]{2,64}" name="u[uid]" value="'.$uid.'" required>
    <br>
    <label>First Name</label>
    <input type="text" pattern="[a-zA-Z0-9]{2,32}" name="u[fname]" value="'.$fname.'">
    <br>
    <label>Last Name</label>
    <input type="text" pattern="[a-zA-Z0-9]{2,32}" name="u[lname]" value="'.$lname.'">
    <br>
    <label>Email Address</label>
    <input type="email" name="u[email]" value="'.$email.'" required>'.$acl.'
    <br>
    <label>Password</label>
    <input type="password" name="u[passwd]" pattern=".{8,32}" required autocomplete="off">
    <br>
    <label>Confirm Password</label>
    <input type="password" name="u[passwd2]" pattern=".{8,32}" required autocomplete="off">
    <br>
    <br>
    <div class="rhs">
    <input type="submit" name="a" value="'.$adm .'">
    </div>
  </form>
  <p>All fields except First Name and Last Name are required. Username must be only
  letters and numbers from 2 to 32 characters long and the password has to be at
  least 8 characters.</p>';
}

function forgotpw_form()
{
error_log(__METHOD__);

  $email = isset($_POST['u']['email']) ? $_POST['u']['email'] : '';
  return '
  <form method="post" action="'.$_SERVER['PHP_SELF'] .'">
    <label>Email Address</label>
    <input type="email" name="u[email]" value="'.$email.'" required>
    <br>
    <br>
    <div class="rhs">
    <input type="submit" name="a" value="Reset Password">
    </div>
  </form>';
}

function changepw_form()
{
error_log(__METHOD__);

  return '
  <h2>Change password for '.$_SESSION['u']['uid'].'</h2>
  <form method="post" action="'.$_SERVER['PHP_SELF'] .'">
    <label>New Password</label>
    <input type="password" name="u[passwd]" pattern=".{8,32}" autocomplete="off" required>
    <br>
    <label>Confirm Password</label>
    <input type="password"  name="u[passwd2]" pattern=".{8,32}" autocomplete="off" required>
    <br>
    <br>
    <div class="rhs">
     <input type="submit" name="a" value="Change Password">
    </div>
  </form>';
}

function profile_form($u)
{
error_log(__METHOD__);

  $acl = is_admin() ? '
    <br>
    <label>ACL</label>'.dropdown(cfg('acl'), 'u[acl]', $u['acl'])
    : '
    <input type="hidden" name="u[acl]" value="'.$u['acl'].'">';
  return '
  <h2>Profile settings for '.$u['uid'].'</h2>
  <form method="post" action="'.$_SERVER['PHP_SELF'].'">
    <input type="hidden" name="u[id]" value="'.$u['id'].'">
    <input type="hidden" name="u[uid]" value="'.$u['uid'].'">
    <label>PIN</label> <b>'.$u['id'].'</b>'.$acl.'
    <br>
    <label>First Name</label>
    <input type="text" pattern="[a-zA-Z0-9]{2,32}" name="u[fname]" value="'.$u['fname'].'">
    <br>
    <label>Last Name</label>
    <input type="text" pattern="[a-zA-Z0-9]{2,32}" name="u[lname]" value="'.$u['lname'].'">
    <br>
    <label>Email Address</label>
    <input type="email" name="u[email]" value="'.$u['email'].'" required>
    <br>
    <br>
    <div class="rhs">
    <input type="submit" name="a" value="Update Profile">
    </div>
  </form>
  <br>
  <div class="mid">
    <a class="btn" href="?a=deluser">Delete Account</a>
    <a class="btn" href="?a=changepw">Change Password</a>
  </div>';
}

function confirm_form()
{
error_log(__METHOD__);

  return '
  <form method="post" action="'.$_SERVER['PHP_SELF'] .'">
    <input type="hidden" name="a" value="'.$_SESSION['a'].'">
    <div class="mid">
    <input class="red" type="submit" name="confirm" value="No">
    <input class="green" type="submit" name="confirm" value="Yes">
    </div>
  </form>';
}

function admin_form()
{
error_log(__METHOD__);

  cfg('db', pdo_init(cfg('dbconf')));
  $users = read_users();
  $buf = '';
  foreach($users as $k => $v) {
    $buf .= '
    <tr>
    <td><a href="?a=profile&id='.$v['id'].'">'.$v['uid'].'</a></td>
    <td>'.$v['fname'].' '.$v['lname'].'</td>
    <td>'.$v['email'].'</td>
    <td>'.array_column(cfg('acl'), 1, 0)[$v['acl']].'</td>
    </tr>';
  }
  return pager().'
  <table>
    <tr><th>Username</th><th>Full Name</th><th>Email</th><th>ACL</th></tr>'.$buf.'
  </table>
  <br>
  <div class="mid">
    <a class="btn" href="?a=newuser">Add New User</a>
  </a>';
}

// mail functions

function mail_forgotpw($uid, $email, $newpass)
{
error_log(__METHOD__);

  return mail(
    $email,
    'New password from '.$_SERVER['HTTP_HOST'],
    'Hello '.$uid.',

Here is your new password. Please login as soon as possible and change it
to something your can remember with at least eight characters including
one uppercase and lowercase and/or number.

Login ID: '.$uid.'
Password: '.$newpass.'
LoginURL: http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?a=login',
    'From: '.cfg('email')."\r\n"
  );
}

function mail_activate($uid, $email, $key)
{
error_log(__METHOD__);

  return mail(
    $email,
    'Welcome to '.$_SERVER['HTTP_HOST'],
    'Hello '.$uid.',

Please click the link below to verify and activate your account and then
change your password as soon as possible.

http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?a=activate&key='. $key,
    'From: '.cfg('email')."\r\n"
  );
}


// common funcs

function pdo_init($dbconf)
{
error_log(__METHOD__);

  extract($dbconf);
  $dsn = $type === 'mysql'
    ? 'mysql:'.($sock ? 'unix_socket='.$sock : 'host='.$host.';port='.$port).';dbname='.$name
    : 'sqlite:'.$path;
  $pass = file_exists($pass) ? include $pass : $pass;
  try {
  return new PDO($dsn, $user, $pass, [
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  } catch(PDOException $e) {
  msg($e->getMessage(), 'danger');
  }
}

function msg($msg=null, $level=null)
{
error_log(__METHOD__);

  static $buf = '';
  static $lvl = 'success';
  if ($level) $lvl = $level;
  if (empty($msg) and empty($buf)) return '';
  else if ($msg) { if ($buf) $buf .= "<br>\n"; $buf .= $msg; }
  else return '
    <div class="col-md-12">
      <div class="alert alert-'.$lvl.' alert-dismissable">
      <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
      '.$buf.'
      </div>
    </div>';
}

function view($buf)
{
error_log(__METHOD__);

  $m = isset($_SESSION['m']) ? '
  <p class="msg">'.$_SESSION['m'].'</p>' : '';
  unset($_SESSION['m']);

  if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
  return lhs_nav().$m.$content;
  }

  return '<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">
  <title>'.cfg('title').'</title>
  <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
  <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">
  <style>
.navbar { margin-bottom: 0; }
th > a { display: block; }
th > a:hover { text-decoration: none; }
th > a i { margin-top: 3px; }
.table > thead:first-child > tr:first-child > th:first-child { width: 4em; }
.tablex .pagination,
.tablex .form-group,
.tablex h1,
.tablex h2,
.tablex h3 { margin: 0; }
.tablex table { min-width: 960px; }
.form-horizontal .has-feedback span.form-control-feedback {
  color: #BFBFBF;
  right: 30px;
  top: -2px;
  z-index: 2;
}
  </style>
  </head>
  <body>
    <div class="navbar navbar-inverse navbar-static-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="?a=home">'.cfg('title').'</a>
        </div>
        <div class="navbar-collapse collapse">'.lhs_nav().rhs_nav().'
        </div><!--/.navbar-collapse -->
      </div>
    </div>

    <div class="row tablex">'.msg().$buf.'
    </div>
      <footer>
        <p>&copy; Company 2014</p>
      </footer>
    </div> <!-- /container -->

  <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
  <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
  <script>
$(function() {
  $(".hasclear").on("keyup", function () {
  var t = $(this);
  t.next("span").toggle(Boolean(t.val()));
  });
  if ($(".clearer").prev("input").val() == "")
  $(".clearer").hide($(this).prev("input").val());
  $(".clearer").on("click", function () {
  $(this).prev("input").val("").focus();
  $(this).hide();
  });
});
  </script>
  </body>
</html>
';
}


function viewold($content)
{
error_log(__METHOD__);

  $m = isset($_SESSION['m']) ? '
  <p class="msg">'.$_SESSION['m'].'</p>' : '';
  unset($_SESSION['m']);

  if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    return lhs_nav().$m.$content;
  }

  return '<!DOCTYPE html>
<html lang="en">
  <head>
  <meta charset="utf-8">
  <title>'.cfg('title').'</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>'.style().'
  </style>
  </head>
  <body>
  <h1>'.cfg('title').'</h1>'.lhs_nav().$m.$content.'
  <footer>'.cfg('footer').'</footer>
  </body>
</html>
';
}
