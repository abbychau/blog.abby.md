<?php 
include("./vendor/autoload.php");
use League\HTMLToMarkdown\HtmlConverter;

include("./includes/TranslateChinese.php");

$translator = new TranslateChinese();
$converter = new HtmlConverter();
foreach(glob("./original-data/zhihu/answer/*") as $filename){
    process($filename);
}

function process($filename){
    global $Parsedown,$translator,$converter;
    $strFile=file_get_contents($filename);
    $strFile=$translator->trad($strFile);
    $fileParts = explode("\n",$strFile);
    $dateLine = array_pop($fileParts);
    $titleLine = array_shift($fileParts);
    $content=implode("\n",$fileParts);
    // if(!intval($fileParts[2])){
    //     echo $filename."\n";
    //     print_r($fileParts);
    //     exit;
    // }
    $store=['title'=>$titleLine,'content'=>$content,'date'=>date("Y-m-d H:i",$dateLine)];//$converter->convert($v['content'])

    $txt=file_get_contents($filename);

    $cleanPath = "_generated_pages/answers/".str_replace(['/','.txt','zhihu','original-data','.','txt','answer'],'',$filename).".htm";

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