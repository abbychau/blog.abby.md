<?php 
include("./vendor/autoload.php");
use League\HTMLToMarkdown\HtmlConverter;

include("./includes/TranslateChinese.php");

$translator = new TranslateChinese();
$converter = new HtmlConverter();
$indexList = [];
$stores=[];
foreach(glob("./original-data/zhihu/answer/*") as $filename){
    process($filename);
}
function process($filename){
    global $Parsedown,$translator,$converter,$indexList,$stores;
    $strFile=file_get_contents($filename);
//    $strFile=$translator->trad($strFile);
//    file_put_contents($filename,$strFile);
    $fileParts = explode("\n",$strFile);
    $dateLine = trim(array_pop($fileParts));
    $titleLine = trim(array_shift($fileParts));
    $content=trim(implode("\n",$fileParts));
    // if(!intval($fileParts[2])){
    //     echo $filename."\n";
    //     print_r($fileParts);
    //     exit;
    // }
    if($dateLine[0]=="x"){
        echo "x";
        return;
    }
    if($dateLine[0]=="o"){
        echo "o";
        $dateLine=substr($dateLine,1);
    }else{
        echo ".";
    }
    $store=['title'=>$titleLine,'content'=>$content,'date'=>date("Y-m-d H:i",$dateLine)];//$converter->convert($v['content'])
    $stores[]=$store;
    $txt=file_get_contents($filename);

    $cleanPath = "_generated_pages/answers/".str_replace(['/','.txt','zhihu','original-data','.','txt','answer'],'',$filename).".htm";
    
    $indexList[] = "<li><a href='/$cleanPath'>{$store['title']}</a></li>";
    $template = file_get_contents('templates/template_html.htm');
    $template = str_replace(
        ["{{subject}}","{{date}}","{{tags}}","{{content}}","{{paramlink}}"],
        [$store['title'],$store['date'],"答案",$store['content'],"https://blog.abby.md/{$cleanPath}"],
        $template
    );
    file_put_contents($cleanPath,$template);
    // $out=['meta'=>$data,'content'=>$content,'markdown'=>$markdownContent,'generated_path'=>$cleanPath];
    // return $out;
}
$template = file_get_contents("./templates/template_meta.htm");

$strIndexList = "<ol>".implode("\n",$indexList)."</ol>";
file_put_contents("_generated_pages/list.htm",
    str_replace(["{{tag}}","{{list}}"],["知乎答案",$strIndexList],$template)
);

file_put_contents("zhihu_dump.json",json_encode($stores,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));