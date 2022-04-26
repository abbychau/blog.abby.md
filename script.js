var docListDom;

function myMarked(str) {
  str = str.replace(
    /\{% youtube (.+?) %\}/g,
    '<iframe id="ytplayer" type="text/html" width="640" height="360" src="https://www.youtube.com/embed/$1" "frameborder="0"></iframe>'
  );
  str = str.replace(/\{% ghcode (.+?) (.+?) (.+?) %\}/g, "$1#L$2-L$3");
  return marked(str, { gfm: true, breaks: true });
}
$(document).ready(function () {
  //$("#content").html(myMarked($("#content").html()));
  hljs.highlightAll();

  docListDom = $("#docList");
  if (localStorage.tempScrollTop) {
    docListDom.scrollTop(localStorage.tempScrollTop);
  }
  docListDom.on("scroll", function () {
    localStorage.setItem("tempScrollTop", docListDom.scrollTop());
  });
});