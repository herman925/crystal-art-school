/* Uses Tutor's supported content/field slots; the named-input bridge is tested against the native save payload. */
(function () {
 'use strict';
 var el=wp.element.createElement, useState=wp.element.useState, useEffect=wp.element.useEffect, useRef=wp.element.useRef;
 var cfg=window.casBunnyEditor;
 function api(path,body){return fetch(cfg.root+path,{method:body===undefined?'GET':'POST',credentials:'same-origin',headers:{'Content-Type':'application/json','X-WP-Nonce':cfg.nonce},body:body===undefined?undefined:JSON.stringify(body)}).then(async function(r){var d=await r.json();if(!r.ok)throw new Error(d.message||'The video operation could not be verified.');return d;});}
 function input(){return document.querySelector('input[name="_cas_bunny_edit"]');}
 function bridge(value){var node=input();if(!node)throw new Error('Reopen the lesson before selecting its video.');Object.getOwnPropertyDescriptor(HTMLInputElement.prototype,'value').set.call(node,value);node.dispatchEvent(new Event('input',{bubbles:true}));node.dispatchEvent(new Event('change',{bubbles:true}));}
 function duration(n){return Math.floor(n/60)+':'+String(n%60).padStart(2,'0');}
 function random(){var a=new Uint8Array(16);crypto.getRandomValues(a);return Array.from(a,function(n){return n.toString(16).padStart(2,'0');}).join('');}
 function Video(){
  var ctx=useRef(null),active=useRef(null),upload=useRef(null),timer=useRef(null),fileInput=useRef(null),alive=useRef(true),last=useRef(''),generation=useRef(0);
  var state=useState({message:'Choose a film for this lesson.',busy:false,current:null,candidate:null,library:null,error:''}),s=state[0],set=state[1];
  function update(v){if(alive.current)set(function(old){return Object.assign({},old,v);});}
  function title(){var n=document.querySelector('input[name="title"]');return n?n.value.trim():'';}
  function pending(id){for(var i=0;i<localStorage.length;i++){var k=localStorage.key(i);if(k.indexOf('cas-bunny-pending:'+cfg.user+':'+cfg.course+':')!==0)continue;try{var v=JSON.parse(localStorage.getItem(k));if(v&&v.id===id)return {key:k,value:v};}catch(e){}}return null;}
  function key(){return 'cas-bunny-pending:'+cfg.user+':'+cfg.course+':'+(ctx.current&&ctx.current.lesson||0);}
  async function context(){if(ctx.current)return ctx.current;var data=await api('contexts',{course:cfg.course,lesson:0});ctx.current={id:data.id,revision:data.revision,lesson:0};update({current:data.current});return ctx.current;}
  function stillHere(g){return alive.current&&g===generation.current;}
  async function select(which,job){var g=generation.current,c=await context();if(!stillHere(g))return;var d=await api('contexts/'+c.id+'/selection',{selection:which,video:job?job.id:''});if(!stillHere(g))return;c.revision=d.revision;last.current=c.id+':'+d.revision;bridge(last.current);update({candidate:which==='use'?job:null,library:null,error:'',message:which==='use'?'Ready — preview, then save this lesson to use the film.':which==='remove'?'The film will be removed from this lesson when you save.':'The currently saved film will be kept.'});}
  async function poll(id){
   var g=generation.current;clearTimeout(timer.current);try{var j=await api('uploads/'+id);if(!alive.current||g!==generation.current)return;active.current=j;
    if(j.state==='ready'){var data=pending(id);if(data)localStorage.removeItem(data.key);await select('use',j);update({busy:false,message:'Ready — save this lesson to use the film.'});return;}
    if(j.state==='uploading'){update({busy:false,message:'Bunny has not confirmed receipt. Check status again, or resume with the same file.'});return;}
    if(['failed','cancelled','unknown'].indexOf(j.state)>=0){update({busy:false,error:j.state==='unknown'?'Upload creation needs an administrator check. Do not submit the same film repeatedly.':j.state==='failed'?'Bunny could not process this film. The current video is unchanged.':'Upload cancelled.',message:''});return;}
    update({message:'Processing — Bunny is preparing playback. The current saved film is unchanged.'});timer.current=setTimeout(function(){poll(id);},5000);
   }catch(e){update({busy:false,error:e.message,message:'Check status before starting another upload.'});}
  }
  async function start(file,resume){
   if(!file)return;if(!title()){update({error:'Enter a lesson name first.'});return;}
   try{var g=++generation.current,c=await context();if(!stillHere(g))return;update({busy:true,error:'',candidate:null,library:null,message:'Preparing upload…'});
    var saved=resume&&active.current?pending(active.current.id):null;var old=resume?(saved?saved.value:JSON.parse(localStorage.getItem(key())||'null')):null;if(resume&&!old)throw new Error('No matching transfer was found in this browser. Check status or ask the administrator before starting again.');
    if(old&&(old.name!==file.name||old.size!==file.size||old.modified!==file.lastModified))throw new Error('Choose the same original file to resume.');
    var job=old?await api('uploads/'+old.id):await api('uploads',{context:c.id,title:title(),request:random()});if(!stillHere(g))return;active.current=job;
    if(job.state==='ready'){await select('use',job);update({busy:false});return;}
    if(['unknown','failed','cancelled','creating'].indexOf(job.state)>=0)throw new Error('This upload is not available for transfer. Check its status or contact the administrator.');
    localStorage.setItem(key(),JSON.stringify({id:job.id,name:file.name,size:file.size,modified:file.lastModified}));
    var auth=await api('uploads/'+job.id+'/authorisation',{context:c.id});if(!stillHere(g))return;
    upload.current=new tus.Upload(file,{endpoint:auth.endpoint,chunkSize:8*1024*1024,retryDelays:[0,1000,3000,5000,10000],removeFingerprintOnSuccess:true,
     fingerprint:function(){return Promise.resolve('cas-bunny:'+job.id+':'+file.size+':'+file.lastModified);},
     headers:{AuthorizationSignature:auth.signature,AuthorizationExpire:String(auth.expires),LibraryId:auth.library,VideoId:auth.video},metadata:{filetype:file.type||'application/octet-stream',title:title()},
     onProgress:function(done,total){if(g!==generation.current)return;update({message:'Uploading — '+Math.floor(done/total*100)+'%. Keep this tab open.'});},
     onError:function(){if(g!==generation.current)return;update({busy:false,message:'Connection interrupted.',error:'The transfer stopped. Choose the same file with Resume upload, or check status. The saved lesson video is unchanged.'});},
     onSuccess:function(){if(g!==generation.current)return;update({message:'Upload complete. Processing…'});poll(job.id);}});
    var previous=await upload.current.findPreviousUploads();if(!stillHere(g))return;if(previous.length)upload.current.resumeFromPreviousUpload(previous[0]);upload.current.start();
   }catch(e){update({busy:false,error:e.message});}
  }
  async function cancel(){try{++generation.current;if(upload.current)await upload.current.abort(false);clearTimeout(timer.current);if(active.current)await api('uploads/'+active.current.id+'/cancel',{});var stored=active.current&&pending(active.current.id);if(stored)localStorage.removeItem(stored.key);active.current=null;update({busy:false,candidate:null,error:'',message:'Upload cancelled. The saved lesson video is unchanged.'});}catch(e){update({busy:false,error:e.message});}}
  async function library(){try{var g=generation.current;await context();if(!stillHere(g))return;var rows=await api('library?course='+cfg.course);if(!stillHere(g))return;update({library:rows,error:''});}catch(e){update({error:e.message});}}
  useEffect(function(){alive.current=true;var attempts=0;var tick=setInterval(function(){var n=input();if(!n||!n.value){if(++attempts>20)clearInterval(tick);return;}clearInterval(tick);var a=n.value.split(':');if(!/^[a-f0-9]{32}$/.test(a[0]))return;ctx.current={id:a[0],revision:Number(a[1]||0)};last.current=n.value;api('contexts/'+a[0]).then(function(d){ctx.current.lesson=d.lesson||0;active.current=d.pending||null;update({current:d.current,message:d.pending?'An unfinished upload was found. Check status, or resume with the same file.':'Choose a film for this lesson.'});}).catch(function(e){update({error:e.message});});},25);
   var reset=setInterval(function(){var n=input();if(n&&last.current&&n.value===ctx.current.id+':0'&&last.current!==n.value){last.current=n.value;++generation.current;clearTimeout(timer.current);if(upload.current)upload.current.abort(false);update({busy:false,candidate:null,message:'The saved film is unchanged. Choose a video again if needed.'});}},300);
   return function(){alive.current=false;++generation.current;clearInterval(tick);clearInterval(reset);clearTimeout(timer.current);if(upload.current)upload.current.abort(false);};
  },[]);
  var candidate=s.candidate,preview=candidate&&candidate.preview||s.current&&s.current.preview;
  return el('section',{className:'cas-bunny-uploader','aria-label':'Lesson video'},
   el('h3',null,'Lesson video'),el('p',null,'Videos upload directly to Bunny. Save this lesson to use a ready film.'),
   preview?el('iframe',{src:preview,title:'Lesson video preview',allow:'fullscreen;picture-in-picture',allowFullScreen:true,loading:'lazy'}):s.current&&s.current.has_video?el('p',null,'An existing film is saved. It will be kept unless you replace or remove it.'):null,
   candidate&&candidate.duration?el('p',null,'Video duration: '+duration(candidate.duration)):s.current&&s.current.duration?el('p',null,'Saved duration: '+duration(s.current.duration)):null,
   el('p',{role:'status','aria-live':'polite'},s.message),s.error?el('p',{role:'alert',className:'cas-bunny-error'},s.error):null,
   el('div',{className:'cas-bunny-actions'},el('button',{type:'button',disabled:s.busy,onClick:function(){fileInput.current.dataset.resume='';fileInput.current.click();}},'Upload video'),el('button',{type:'button',disabled:s.busy,onClick:library},'Choose existing'),el('button',{type:'button',disabled:s.busy,onClick:function(){fileInput.current.dataset.resume='yes';fileInput.current.click();}},'Resume upload'),
    active.current?el('button',{type:'button',disabled:s.busy,onClick:function(){poll(active.current.id);}},'Check status'):null,(s.busy||active.current&&['creating','uploading','processing','failed','unknown'].indexOf(active.current.state)>=0)?el('button',{type:'button',onClick:cancel},'Cancel upload'):null,
    s.current&&s.current.has_video?el('button',{type:'button',disabled:s.busy,onClick:function(){select('remove').catch(function(e){update({error:e.message});});}},'Remove from lesson'):null,
    candidate?el('button',{type:'button',onClick:function(){select('keep').catch(function(e){update({error:e.message});});}},'Keep saved video'):null),
   el('input',{type:'file',accept:'video/*',hidden:true,ref:fileInput,onChange:function(e){var f=e.target.files[0],r=e.target.dataset.resume==='yes';e.target.value='';start(f,r);}}),
   s.library?el('div',{className:'cas-bunny-library'},el('p',null,s.library.length?'Choose a ready film:':'No approved ready films are indexed yet.'),s.library.map(function(j){return el('button',{key:j.id,type:'button',onClick:function(){select('use',j).catch(function(e){update({error:e.message});});}},(j.title||'Lesson film')+(j.duration?' · '+duration(j.duration):''));})):null);
 }
 var attempts=0;function register(){var lesson=window.Tutor&&Tutor.CourseBuilder&&Tutor.CourseBuilder.Curriculum.Lesson;if(!lesson){if(++attempts<100)setTimeout(register,100);return;}lesson.registerField('bottom_of_sidebar',{name:'_cas_bunny_edit',type:'text',defaultValue:''});lesson.registerContent('bottom_of_sidebar',{component:el(Video)});}register();
})();
