<?php
// A standalone config-boundary check; no WordPress credentials or provider calls.
$mode=$argv[1]??'';if(!in_array($mode,['private','public','permissions','symlink','no-signing-key'],true))throw new Exception('Known fixture mode required');
$base='/scratch/config-check-'.$mode;@mkdir($base,0700);@mkdir($base.'/web',0700);define('ABSPATH',$base.'/web/');
$path=$mode==='public'?$base.'/web/upload.php':$base.'/upload.php';file_put_contents($path,"<?php return ['enabled'=>true,'library_id'=>'123456','library_key'=>'synthetic-library-key-not-real'];");chmod($path,$mode==='permissions'?0644:0600);
if($mode==='symlink'){symlink($path,$base.'/link.php');$path=$base.'/link.php';}
define('CAS_BUNNY_UPLOAD_CONFIG',$path);define('TEST_HAS_SIGNER',$mode!=='no-signing-key');function cas_video_token_key(){return TEST_HAS_SIGNER?'synthetic-playback-key':'';}
require '/target/bunny/includes.php';$result=(bool)CAS_Bunny_Uploader::enabled();if($result!==($mode==='private'))throw new Exception('Config boundary failed: '.$mode);echo $mode." passed\n";
