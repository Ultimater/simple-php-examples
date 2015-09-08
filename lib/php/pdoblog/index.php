<?php

// index.php 20140621 (C) Mark Constable <markc@renta.net> (AGPL-3.0)
// https://github.com/markc/ublog

const ROOT = __DIR__;
const DS = DIRECTORY_SEPARATOR;
function __autoload($c)
{
    include ROOT.DS.'lib'.DS.'php'.DS.$c.'.php';
}

echo new init([
  'db' => [
    'host' => '127.0.0.1',
    'name' => 'ublog',
    'pass' => 'changeme', // ROOT.'.ht_pw.php',
    'path' => ROOT.DS.'lib'.DS.'db'.DS.'.ht_ublog.db',
    'port' => '3306',
    'sock' => '/run/mysqld/mysqld.sock', // or just ''
    'type' => 'mysql',
    'user' => 'ublog',
  ],
  'in' => [
    'm' => 'blog', // (M)odule (class)
    'a' => 'read', // (A)action (method)
    'i' => 0,      // (I)tem ID (not used)
    'title' => '',
    'content' => '',
  ],
  'out' => [
    'buf' => '',
    'css' => '',
    'dbg' => '',
    'doc' => 'uBlog',
    'end' => '<br><em><small>Copyright (C) 2014 Mark Constable</small></em><br>',
    'js' => '',
    'meta' => '',
    'msg' => '',
    'nav' => '',
    'self' => $_SERVER['PHP_SELF'],
    'title' => 'uBlog',
    'top' => '',
  ],
  'nav' => [
    'lhs' => [['?m=blog&a=create', 'Add Post', 'envelope']],
    'rhs' => [],
  ],
]);
