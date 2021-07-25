

<?php
include("./vendor/autoload.php");
$Parsedown = new Parsedown();
use Symfony\Component\Yaml\Yaml;
use WyriHaximus\HtmlCompress\Factory;

$pickupList = [];
$tagToArticles=[];
$store=[];
function process($file){
    global $Parsedown,$pickupList,$tagToArticles,$store;
    $txt=file_get_contents($file);
    $ytxt=[];$mc=0;
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
    $cleanPath = "_generated/".trim(str_replace(['original-data','/','.md'],['','_','.htm'],$data['path']),"._");
    $template = file_get_contents('templates/template_article.htm');
    if(isset($data['tags'])){
        if(!is_array($data['tags'])){
            $data['tags'] = [$data['tags']];
        }
    }else{
        $data['tags'] = [];
    }
    if(isset($data['categories'])){
        if(!is_array($data['categories'])){
            $data['categories'] = [$data['categories']];
        }
    }else{
        $data['categories'] = [];
    }
    $tags = array_merge($data['tags'],$data['categories']);
    $tags = array_unique($tags);
    if(in_array('omit',$tags)){
        echo "omit!\n";
        return;
    }
    $strTagLinks=[];
    foreach($tags as $tag){
        $tagToArticles[$tag][]=['title'=>$data['title'],'url'=>$cleanPath];
        $file = sanTag($tag);
        $strTagLinks[] = "<a href='/_meta/{$file}.htm'>$tag</a>";
    }
    $template = str_replace(
        ["{{subject}}","{{date}}","{{tags}}","{{markdown}}","{{paramlink}}","{{source}}"],
        [
            $data['title'],$data['date'],implode(", ",$strTagLinks),$markdownContent,
            "https://blog.abby.md/{$cleanPath}",
            "https://github.com/abbychau/blog.abby.md/blob/master/{$data['path']}"
        ],
        $template
    );
    file_put_contents($cleanPath,$template);
    $store[]=['meta'=>$data,'content'=>$content,'markdown'=>$markdownContent,'generated_path'=>$cleanPath];
}
echo "Loading Files...\n";
foreach(glob("./original-data/hexo/*") as $file){
    process($file);
}
foreach(glob("./original-data/msn-space/*/*") as $v){
    $file = "$v/index.md";
    process($file);
}
foreach(glob("./original-data/realblog/*/*") as $v){
    process($v);
}
usort($store,function($a,$b){
    return $b['meta']['date'] <=> $a['meta']['date'];
});
$strArchive='';$strPickup='';
$prev07='';
echo "Generating List...\n";
foreach($store as $v){
    if(!$v){continue;}
    $sub07=substr($v['meta']['date'],0,7);
    $head = '';
    if($sub07 != $prev07){
        $prev07 = $sub07;
        $head = "$sub07<br />";
    }
    $title=$v['meta']['title']?$v['meta']['title']:'untitled';
    $strArchive.="$head &gt; <a href='{$v['generated_path']}' target='main_frame'>{$title}</a><br />\n";
}
foreach($tagToArticles['pick-up'] as $v){
    $strPickup.="$head &gt; <a href='{$v['url']}' target='main_frame'>{$v['title']}</a><br />\n";
}
$template = file_get_contents('templates/template_list.htm');
$template = str_replace("{{list-archive}}",$strArchive,$template);
$template = str_replace("{{list-pickup}}",$strPickup,$template);
// echo "Compressing...\n";
// $parser = Factory::constructSmallest();
// $compressedHtml = $parser->compress($template);

file_put_contents("list.htm",$template);

echo "Generating Meta List...\n";
$files = glob('./_meta/*'); // get all file names
foreach($files as $file){ // iterate files
  if(is_file($file)) {
    unlink($file); // delete file
  }
}
$template = file_get_contents('templates/template_meta.htm');
function sanTag($tag){
    // Thanks @Łukasz Rysiak!
    $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $tag);
    // Remove any runs of periods (thanks falstro!)
    $file = mb_ereg_replace("([\.]{2,})", '', $file);
    return strtolower($file);
}
$metaIndexStr="<ul>";
foreach($tagToArticles as $tag=>$list){
    $strSave = str_replace("{{tag}}",$tag,$template);    
    $_str="<ul>";
    foreach($list as $item){
        $_str.="<li><a target='_self' href='/{$item['url']}'>{$item['title']}</a></li>\n";
    }
    $_str.="</ul>";

    $file=sanTag($tag);
    $strSave = str_replace("{{list}}",$_str,$strSave);  
    file_put_contents("_meta/{$file}.htm",$strSave);
    $metaIndexStr.="<li><a target='_self' href='/_meta/{$file}.htm'>$tag</a></li>";
}
$metaIndexStr.="</ul>";
$metaIndexTemplate = file_get_contents("templates/template_meta_index.htm");
$metaIndexStr=str_replace("{{list}}",$metaIndexStr,$metaIndexTemplate);
file_put_contents("_meta/index.htm",$metaIndexStr);

echo "Generating jsonfeed...\n";
$jsonfeed['version']="https://jsonfeed.org/version/1.1";
$jsonfeed['title']="Abby's Archive";
$jsonfeed['home_page_url']="https://blog.abby.md";
$jsonfeed['feed_url']="https://blog.abby.md/feed.json";
foreach($store as $v){
    $jsonfeed['items'][]=['id'=>md5($v['meta']['date']), 'url'=>"https://blog.abby.md/{$v['generated_path']}", 'title'=>$v['meta']['title'] ];
}
file_put_contents("feed.json",json_encode($jsonfeed, JSON_PRETTY_PRINT));
echo "Done.\n";
