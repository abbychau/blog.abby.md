<?php
include("./vendor/autoload.php");
use Mailgun\Mailgun;

$feed = 'https://www.plurk.com/abbychau.xml';
$arrFeeds = (array) simplexml_load_file($feed);
$arr=json_decode(json_encode($arrFeeds),true);
foreach($arr as $k=>&$v){
    if($k=='link'){
        $links=[];
        foreach($v as $item){
            $links[]=$item['@attributes']['href'];
        }
        $v=$links;
    }
    if($k=='entry'){
        foreach($v as &$item){
            $href=$item['link']['@attributes']['href'];
            $item['link']=trim($links[1],"/").$href;
            $item['author']=$item['author']['name'];
        }
    }
}
$str="";
foreach($arr['entry'] as $entry){
    if(date('Ymd') != date('Ymd', strtotime($entry['published']))){
        continue;
    }
    //echo $entry['id']."\n";
    $str.= $entry['published'];
    //$dt=strtotime($entry['published']);
    $str.=  "<br />\n";
    //echo $entry['author']."\n";
    $str.=  mb_substr($entry['content'],8,NULL,'utf8')."<br /><br />\n";
    $str.= "<a href='{$entry['link']}'>Link</a>";
    

    $str.=  "<hr>\n";
}
$targets = explode(",",getenv('EMAIL_TARGETS'));
foreach($targets as $v){
    mySendMail($v,$v,"Abby's Daily", $str);
}

function mySendMail($to_name, $to_email, $title, $content)
{
        $mg = Mailgun::create(getenv('MAILGUN_KEY')); // For US servers
        // $mg = Mailgun::create('key-example', 'https://api.eu.mailgun.net'); // For EU servers

        // Now, compose and send your message.
        // $mg->messages()->send($domain, $params);
        $mg->messages()->send('mail.m2np.com', [
                'from'    => 'abby <abby@mail.m2np.com>',
                'to'      => $to_email,
                'subject' => $title,
                'html'    => $content
        ]);
}
echo $str;