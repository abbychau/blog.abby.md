---
title: "解決不能在Windows Live Writer中使用 WordPress MU 1.5.1 的 wp_getUserBlogs錯誤"
date: "2008-06-30"
categories: 
  - "未分類"
---

###### [![clip_image001](http://blufiles.storage.msn.com/y1pWlC0zGOTFO19mYekKBlyBMPN71lmyVwuhObRcfNSAhliLFqDPO-idKEWcM1NH7To?PARTNER=WRITER)](http://blufiles.storage.msn.com/y1p1xTq-5z2vrJwtF_sey1-ZB4QvppXLDJ7EoGcOmroM8xqgD3l3wHB6qQgKKfQJ9W7?PARTNER=WRITER)一直使用 Microsoft Windows Live Writer 來更新自己的博客，感覺都不錯。可是在前幾天剛把自己所有的博客 WordPress MU 多用戶版1.3.3 升級到 1.5.1後，WLW卻不能好好地與MU合作了。具體是這樣的，等自己在 WLW 裏將文章寫好後，按”Publish“ 來發表，卻出現了一個 wp.getUsersBlogs 調用不存在的錯誤，言道要麼是用戶名出錯，或密碼不對。自己想到可能升級將我的用戶名和密碼搞亂了，所以趕緊登錄到後臺將用戶名和密碼統統更新了一把。再到WLW裏重新發表新文章，還是同樣的錯誤資訊。為了測試一下是不是僅僅撰寫出錯，我試著在WLW裏打開我博客上的文章，看到狀態條動了一會，最後也是一個 wp.getUsersBlogs 不存在的錯誤。看來 WLW 整個與 WordPress MU 1.5.1 就不相容了。

下面是我得到錯誤資訊的全文：

**log Server Error - Server Error -32601 Occurred server error. requested method wp.getUsersBlogs does not exist. You must correct this error before proceeding.**

當然不甘心就因為一個升級而放棄WLW，趕忙到 WP MU 官方支持網站上找對策。竟然發現這就是個MU新版本的bug（見有人提交的錯誤報告[http://trac.mu.wordpress.org/ticket/631](http://trac.mu.wordpress.org/ticket/631)）。 這個錯誤的罪魁禍首就是這個xmlrpc.php 文件。

要解決這個問題，主要需要改動兩個地方。首先，當然是用你的文本編輯器打開 xmlrpc.php 文件。

**第一、** 找到第94行，如下:

Line 94 ‘blogger.getUsersBlogs’ => ‘this:blogger\_getUsersBlogs’,

然後在它的前邊加上下面這行代碼：

‘wp.getUsersBlogs’=> ‘this:wp\_getUsersBlogs’,

**第二：**再到檔的753行，開始複製代碼到791行，然後回到代碼221行 “\* WordPress XML-RPC API” 處，粘帖整個剛才複製的代碼。對這些代碼稍作修改，就相當於插入一個新的 function 名叫 **wp\_getUserBlogs** 修改後的代碼如下：

              /\* wp\_getUsersBlogs    \*/   
                 function wp\_getUsersBlogs($args) {   
                         $this->escape($args);   
                         $username = $args\[0\];   
                         $password = $args\[1\];   
                         if (!$this->login\_pass\_ok($username, $password) )   
                                 return $this->error;   
                         do\_action(‘xmlrpc\_call’, ‘wp.getUsersBlogs’);   
                         $user = set\_current\_user(0, $username);   
                         $blogs = (array) get\_blogs\_of\_user($user->ID);   
                         $struct = array();   
                         foreach ( $blogs as $blog ) {   
              // Don’t include blogs that aren’t hosted at wordpress.com   
                                 if ( $blog->site\_id != 1 )   
                                         continue;   
                                 $blog\_id = $blog->userblog\_id;   
                                 switch\_to\_blog($blog\_id);   
                                 $is\_admin = current\_user\_can(‘level\_8′);   
                                 $struct\[\] = array(   
                                         ‘isAdmin’  => $is\_admin,   
                                         ‘url’         => get\_option(‘home’) . ‘/’,   
                                         ‘blogid’    => $blog\_id,   
                                       ‘blogName’  => get\_option(‘blogname’),   
                                   ‘xmlrpc’   =>get\_option(‘home’). ‘/xmlrpc.php’   
                                 );   
                         }   
                         return $struct;   
                 } 

**最後、**保存你的修改，再通過FTP上傳這個檔到你的伺服器，覆蓋原來的 xmlrpc.php文件，就行了！你又可以用你心愛的WLW來更新你的MU博客了！

當然，如果你不想直接更改xmlrpc.php檔，你可以[到這裏下載已經改好的 xmlrpc.php 文件](http://lichao.net/weblog/downloads/xmlrpc.php.txt)。另外，我關於這個問題的英文原文[可以在這裏找到](http://lichao.net/eblog/fix-the-publish-wpgetusersblogs-error-in-windows-live-writer-after-upgrading-to-wordpress-mu-151-200805146.html)。
