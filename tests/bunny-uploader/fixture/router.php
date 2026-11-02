<?php
$p=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(str_starts_with($p,'/fake-tus')){
 header('Tus-Resumable: 1.0.0');$id=basename($p);
 if($_SERVER['REQUEST_METHOD']==='POST'){$id=bin2hex(random_bytes(8));$guid=$_SERVER['HTTP_VIDEOID']??'';file_put_contents('/scratch/tus-'.$id.'.json',json_encode(['guid'=>$guid,'offset'=>0,'length'=>(int)($_SERVER['HTTP_UPLOAD_LENGTH']??0)]));header('Location: http://localhost:18089/fake-tus/'.$id);http_response_code(201);exit;}
 $file='/scratch/tus-'.$id.'.json';if(!is_file($file)){http_response_code(404);exit;}$v=json_decode(file_get_contents($file),true);
 if($_SERVER['REQUEST_METHOD']==='HEAD'){header('Upload-Offset: '.$v['offset']);header('Upload-Length: '.$v['length']);http_response_code(200);exit;}
 if($_SERVER['REQUEST_METHOD']==='PATCH'){if((int)($_SERVER['HTTP_UPLOAD_OFFSET']??-1)!==$v['offset']){http_response_code(409);exit;}$v['offset']+=strlen(file_get_contents('php://input'));file_put_contents($file,json_encode($v));if($v['offset']>=$v['length']){$all=json_decode(file_get_contents('/scratch/provider.json'),true);$all[$v['guid']]['status']=4;$all[$v['guid']]['encodeProgress']=100;file_put_contents('/scratch/provider.json',json_encode($all));}header('Upload-Offset: '.$v['offset']);http_response_code(204);exit;}
 http_response_code(405);exit;
}
if(is_file('/scratch/site'.$p))return false;
require '/scratch/site/index.php';
