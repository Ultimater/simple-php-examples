<?php
// pdo-crud 20130516 (C) Mark Constable (AGPLv3)
// https://github.com/markc/pdo-crud
//
// PDO = PHP Data Objects, a native PHP data-access abstraction layer
// CRUD = Create, Read, Update and Delete, database access methods
//
// This script is simple example of using PHP PDO database abstracion
// to access a news system using either MySQL or SQLite.

echo page();

// CRUD funtions

  function create($n)
  {
dbg(__METHOD__);
    if (isset($_POST['submit'])) {
      try {
        $n->add($_POST['title'], $_POST['text'], $_POST['author']);
        return read($n);
      } catch(PDOException $e) {
        echo $e->getMessage();
      }
    } else {
      return tpl('tpl-form.php', [
        'id' => 0,
        'title' => '',
        'text' => '',
        'author' => '',
        'a' => 'create',
        'i' => 0,
        'p'=>1
      ]);
    }
  }

  function read($n, $i = 0, $p = 1)
  {
dbg(__METHOD__);
    if ($i) {
      $d = $n->get($i);
      $d['updated'] = now(strtotime($d['updated']));
      $tmp = array_merge($d, [
        'update' => sef('/?a=update&i='.$i),
        'delete' => sef('/?a=delete&i='.$i)
      ]);
      return tpl('tpl-item.php', $tmp);
    } else {
      $buf = nav($n);
      try {
        foreach($n->getAll($p) as $d) {
          $d['updated'] = now(strtotime($d['updated']));
          $tmp = array_merge($d, [
            'update' => sef('/?a=update&i='.$d['id']),
            'delete' => sef('/?a=delete&i='.$d['id'])
          ]);
          $buf .= tpl('tpl-item.php', $tmp);
        }
      } catch(Exception $e) {
        echo $e->getMessage();
      }
      return $buf;
    }
  }

  function update($n, $i = 0)
  {
dbg(__METHOD__);
    if (isset($_POST['submit'])) {
      try {
        $n->update($i, $_POST['title'], $_POST['text'], $_POST['author']);
        return read($n);
      } catch(Exception $e) {
        echo $e->getMessage();
      }
    } else {
      return tpl('tpl-form.php',
        array_merge($n->get($i), ['a'=>'update', 'i'=>$i, 'p'=>1]));
    }
  }


  function delete($n, $i = 0)
  {
dbg(__METHOD__);
    try {
      $n->delete($i);
      return read($n);
    } catch(Exception $e) {
      echo $e->getMessage();
    }
  }

  // support functions

  function page()
  {
    $c = cfg(include '.htconf.php');
dbg(__METHOD__);
    $d = new DB($c);
    $n = new Crud($d->getDb());

    if (DBG) error_log(var_export($_REQUEST,true));
    $a = isset($_REQUEST['a']) ? $_REQUEST['a'] : 'read';
    $i = isset($_REQUEST['i']) ? $_REQUEST['i'] : 0;
    $p = isset($_REQUEST['p']) ? $_REQUEST['p'] : 1;
    $n->setItemsPerPage($c['paglen']);
    $b = $a($n, $i, $p);
    return tpl('tpl-body.php', [
      'htitle' => cfg('htitle'),
      'btitle' => cfg('btitle'),
      'readlink' => sef('/?a=read'),
      'createlink' => sef('/?a=create'),
      'content' => $b.dbg()
    ]);
  }

  function cfg($k=NULL, $v=NULL)
  {
dbg(__METHOD__);
    static $stash = array();
    if (empty($k)) return $stash;
    if (is_array($k)) return $stash = array_merge($stash, $k);
    if ($v) $stash[$k] = $v;
    return isset($stash[$k]) ? $stash[$k] : NULL;
  }

  function sef($url)
  {
dbg(__METHOD__);
    return cfg('sefurl')
      ? preg_replace('/[\&].=/', '/', preg_replace('/[\?].=/', '', $url))
      : $url;
  }

  function nav($n)
  {
dbg(__METHOD__);
    $buf = '
    <div class="buttons">&nbsp;';
    $pages = ceil($n->getNumItems() / $n->getItemsPerPage());
    if ($pages > 1) {
      for ($i = 1; $i <= $pages; $i++) {
        $buf .= '
  <a href="'.sef("/?a=read&i=0&p=$i").'" title="Page '.$i.'">'.$i.'</a> ';;
      }
    }
    return $buf.'
    </div>';
  }

  function tpl($f, $t = [])
  {
dbg(__METHOD__);
    if (is_file($f)) {
      ob_start();
      include $f;
      return ob_get_clean();
    }
    return false;
  }

  function dbg($msg='')
  {
    static $dbg = '';
    if ($msg) {
      if (DBG) { $dbg .= $msg."\n"; error_log($msg); }
    } else if (DBG > 9) return '
<h2>Debug</h2>
<pre>'.$dbg.'
Execution time: '.(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']).'
</pre>';
  }

  function now($date1, $date2=NULL)
  {
dbg(__METHOD__);
    $date2 = $date2 ? $date2 : time();
    $diff = abs($date1 - $date2);
    if ($diff < 10) return ' just now';
    $blocks = [
      ['k'=>'year', 'v' => 31536000],
      ['k'=>'month','v' => 2678400],
      ['k'=>'week', 'v' => 604800],
      ['k'=>'day',  'v' => 86400],
      ['k'=>'hour', 'v' => 3600],
      ['k'=>'min',  'v' => 60],
      ['k'=>'sec',  'v' => 1]
    ];
    $levels = 2;
    $current_level = 1;
    $result = array();
    foreach($blocks as $block) {
      if ($current_level > $levels) break;
      if ($diff/$block['v'] >= 1) {
        $amount = floor($diff / $block['v']);
        $plural = ($amount > 1) ? 's' : '';
        $result[] = $amount.' '.$block['k'].$plural;
        $diff -= $amount * $block['v'];
        $current_level++;
      }
    }
    return implode(' ', $result).' ago';
  }

