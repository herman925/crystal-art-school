<?php
// Synthetic provider fixture only. No outbound calls or real keys.
add_filter('cas_bunny_provider_response',static function($pre,$method,$video,$body){
 $path='/scratch/provider.json';$all=is_file($path)?json_decode(file_get_contents($path),true):[];
 if($method==='POST'){$video=sprintf('00000000-0000-4000-8000-%012d',count($all)+1);$all[$video]=['guid'=>$video,'videoLibraryId'=>123456,'status'=>0,'length'=>12,'availableResolutions'=>'720p','outputCodecs'=>'x264','encodeProgress'=>0,'title'=>$body['title']];file_put_contents($path,json_encode($all));}
 $value=$all[$video]??null;
 return ['response'=>['code'=>$value?200:404],'body'=>json_encode($value??[])];
},10,4);
add_filter('cas_bunny_test_upload_endpoint',static function(){return 'http://localhost:18089/fake-tus';});
add_filter('cas_bunny_test_preview',static function(){return 'http://localhost:18089/fixture-player.html';});
