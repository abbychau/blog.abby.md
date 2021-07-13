

<?php
include("./vendor/autoload.php");
$Parsedown = new Parsedown();
use Symfony\Component\Yaml\Yaml;
use WyriHaximus\HtmlCompress\Factory;

function process($file){
    global $Parsedown;
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
                $ytxt=[];
                // return $data;
                // break;
            }
            continue;
        }
        $ytxt[]=$line;
    }
    $content=implode("\n",$ytxt);
    $markdownContent = $Parsedown->text($content);
    $cleanPath = "_generated/".trim(str_replace(['/','.md'],['_','.htm'],$data['path']),"._");
    $template = file_get_contents('templates/template_article.htm');
    $template = str_replace(
        ["{{subject}}","{{date}}","{{tags}}","{{markdown}}","{{paramlink}}"],
        [$data['title'],$data['date'],
        (isset($data['tags'])?json_encode($data['tags'],JSON_UNESCAPED_UNICODE):"").json_encode($data['categories'],JSON_UNESCAPED_UNICODE),$markdownContent,"https://blog.abby.md/{$cleanPath}"],
        $template
    );
    file_put_contents($cleanPath,$template);
    $out=['meta'=>$data,'content'=>$content,'markdown'=>$markdownContent,'generated_path'=>$cleanPath];
    return $out;
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
    return $b['meta']['date'] <=> $a['meta']['date'];
});
$list='';
$prev07='';
echo "Generating List...\n";
foreach($store as $v){
    $sub07=substr($v['meta']['date'],0,7);
    $head = '';
    if($sub07 != $prev07){
        $prev07 = $sub07;
        $head = "$sub07<br />";
    }
    $title=$v['meta']['title']?$v['meta']['title']:'untitled';
    $list.="$head &gt; <a href='{$v['generated_path']}' target='main_frame'>{$title}</a><br />";
}
$template = file_get_contents('templates/template_list.htm');
$template = str_replace("{{list}}",$list,$template);
// echo "Compressing...\n";
// $parser = Factory::constructSmallest();
// $compressedHtml = $parser->compress($template);

file_put_contents("list.htm",$template);

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
    $jsonfeed['items'][]=['id'=>md5($v['meta']['date']), 'url'=>"https://blog.abby.md/{$v['generated_path']}", 'title'=>$v['meta']['title'] ];
}
file_put_contents("feed.json",json_encode($jsonfeed));
echo "Done.\n";
