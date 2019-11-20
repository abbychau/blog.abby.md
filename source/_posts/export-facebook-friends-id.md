---
title: Export Facebook Friends list
date: 2019-11-20 18:00:27
tags:
- facebook
- api
- m2np
---
Facebook removed API to get your friend list.
Now:
1. Go to your logged in facebook.com
2. request token: `document.getElementsByName('fb_dtsg')[0].value`
3. your uid: `https://findmyfbid.in/`
4. https://www.facebook.com/ajax/typeahead/first_degree.php?__a=1&filter%5B0%5D=user&lazy=0&viewer=`your uid`&token=v7&stale_ok=0&options%5B0%5D=friends_only&options%5B1%5D=nm&fb_dtsg=`request token`

Do you share your request token to anyone.