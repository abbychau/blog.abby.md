<?php
include("./vendor/autoload.php");
$Parsedown = new Parsedown();
use Symfony\Component\Yaml\Yaml;
use WyriHaximus\HtmlCompress\Factory;

$tagToArticles=[];
$store=[];
$Parsedown->setBreaksEnabled(true);
function process($file){
    global $Parsedown,$tagToArticles,$store;
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
<<<<<<< HEAD
    $cleanPath = "_generated/".trim(str_replace(['original-data','/','.md'],['','_','.htm'],$data['path']),"._");
    $articleTemplate = file_get_contents('templates/template_article.htm');
=======
    $cleanPath = "posts/".trim(str_replace(['original-data','/','.md'],['','_','.htm'],$data['path']),"._");
    $template = file_get_contents('templates/template_article.htm');
>>>>>>> 2f9c64d3d169d1be58c1de59648ffcb731b973b7
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
        $tagToArticles[$tag][]=['title'=>$data['title'],'url'=>"/".$cleanPath];
        $file = sanTag($tag);
        $strTagLinks[] = "<a href='/tags/{$file}.htm'>$tag</a>";
    }
    $mixdownContent = str_replace(
        ["{{subject}}","{{date}}","{{tags}}","{{markdown}}","{{paramlink}}","{{source}}"],
        [
            $data['title'],$data['date'],implode(", ",$strTagLinks),$markdownContent,
            "https://blog.abby.md/{$cleanPath}",
            "https://github.com/abbychau/blog.abby.md/blob/master/{$data['path']}"
        ],
        $articleTemplate
    );
    if($data['title']==""){
        echo $data['path'];
        exit;
    }
    file_put_contents($cleanPath, $mixdownContent);
    $store[]=['meta'=>$data,'content'=>$content,'markdown'=>$markdownContent,'generated_path'=>$cleanPath];
}
echo "Loading Files...\n";
foreach(glob("./original-data/hexo/*") as $file){
    process($file);
}
foreach(glob("./original-data/blog/*") as $file){
    process($file);
}
foreach(glob("./original-data/msn-space/*/*") as $v){
    $file = "$v/index.md";
    process($file);
}
foreach(glob("./original-data/realblog/*/*") as $v){
    // $txt=file_get_contents($v);
    // //strip tags
    // $txt = str_replace(["<p>","</p>","<br>","<br/>","<br />","<tr>","</tr>","<div>","</div>"],"\n",$txt);
    // $txt = strip_tags($txt);
    // echo $v . "\n";
    // file_put_contents($v,$txt);
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
    $strArchive.="$head &gt; <a href='/{$v['generated_path']}'>{$title}</a><br />\n";
}
foreach($tagToArticles['pick-up'] as $v){
    $strPickup.="&gt; <a href='{$v['url']}'>{$v['title']}</a><br />\n";
}
$template = file_get_contents('templates/template_list.htm');
$listMixdown = str_replace(["{{list-archive}}","{{list-pickup}}"],[$strArchive,$strPickup],$template);
// echo "Compressing...\n";
// $parser = Factory::constructSmallest();
// $compressedHtml = $parser->compress($template);

//file_put_contents("list.htm",$listMixdown);

echo "Generating Meta List...\n";
$files = glob('./tags/*'); // get all file names
foreach($files as $file){ // iterate files
  if(is_file($file)) {
    unlink($file); // delete file
  }
}
$template = file_get_contents('templates/template_tags.htm');
function sanTag($tag){
    // Thanks @Łukasz Rysiak!
    $file = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $tag);
    // Remove any runs of periods (thanks falstro!)
    $file = mb_ereg_replace("([\.]{2,})", '', $file);
    return strtolower($file);
}
$metaIndexStr="<ul>";
uasort($tagToArticles,function($a,$b){
    return sizeof($b) <=> sizeof($a);
});
foreach($tagToArticles as $tag=>$list){
    $strSave = str_replace("{{tag}}",$tag,$template);    
    $_str="<ul>";
    foreach($list as $item){
        $_str.="<li><a target='_self' href='/{$item['url']}'>{$item['title']}</a></li>\n";
    }
    $_str.="</ul>";

    $file=sanTag($tag);
<<<<<<< HEAD
    $strSave = str_replace(["{{content}}","{{listMixdown}}"],[$_str,$listMixdown],$strSave);  
    file_put_contents("_meta/{$file}.htm",$strSave);
=======
    $strSave = str_replace("{{list}}",$_str,$strSave);  
    file_put_contents("tags/{$file}.htm",$strSave);
>>>>>>> 2f9c64d3d169d1be58c1de59648ffcb731b973b7
    $len=sizeof($list);
    $metaIndexStr.="<li><a target='main_frame' href='/tags/{$file}.htm'>$tag</a>($len)</li>";
}
$metaIndexStr.="</ul>";
<<<<<<< HEAD
$metaIndexTemplate = file_get_contents("templates/template_meta_index.htm");
$tagMixdown=str_replace(["{{content}}","{{listMixdown}}"],[$metaIndexStr,$listMixdown],$metaIndexTemplate);
file_put_contents("_meta/index.htm",$tagMixdown);

// Replace {{#documents}} in _generated folder
// foreach file in _generated folder
foreach(glob("./_generated/*") as $file){
    $content = file_get_contents($file);
    $content = str_replace("{{listMixdown}}",$listMixdown,$content);

    file_put_contents($file,$content);
}
=======
$metaIndexTemplate = file_get_contents("templates/template_tags_index.htm");
$metaIndexStr=str_replace("{{list}}",$metaIndexStr,$metaIndexTemplate);
file_put_contents("tags/index.htm",$metaIndexStr);
>>>>>>> 2f9c64d3d169d1be58c1de59648ffcb731b973b7

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
