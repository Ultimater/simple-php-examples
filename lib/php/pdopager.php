<?php

error_log("\n\n!!! START -> ".__FILE__."\n");
// pdopager.php 20140604 (C) Mark Constable <markc@renta.net> (AGPL-3.0)

const ROOT = __DIR__;
require 'pdocommon.php';

$config = [
  'db' => [
    'host' => 'localhost',
    'name' => 'admin',
    'pass' => ROOT.'/.ht_pw.php',
    'path' => ROOT.'/.ht_pager.db',
    'port' => '3306',
    'sock' => '/run/mysqld/mysqld.sock',
    'type' => 'mysql',
    'user' => 'admin',
  ],
  'in' => [
    'm' => 'page',      // (M)odule (class)
    'a' => 'show',      // (A)action (method)
    'i' => 0,           // (I)tem ID
    'j' => '[]',        // (J)son

    'b' => 'id',        // sort order (B)y
    'c' => 'id',        // filter (C)olumn
    'd' => 'ASC',       // sort (D)irection
    'f' => '',          // (F)ilter search
    'l' => 5,           // per page (L)imit
    'n' => 'pdopager',  // db (N)ame //FIXME: repeat of db[name]
    'p' => 1,           // current (P)age
    't' => 'elements',  // db (T)able
    'w' => '',          // (W)here clause
  ],
];

echo view(new pager($config));

class pager
{
    private $buf = '';    // outgoing string buffer

  public function __construct($config)
  {
      error_log(__METHOD__);

      if (isset($_REQUEST['t']) and isset($_SESSION['pager']['t'])) {
          if ($_REQUEST['t'] != $_SESSION['pager']['t']) {
              unset($_SESSION['pager']);
          }
      }

      foreach ($config['in'] as $k => $v) {
          $this->$k = isset($_REQUEST[$k])
        ? strip_tags(trim($_REQUEST[$k]))
        : (isset($_SESSION['pager'][$k]) ? $_SESSION['pager'][$k] : $v);
      }

      $this->dbh = init_pdo($config['db']);
      $this->t = preg_replace('/^([a-zA-Z_]{2,20})$/', '$1', $this->t);
      $this->pager_session($config['db']);

      $this->t = in_array($this->t, $this->tables)  ? $this->t : $config['in']['t']; // redundant
    $this->b = in_array($this->b, $this->columns) ? $this->b : $config['in']['b'];
      $this->c = in_array($this->c, $this->columns) ? $this->c : $config['in']['c'];

      $this->l = is_numeric($this->l) ? (int) $this->l : $config['in']['l'];
      $this->p = is_numeric($this->p) ? (int) $this->p : $config['in']['p'];

      $this->f = preg_replace('/^([a-zA-Z0-9-_\.\^\$]{1,20})$/', '$1', $this->f);
      $this->w = preg_replace('/^([a-zA-Z0-9-_=%\.\']{1,40})$/', '$1', $this->w);

      $this->d = ($this->d === 'ASC' || $this->d === 'DESC') ? $this->d : $config['in']['d'];
      $this->j = json_decode($this->j);

      foreach ($config['in'] as $k => $v) {
          $_SESSION['pager'][$k] = isset($_REQUEST[$k]) ? $this->$k :
        (isset($_SESSION['pager'][$k]) ? $_SESSION['pager'][$k] : $v);
      }

      if (isset($_REQUEST['f']) and isset($_SESSION['pager']['f'])) {
          if ($_REQUEST['f'] != $_SESSION['pager']['f']) {
              $this->p = $_SESSION['pager']['p'] = 1;
          }
      }

      $this->startrow = intval(ceil(($this->p * $this->l) - $this->l));
      if (!msg()) {
          $this->buf = $this->table();
      }
  }

