/* pdocommon.js 20140604 (C) Mark Constable <markc@renta.net> (AGPL-3.0) */

$(function() {

  $(".hasclear").on("keyup", function () {
    var t = $(this);
    t.next("span").toggle(Boolean(t.val()));
  });

  if ($(".clearer").prev("input").val() == "") {
    $(".clearer").hide($(this).prev("input").val());
  }

  $(".clearer").on("click", function () {
    $(this).prev("input").val("").focus();
    $(this).hide();
  });

});
