<?php
// db.php 20140621 (C) Mark Constable <markc@renta.net> (AGPL-3.0)
// https://github.com/markc/ublog

class db extends \PDO {

  public function __construct($dbcfg)
  {
    extract($dbcfg);
    $dsn = $type === 'mysql'
      ? 'mysql:'.($sock ? 'unix_socket='.$sock : 'host='.$host.';port='.$port).';dbname='.$name
      : 'sqlite:'.$path;
    $pass = file_exists($pass) ? include $pass : $pass;
    try {
      parent::__construct($dsn, $user, $pass, [
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
      ]);
    } catch(\PDOException $e) { die($e->getMessage()); }
  }

  public static function bvs($stm, $ary)
  {
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
}
