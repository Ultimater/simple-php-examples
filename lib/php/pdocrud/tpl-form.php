<form action="" method="POST">
  <label for="title">Title</label>
  <input type="text" name="title" id="title" value="<?=$t['title']?>"/>
  <br/>
  <label for="author">Author</label>
  <input type="text" name="author" id="author" value="<?=$t['author']?>"/>
  <br/>
  <textarea name="text" id="text"><?=$t['text']?></textarea>
  <br/>
  <input type="hidden" name="a" value="<?=$t['a']?>"/>
  <input type="hidden" name="i" value="<?=$t['i']?>"/>
  <input type="hidden" name="p" value="<?=$t['p']?>"/>
  <div class="buttons">
    <input type="submit" name="submit" value="Submit"/>
  </div>
</form>
