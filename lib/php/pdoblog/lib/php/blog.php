<?php

// blog.php 20140621 (C) Mark Constable <markc@renta.net> (AGPL-3.0)
// https://github.com/markc/ublog

class blog
{
    public $buf = '';
    private $in = [];
    private $db = null;

    public function __construct($in, $db)
    {
        $this->in = $in;
        $this->db = new db($db);
    }

    public function create()
    {
        if ($_POST) {
            $sql = '
 INSERT INTO `blog` (`title`, `content`)
 VALUES (:title, :content)';

            try {
                $stm = $this->db->prepare($sql);
                db::bvs($stm, [
          'title' => $this->in['title'],
          'content' => $this->in['content'],
        ]);
                $stm->execute();
                $stm->closeCursor();
        //return $this->db->lastInsertId();
            } catch (PDOException $e) {
                die($e->getMessage());
            }
            header('Location: '.$_SERVER['PHP_SELF']);
        }
        $this->buf = init::form('New Blog Post', $_SERVER['REQUEST_URI'], self::editor('Add Post'));
    }

    public function read($return = false)
    {
        $sql = $this->in['i']
      ? '
 SELECT * FROM `blog`
  WHERE id = :id LIMIT 1'
      : '
 SELECT * FROM `blog`
  ORDER BY `created` DESC';

        try {
            $stm = $this->db->prepare($sql);
            if ($this->in['i']) {
                db::bvs($stm, ['id' => $this->in['i']]);
            }
            $stm->execute();
            $ary = $this->in['i'] ? $stm->fetch() : $stm->fetchAll();
            $stm->closeCursor();
            if ($return) {
                return $ary;
            }
        } catch (PDOException $en) {
            die($e->getMessage());
        }
        if ($this->in['i']) {
            $this->buf = self::post($ary);
        } else {
            foreach ($ary as $p) {
                $this->buf .= self::index($p);
            }
        }
    }

    public function update()
    {
        if ($_POST) {
            $sql = '
 UPDATE `blog` SET `title` = :title, `content` = :content
  WHERE `id` = :id';

            try {
                $stm = $this->db->prepare($sql);
                db::bvs($stm, [
          'id' => $this->in['i'],
          'title' => $this->in['title'],
          'content' => $this->in['content'],
        ]);
                $stm->execute();
                $count = $stm->rowCount();
                $stm->closeCursor();
            } catch (PDOException $e) {
                die($e->getMessage());
            }
            header('Location: '.$_SERVER['PHP_SELF']);
        }
        $p = $this->read(true);
        $this->buf = init::form(
      'Edit Blog Post',
      $_SERVER['REQUEST_URI'],
      self::editor('Update', $p['id'], $p['title'], $p['content'])
    );
    }

    public function delete()
    {
        $sql = '
 DELETE FROM `blog`
  WHERE `id` = :id';

        try {
            $stm = $this->db->prepare($sql);
            db::bvs($stm, ['id' => $this->in['i']]);
            $res = $stm->execute();
            $stm->closeCursor();
        } catch (PDOException $e) {
            die($e->getMessage());
        }
        header('Location: '.$_SERVER['PHP_SELF']);
    }

    public static function editor($action = 'Submit', $id = 0, $title = '', $content = '', $title_label = 'Title', $content_label = 'Content')
    {
        return '
              <div class="form-group">
                <label for="title">'.$title_label.'</label>
                <input type="text" class="form-control" name="title" id="title" value="'.$title.'" required>
              </div>
              <div class="form-group">
                <label for="content">'.$content_label.'</label>
                <textarea class="form-control" name="content" id="content" rows="9" required>'.$content.'</textarea>
              </div>
              <button class="btn btn-md btn-primary pull-right" type="submit">'.$action.'</button>
              <input type="hidden" name="i" id="i" value="'.$id.'">';
    }

    public static function index($ary)
    {
        extract($ary);

        return '
      <h3><a href="?m=blog&a=read&i='.$id.'">'.$title.'</a></h3>
      <p>
'.nl2br($content).'
      </p>
      <div class="text-right">
        <small><em>Posted on '.$created.'</em></small>
      </div>
      <hr style="margin-top:0">';
    }

    public static function post($ary)
    {
        extract($ary);

        return '
      <h3><a href="?m=blog&a=read">'.$title.'</a></h3>
      <p><small><em>Posted on '.$created.'</em></small></p>
      <p>
'.nl2br($content).'
      </p>
      <div class="row pull-right">
        <div class="col-sm-12">
          <a class="btn btn-sm btn-default" href="?m=blog&a=read">&laquo; Back</a>
          <a class="btn btn-sm btn-primary" href="?m=blog&a=update&i='.$id.'">Update</a>
          <a class="btn btn-sm btn-danger" href="?m=blog&a=delete&i='.$id.'" onClick="javascript: return confirm(\'Are you sure you want to delete?\');">Delete</a>
        </div>
      </div>';
    }
}