// support classes

class DB {
  private $db;

  public function __construct($cfg)
  {
dbg(__METHOD__);
    extract($cfg);
    try {
      if ($dbtype === 'mysql') {
        $this->db = new PDO(
          'mysql:host='.$dbhost.';port='.$dbport.';dbname='.$dbname.'',
          $dbuser,
          $dbpass);
      } else if ($dbtype === 'sqlite') {
        $this->db = new PDO('sqlite:./'.$dbpath);
      }
      $this->db->query("SET NAMES UTF8");
    } catch (Exception $e) {
      $tmp = cfg('dbtype') === 'mysql' ? $dbname : $dbpath;
      throw new Exception("Connection to database '".$tmp."' failed.\n
        ".$e->getMessage());
    }
  }

  public function getDb()
  {
dbg(__METHOD__);
    return $this->db;
  }
}

class Crud {
  private $db;
  private $itemsPerPage = 20;

  public function  __construct(PDO $db)
  {
dbg(__METHOD__);
    $this->db = $db;
  }

  public function add($title, $text, $author)
  {
dbg(__METHOD__);
    $q = $this->db->prepare("
 INSERT INTO ".cfg('dtable')." (title, text, author, updated, created)
 VALUES (:title, :text, :author, :updated, :created)");

    $q->bindValue(":title", $title);
    $q->bindValue(":text", $text);
    $q->bindValue(":author", $author);
    $q->bindValue(":updated", date("Y-m-d H:i:s"));
    $q->bindValue(":created", date("Y-m-d H:i:s"));
    if (!$q->execute()) {
      $errors = $q->errorInfo();
      throw new Exception(
        "Error while adding a ".cfg('dtable')." (".$errors[2].").");
    }
    $q->closeCursor();
  }

  public function get($id)
  {
dbg(__METHOD__);
    $q = $this->db->prepare("
 SELECT * FROM ".cfg('dtable')."
  WHERE id = :id");

    $q->bindValue(":id", $id, PDO::PARAM_INT);
    if (!$q->execute()) {
      $errors = $q->errorInfo();
      throw new Exception(
        "Error while getting a ".cfg('dtable')." (".$errors[2].").");
    } else {
      if ($res = $q->fetch(PDO::FETCH_ASSOC))
        return $res;
      else
        throw new Exception("No match for id(".$id.").");
    }
    $q->closeCursor();
  }

  public function delete($id)
  {
dbg(__METHOD__);
    $q = $this->db->prepare("
 DELETE FROM ".cfg('dtable')."
  WHERE id = :id");

    $q->bindValue(':id', $id);
    if (!$q->execute()) {
      $errors = $q->errorInfo();
      throw new Exception(
        "Error while deleting a ".cfg('dtable')." (id:".$id.") (".$errors[2].").");
    }
    $q->closeCursor();
  }

  public function update($id, $title, $text, $author)
  {
dbg(__METHOD__);
    $q = $this->db->prepare("
 UPDATE ".cfg('dtable')."
    SET title = :title, text = :text, author = :author, updated = :updated
  WHERE id = :id");

    $q->bindValue(":id", $id, PDO::PARAM_INT);
    $q->bindValue(":title", $title);
    $q->bindValue(":text", $text);
    $q->bindValue(":author", $author);
    $q->bindValue(":updated", date("Y-m-d H:i:s"));
    if (!$q->execute()) {
      $errors = $q->errorInfo();
      throw new Exception(
        "Error while updating a ".cfg('dtable')." (id:".$id.") (".$errors[2].").");
    }
    $q->closeCursor();
  }

  public function getAll($page = 1)
  {
dbg(__METHOD__);
    if($page < 1) $page = 1;
    $q = $this->db->prepare("
 SELECT * FROM ".cfg('dtable')."
  ORDER BY ".cfg('orderby')." ".cfg('ascdesc')."
  LIMIT :start, :itemsPerPage ");

    $q->bindValue(':start', ($page - 1) * $this->itemsPerPage, PDO::PARAM_INT);
    $q->bindValue(':itemsPerPage', $this->itemsPerPage, PDO::PARAM_INT);
    if (!$q->execute()) {
      $errors = $q->errorInfo();
      throw new Exception(
        "Error while getting a ".cfg('dtable')."'s page(".$errors[2].").");
    } else {
      if ($res = $q->fetchAll(PDO::FETCH_ASSOC))
        return $res;
      else
        throw new Exception("No match for page(".$page.").");
    }
    $q->closeCursor();
  }

  public function getNumItems()
  {
dbg(__METHOD__);
    $q = $this->db->query("
 SELECT COUNT(*) FROM ".cfg('dtable'));

    $res = $q->fetch();
    $q->closeCursor();
    return $res[0];
  }

  public function setItemsPerPage($itemsPerPage)
  {
dbg(__METHOD__);
    $this->itemsPerPage = $itemsPerPage;
  }

  public function getItemsPerPage()
  {
dbg(__METHOD__);
    return $this->itemsPerPage;
  }
}

?>
