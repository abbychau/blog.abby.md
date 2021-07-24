---
title: "localhost 與127.0.0.1"
date: "2005-08-19"
categories: 
  - "短評"
---

127.0.0.1 與 localhost 一向在windows 都被認為是完全對等並且共通的,

我也不例外...

直至今日, 我打算用mysql control center 來登入自己的mysql server 時...

我打入localhost:password 才發現登入不能,

還出現access denied 的錯誤信息...

心想伺服器和所有程式都可以用一樣的設置運行,

所以推想應該是埠位有錯,

但我一直都用預設埠: 3306(有種就來hack)...

於是....我突破了proof-reading 傳統,

把localhost 改做127.0.0.1!

(註: HKCEE 和HKAL 考試中英文卷的proof-reading 部分題目有指示:

Do not make unnecessary changes.)

出乎意料的... 成功登入了!

金句: Make unnecessary changes when it is necessary! ![](images/umbrella.gif)