    public function pager_session($db)
    {
        error_log(__METHOD__);

        if (isset($_SESSION['pager']['tables'])) {
            $this->tables = $_SESSION['pager']['tables'];
            $this->columns = $_SESSION['pager']['columns'];
            $this->totalrows = $_SESSION['pager']['totalrows'];
        } else {
            $show_name = $db['type'] == 'sqlite' ? 'name' : 'Tables_in_'.$db['name'];
            $show_tables = $db['type'] == 'sqlite' ? "
 SELECT name FROM sqlite_master WHERE type = 'table'" : '
   SHOW TABLES FROM `'.$db['name'].'`';

            try {
                $this->tables = array_map(
          function ($a) use ($show_name) { return $a[$show_name]; },
            $this->dbh->query($show_tables)->fetchAll());

                $this->columns = array_keys($this->dbh->query("
 SELECT * FROM `{$this->t}` LIMIT 1")->fetch(PDO::FETCH_ASSOC));

                $this->totalrows = (int) $this->dbh->query("
 SELECT count(*) FROM `{$this->t}`")->fetchColumn();
            } catch (PDOException $e) {
                msg($e->getMessage(), 'danger');
            }

            $_SESSION['pager']['tables'] = $this->tables;
            $_SESSION['pager']['columns'] = $this->columns;
            $_SESSION['pager']['totalrows'] = $this->totalrows;
        }
    }

    public function bindValues($stm, $ary)
    {
        error_log(__METHOD__);

        if (is_object($stm) && ($stm instanceof PDOStatement)) {
            foreach ($ary as $k => $v) {
                if (is_int($v)) {
                    $p = PDO::PARAM_INT;
                } elseif (is_bool($v)) {
                    $p = PDO::PARAM_BOOL;
                } elseif (is_null($v)) {
                    $p = PDO::PARAM_NULL;
                } elseif (is_string($v)) {
                    $p = PDO::PARAM_STR;
                } else {
                    $p = false;
                }
                if ($p) {
                    $stm->bindValue(":$k", $v, $p);
                }
            }
        }
    }

    public function query()
    {
        error_log(__METHOD__);

        $where = '';
        if ($this->f) {
            $pre = preg_match('/^[\^]/', $this->f) ? '' : '%';
            $post = preg_match('/[\$]$/', $this->f) ? '' : '%';
            $find = preg_replace('/^[\^](.+)/', '$1', $this->f);
            $find = preg_replace('/(.+)[\$]$/', '$1', $find);
            $where = "
  WHERE {$this->c} LIKE '{$pre}{$find}{$post}'";
        }

        if ($this->w) {
            $where = $where ? $where.$this->w : '
  WHERE '.$this->w;
        }

        $sql = "
 SELECT *
   FROM `{$this->t}`$where
  ORDER BY {$this->b} {$this->d}
  LIMIT :start,:count";
        error_log("sql = $sql");

        try {
            if ($where) {
                $this->totalrows = (int) $this->dbh->query("
 SELECT count(*) FROM `{$this->t}`$where")->fetchColumn();
            }

            $stm = $this->dbh->prepare($sql);
            $this->bindValues($stm, [
        'start' => $this->startrow,
        'count' => $this->l,
      ]);
            $stm->execute();

            return $stm->fetchAll();
        } catch (PDOException $e) {
            msg($e->getMessage(), 'danger');
        }
    }

    public function __toString()
    {
        error_log(__METHOD__);

        return $this->buf;
    }

    public function column($col)
    {
        error_log(__METHOD__."($col)");

        $text = ucwords(str_replace('_', ' ', $col));
        $dstr = 'd=ASC';
        $bstr = 'b='.$col;

        if ($this->d == 'ASC') {
            $dstr = 'd=DESC';
            $icon = ' <i class="fa fa-sort-desc fafw pull-right"></i>';
        } elseif ($this->d == 'DESC') {
            $dstr = 'd=ASC';
            $icon = ' <i class="fa fa-sort-asc fafw pull-right"></i>';
        }

        if ($this->f == $col) {
            $bstr = 'b='.$col;
        } else {
            $icon = ' <i class="fa fa-sort fafw pull-right"></i>';
        }

        return '<a href="?'.$bstr.'&amp;'.$dstr.'">'.$text.$icon.'</a> ';
    }

    public function panel_heading_ipp()
    {
        error_log(__METHOD__);

        $buf = '';
        $ipp = [5, 10, 25, 50, 100, 'All']; // items per page, config?
    foreach ($ipp as $p) {
        $sel = $p == $this->l ? ' selected' : '';
        $buf .= '
                      <option'.$sel.' value="'.$p.'">'.$p.'</option>';
    }

        return '
                  <div class="col-md-2">
                    <select class="form-control input-sm" onchange="window.location=\'?l=\'+this[this.selectedIndex].value;return false;">'.$buf.'
                    </select>
                  </div>';
    }

    public function panel_heading_tables()
    {
        error_log(__METHOD__);

        $buf = '';
        foreach ($this->tables as $t) {
            $sel = $t == $this->t ? ' selected' : '';
            $buf .= '
                      <option'.$sel.' value="'.$t.'">'.$t.'</option>';
        }

        return '
                  <div class="col-md-2">
                    <select class="form-control input-sm" onchange="window.location=\'?t=\'+this[this.selectedIndex].value;return false;">'.$buf.'
                    </select>
                  </div>';
    }

    public function panel_heading_filcol()
    {
        error_log(__METHOD__);

        $buf = '';
        foreach ($this->columns as $c) {
            $sel = $c == $this->c ? ' selected' : '';
            $buf .= '
                      <option'.$sel.' value="'.$c.'">'.ucfirst($c).'</option>';
        }

        return '
                  <div class="col-md-2">
                    <select class="form-control input-sm" id="c" name="c">'.$buf.'
                    </select>
                  </div>';
    }

    public function panel_footer()
    {
        error_log(__METHOD__);

        $buf = '';
        $pgrwindow = 5; // sliding window of how many pager items to show
    $startpage = 1;
        $endpage = $pgrwindow;
        $endrow = $this->startrow + $this->l > $this->totalrows
      ? $this->totalrows : $this->startrow + $this->l;

        $numpages = ceil($this->totalrows / $this->l);
        if ($this->p > $numpages) {
            $this->p = 1;
        }
//FIXME: $this->startrows needs to be reset before query()

    if ($numpages > $pgrwindow) {
        $startpage = $this->p - floor($pgrwindow / 2);
        if ($startpage < 1) {
            $startpage = 1;
        }
        $endpage = $startpage + $pgrwindow;
        if ($endpage > $numpages) {
            $endpage = $numpages;
            $startpage = $endpage - $pgrwindow;
        }
    } else {
        $endpage = $numpages;
    }

        $pager = $numpages > 1 ? $this->pager($startpage, $endpage, $numpages) : '';

        return '
                <div class="row">
                  <div class="col-sm-6">
                    <span>Showing page '.$this->p.' / '.$numpages.' (Rows '.($this->startrow + 1).' - '.$endrow.' / '.$this->totalrows.')</span>
                  </div>
                  <div class="col-sm-6">'.$pager.'
                  </div>
                </div>';
    }

    public function pager($startpage, $endpage, $numpages)
    {
        error_log(__METHOD__."($startpage, $endpage, $numpages)");

        $buf = '';
        for ($i = $startpage; $i <= $endpage; ++$i) {
            $buf .=  ($i == $this->p) ? '
                      <li class="active"><span>'.$i.'</span></li> ' : '
                      <li><a href="?p='.$i.'">'.$i.'</a></li> ';
        }

        $prev_state = $next_state = ' class="disabled"';
        $first = '<span><i class="fa fa-angle-double-left fafw"></i></span>';
        $prev = '<span><i class="fa fa-angle-left fafw"></i></span>';
        $next = '<span><i class="fa fa-angle-right fafw"></i></span>';
        $last = '<span><i class="fa fa-angle-double-right fafw"></i></span>';

        if ($this->p > 1) {
            $prev_state = '';
            $first = '<a href="?p=1'.'"><i class="fa fa-angle-double-left fafw"></i></a>';
            $prev = '<a href="?p='.($this->p - 1).'"><i class="fa fa-angle-left fafw"></i></a>';
        }

        if ($this->p < $numpages) {
            $next_state = '';
            $next = '<a href="?p='.($this->p + 1).'"><i class="fa fa-angle-right fafw"></i></a>';
            $last = '<a href="?p='.$numpages.'"><i class="fa fa-angle-double-right fafw"></i></a>';
        }

        return '
                    <ul class="pagination pagination-sm pull-right">
                      <li'.$prev_state.'>'.$first.'</li>
                      <li'.$prev_state.'>'.$prev.'</li>'.$buf.'
                      <li'.$next_state.'>'.$next.'</li>
                      <li'.$next_state.'>'.$last.'</li>
                    </ul>';
    }

    public function table_head()
    {
        error_log(__METHOD__);

        $buf = '';
        foreach ($this->columns as $k => $v) {
            $buf .= '
                      <th>'.$this->column($v).'</th>';
        }

        return '
                    <tr>'.$buf.'
                    </tr>';
    }

    public function table_body($ary)
    {
        error_log(__METHOD__);

        $buf = '';
        foreach ($ary as $row) {
            $tmp = '';
            foreach ($row as $k => $v) {
                $tmp .= '
                      <td>'.$v.'</td>';
            }
            $buf .= '
                    <tr>'.$tmp.'
                    </tr>';
        }

        return $buf;
    }

    public function panel_heading()
    {
        error_log(__METHOD__);

        return '
                <div class="row">'.$this->panel_heading_ipp().$this->panel_heading_tables().'
                  <div class="col-sm-4 text-center"><h3>'.ucfirst($this->t).'</h3>
                  </div>'.$this->panel_heading_filcol().'
                  <div class="col-sm-2">
                    <div class="input-group has-feedback">
                      <input type="text" class="form-control input-sm hasclear" id="f" name="f" value="'.$this->f.'">
                      <span class="clearer glyphicon glyphicon-remove-circle form-control-feedback"></span>
                      <span class="input-group-btn">
                        <button class="btn btn-default btn-sm" type="submit">
                          <span class="fa fa-filter"></span>
                        </button>
                      </span>
                    </div>
                  </div>
                </div>';
    }

    public function table()
    {
        error_log(__METHOD__);

        return '
        <form class="form-horizontal" role="form">
          <div class="col-md-12">
            <div class="panel panel-primary">
              <div class="panel-heading">'.$this->panel_heading().'
              </div>
              <div class="table-responsive">
                <table class="table table-condensed">
                  <thead>'.$this->table_head().'
                  </thead>
                  <tbody>'.$this->table_body($this->query()).'
                  </tbody>
                </table>
              </div>
              <div class="panel-footer">'.$this->panel_footer().'
              </div>
            </div>
          </div>
        </form>';
    }
} // end pager class

function init_pdo($dbconf)
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
    } catch (PDOException $e) {
        msg($e->getMessage(), 'danger');
    }
}

function msg($msg = null, $level = null)
{
    error_log(__METHOD__);

    static $buf = '';
    static $lvl = 'success';
    if ($level) {
        $lvl = $level;
    }
    if (empty($msg) and empty($buf)) {
        return '';
    } elseif ($msg) {
        if ($buf) {
            $buf .= "<br>\n";
        }
        $buf .= $msg;
    } else {
        return '
        <div class="col-md-12">
          <div class="alert alert-'.$lvl.' alert-dismissable">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            '.$buf.'
          </div>
        </div>';
    }
}

function view($buf)
{
    error_log(__METHOD__);

    return '<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>PDOPager</title>
    <link href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" rel="stylesheet">
    <style>
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
    <div class="container">
      <h1>PDOPager</h1>
      <div class="row tablex">'.msg().$buf.'
      </div>
    </div>
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
