---
title: 我又用Frameset 了
date: 2021-07-26 02:36:00
categories: 日記
tags:
- blog
weather: sunny
---

今天又是沒有元氣的一天，
在吃飯時，單就看看自己在噗浪寫過的東西，也可以哭起來。

情緒is a mess。

但是，我把這麼多年的爛日記都整理一遍了。
在github 中放置了一個Github Action, 這樣我就可以隨時在Github上面寫日記了。

這個博客也是夠復古的。
我一直有個想法：Frameset 這東西只會一直處於deprecated 的狀態，而永遠不會remove。
原因是：他不像embed / marquee / bgmusic , 移除它對瀏覽器而言亳無好處。
他也足夠簡單地可以被JS 作透明模擬。

所以，我就用Frameset 了。

Frameset 一直有3個問題: (https://www.cs.uct.ac.za/mit_notes/web_programming/html/ch08s02.html)
1. 網址不更新, 無法被用家複製然後傳播 或者 Bookmark
2. Search engines 只能抓到子頁面
3. Back button 變得不合乎習慣

但:
對於1 而言, 我們可以插入一個pushState 啊

```javascript
$('a').on('click', function(e){
    window.parent.history.pushState({}, $(this).text(), "#"+$(this).attr('href'));
})
```

然後

```javascript
$(document).ready(function () {
    if (window.location.hash) {
        var hash = window.location.hash.substring(1); //Puts hash in variable, and removes the # character
        $("#main_frame").attr('src',hash);
    }
})
```

事實上, 這個問題在mvvm 中同樣發生, 而新API 反而給老Frameset 解套了。

對於2 而言: 這在每個頁面中加入一個套新套框的link 就可以了。現在都是自動生成的時代了。
對於3 : 每個連結配合一個後退, 這一直都很合乎習慣, 在MVVM 框架中pushState 後也是假倒退的, 不是嗎?

所以...
我就這樣完成了w
