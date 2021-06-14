

<?php
include("./vendor/autoload.php");
// $Parsedown = new Parsedown();
use Symfony\Component\Yaml\Yaml;
use WyriHaximus\HtmlCompress\Factory;


function process($file){
    $txt=file_get_contents($file);
    $ytxt=[];$mc=0;$store=[];
    foreach(explode("\n", $txt) as $line){
        if(trim($line) == "---"){
            if(++$mc==2){
                $_txt=implode("\n",$ytxt);
                try{
                    $data=Yaml::parse($_txt);
                }catch( Exception $e){
                    echo $file;
                    exit;
                }
                $data['path']=$file;
                if(is_numeric($data['date'])){
                    $data['date'] = date("Y-m-d",$data['date']);
                }
                return $data;
                break;
            }
            continue;
        }
        $ytxt[]=$line;
        
    }
    return null;
}
echo "Loading Files...\n";
foreach(glob("./_posts/*") as $file){
    $store[]=process($file);
}
foreach(glob("./output/*/*") as $v){
    $file = "$v/index.md";
    $store[]=process($file);
}
foreach(glob("./realblog/*/*") as $v){
    $file = $v;
    $store[]=process($file);
}
usort($store,function($a,$b){
    return $b['date'] <=> $a['date'];
});
$list='';
$prev07='';
echo "Generating List...\n";
foreach($store as $v){
    $sub07=substr($v['date'],0,7);
    $head = '';
    if($sub07 != $prev07){
        $prev07 = $sub07;
        $head = "$sub07<br />";
    }
    $title=$v['title']?$v['title']:'untitled';
    $list.="$head &gt; <a onclick=\"go('{$v['path']}')\">{$title}</a><br />";
}
$template = file_get_contents('template.htm');
$template = str_replace("{{list}}",$list,$template);
echo "Compressing...\n";
$parser = Factory::constructSmallest();
$compressedHtml = $parser->compress($template);

file_put_contents("index.htm",$compressedHtml);

//jf1.1
// {
//     "version": "https://jsonfeed.org/version/1.1",
//     "title": "My Example Feed",
//     "home_page_url": "https://example.org/",
//     "feed_url": "https://example.org/feed.json",
//     "items": [
//         {
//             "id": "2",
//             "content_text": "This is a second item.",
//             "url": "https://example.org/second-item"
//         },
//         {
//             "id": "1",
//             "content_html": "<p>Hello, world!</p>",
//             "url": "https://example.org/initial-post"
//         }
//     ]
// }
echo "Generating jsonfeed...\n";
$jsonfeed['version']="https://jsonfeed.org/version/1.1";
$jsonfeed['title']="Abby's Archive";
$jsonfeed['home_page_url']="https://blog.abby.md";
$jsonfeed['feed_url']="https://blog.abby.md/feed.json";
foreach($store as $v){
    $jsonfeed['items'][]=['id'=>md5($v['date']), 'url'=>"https://blog.abby.md/#{$v['path']}", 'title'=>$v['title'] ];
}
file_put_contents("feed.json",json_encode($jsonfeed));
echo "Done.\n";
