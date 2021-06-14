

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
foreach($store as $v){
    $sub07=substr($v['date'],0,7);
    $head = '';
    if($sub07 != $prev07){
        $prev07 = $sub07;
        $head = "$sub07<br />";
    }
    // echo $v['path'];echo "\n";
    $title=$v['title']?$v['title']:'untitled';
    $list.="$head &gt; <a onclick=\"go('{$v['path']}')\">{$title}</a><br />";
}
$template = file_get_contents('template.htm');
$template = str_replace("{{list}}",$list,$template);
file_put_contents("index.htm",$template);

