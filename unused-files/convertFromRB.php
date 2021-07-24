<?php
include("./vendor/autoload.php");
$Parsedown = new Parsedown();
use Symfony\Component\Yaml\Yaml;
use League\HTMLToMarkdown\HtmlConverter;

$converter = new HtmlConverter();

include("zb_contentpages.php");
$store=[];
foreach($zb_contentpages as $v){
    // print_r($v);
    if($v['isshow']==0){continue;}
    if(!$v['title']){continue;}
    if(!$v['content']){$v['content']=$v['title'];}
    $v['content'] = $v['content_markup']=='HTML'?$converter->convert($v['content']):$v['content'];
    $v['date']=$v['datetime'];
    $v['cate']=$v['type'];
    unset($v['datetime']);
    unset($v['commentnum']);
    unset($v['type']);
    unset($v['views']);
    // unset($v['id']);
    unset($v['ownerid']);
    unset($v['password']);
    unset($v['displaymode']);
    // unset($v['isshow']);
    // echo $$markdown;
    // $store[]=$v;
    $s7=substr($v['date'],0,7);
    $ft=$v['title'];
    if(stristr($v['title'], ":") || stristr($v['title'], ">")|| stristr($v['title'], "[")){
        $ft = "\"{$v['title']}\"";
    }
    $line = "---\n";
    $line .= "title: {$ft}\ndate: {$v['date']}\ncategories: {$v['cate']}\n";
    $line .= "---\n\n";
    $line .= $v['content'];
    if (!is_dir("./realblog/{$s7}")) {
        mkdir("./realblog/{$s7}",0777,true);
    }
    //get images
    //![](images/island.gif)
    // $re = '/!\[[^\]]*\]\((.*?)\s*("(?:.*[^"])")?\s*\)/';
    // preg_match_all($re, $v['content'], $matches);
    // if($matches[1]){
    //     foreach($matches[1] as $v){
    //         if (!is_dir("./realblog/{$s7}/{$v['id']}/images")) {
    //             mkdir("./realblog/{$s7}/{$v['id']}",0777,true);
    //         }
    //     };
    // }
    // echo "\n---\n\n";
    file_put_contents("./realblog/{$s7}/{$v['id']}.md",$line);
}
exit;



usort($store,function($a,$b){
    return $b['date'] <=> $a['date'];
});
$list='';
$prev07='';
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
file_put_contents("index.htm",$template);

