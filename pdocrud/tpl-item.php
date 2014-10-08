<table>
  <tr>
    <th><?=$t['title']?></th>
    <th>
      by <b><?=$t['author']?></b>
      <i><?=$t['updated']?></i>
      <small>
        <a href="<?=$t['update']?>" title="Update">E</a>
        <a href="<?=$t['delete']?>" title="Delete">X</a>
      </small>
    </th>
  </tr>
  <tr>
    <td colspan="2"><?=$t['text']?></td>
  </tr>
</table>
<br>
