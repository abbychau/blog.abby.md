

<?php
include("./vendor/autoload.php");
// $Parsedown = new Parsedown();
use Symfony\Component\Yaml\Yaml;

function process($file){
    $txt=file_get_contents($file);
    $ytxt=[];$mc=0;$store=[];
    foreach(explode("\n", $txt) as $line){
        if(trim($line) == "---"){
            if(++$mc==2){
                $_txt=implode("\n",$ytxt);
                $data=Yaml::parse($_txt);
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

foreach(glob("./_posts/*") as $file){
    $store[]=process($file);
}
foreach(glob("./output/*/*") as $v){
    $file = "$v/index.md";
    $store[]=process($file);
}

usort($store,function($a,$b){
    return $b['date'] <=> $a['date'];
});
$list='';
foreach($store as $v){
    $list.="&gt; <a onclick=\"go('{$v['path']}')\">{$v['date']} - {$v['title']}</a><br />";
}
$template = file_get_contents('template.htm');
$template = str_replace("{{list}}",$list,$template);
file_put_contents("index.htm",$template);

