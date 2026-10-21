'use strict';
function selectByText(selector,text) { const select=document.querySelector(selector); if(!select)return; const option=[...select.options].find(o=>o.textContent===text); if(option)select.value=option.value; }
const videoDialog=document.querySelector('#video-dialog');
const vimeoVideos={"2129128044360416": "1236786561", "2013840505981997": "1236786559", "1683918787070180": "1236786562", "1122351437148430": "1236786560"};
function vimeoSource(button){const source=button.dataset.src || '';if(/^https:\/\/(?:www\.)?vimeo\.com\//.test(source)||/^https:\/\/player\.vimeo\.com\/video\//.test(source))return source;return !source && vimeoVideos[button.dataset.reel]?'https://player.vimeo.com/video/'+vimeoVideos[button.dataset.reel]:null;}

const preparedVideos=new Map(), playerShells=new WeakMap();
function prepareVideo(button){
 const source=button.dataset.src || '/assets/reel-'+button.dataset.reel+'.mp4?v=20261017';
 if(!preparedVideos.has(source)){
  const video=document.createElement('video');video.playsInline=true;video.preload='metadata';video.src=source;
  preparedVideos.set(source,video);
  if(preparedVideos.size>2){const oldest=preparedVideos.keys().next().value;const previous=preparedVideos.get(oldest);if(!previous.isConnected){previous.removeAttribute('src');previous.load();preparedVideos.delete(oldest);}}
 }
 const video=preparedVideos.get(source);video.poster=button.dataset.poster || '/assets/reel-'+button.dataset.reel+'.jpg';video.setAttribute('aria-label',button.dataset.title || 'فيديو الدكتور');return video;
}

function createPlayer(video){
 if(playerShells.has(video))return playerShells.get(video);
 const paths={play:'M8 5l11 7-11 7z',pause:'M8 5v14M16 5v14',sound:'M11 5L6 9H3v6h3l5 4z M15 8a6 6 0 010 8M18 5a10 10 0 010 14',muted:'M11 5L6 9H3v6h3l5 4z M16 9l6 6M22 9l-6 6',expand:'M8 3H3v5M16 3h5v5M3 16v5h5M21 16v5h-5',pip:'M3 4h18v16H3z M12 11h7v7h-7z'};
 const icon=name=>'<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'+paths[name]+'"'+(name==='play'?' fill="currentColor" stroke="none"':'')+'/></svg>';
 video.controls=false;
 const shell=document.createElement('div');shell.className='modern-player';shell.tabIndex=0;shell.setAttribute('aria-label','مشغل فيديو د. محمد هلال');video.parentNode.insertBefore(shell,video);shell.append(video);
 const overlay=document.createElement('button');overlay.className='player-center';overlay.type='button';overlay.setAttribute('aria-label','تشغيل الفيديو');overlay.innerHTML=icon('play');shell.append(overlay);
 const bar=document.createElement('div');bar.className='player-controls';bar.innerHTML='<div class="player-progress"><input class="seek" type="range" min="0" max="100" step="0.1" value="0" aria-label="تقديم الفيديو"></div><div class="player-toolbar"><button class="toggle" type="button" aria-label="تشغيل أو إيقاف الفيديو">'+icon('play')+'</button><button class="rewind" type="button" aria-label="رجوع 10 ثوانٍ"><svg viewBox="0 0 24 24" width="25" height="25" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7a8 8 0 111 12M5 3v5h5"/></svg><small>10</small></button><button class="forward" type="button" aria-label="تقديم 10 ثوانٍ"><svg viewBox="0 0 24 24" width="25" height="25" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 7a8 8 0 10-1 12M19 3v5h-5"/></svg><small>10</small></button><span class="player-time">0:00 / 0:00</span><button class="mute" type="button" aria-label="كتم الصوت" aria-pressed="false">'+icon('sound')+'</button><select class="speed" aria-label="سرعة التشغيل"><option value="0.75">0.75×</option><option value="1" selected>1×</option><option value="1.25">1.25×</option><option value="1.5">1.5×</option><option value="2">2×</option></select><button class="pip" type="button" aria-label="صورة داخل صورة">'+icon('pip')+'</button><button class="fullscreen" type="button" aria-label="ملء الشاشة">'+icon('expand')+'</button></div>';shell.append(bar);
 const status=document.createElement('p');status.className='player-status';status.setAttribute('role','status');status.hidden=true;shell.append(status);
 const showStatus=text=>{status.textContent=text;status.hidden=!text;};
 const play=()=>video.play().catch(error=>{if(error.name!=='AbortError')showStatus(error.name==='NotAllowedError'?'اضغطي تشغيل لبدء الفيديو.':'تعذر تشغيل الفيديو. حاولي مرة أخرى.');});
 video.addEventListener('error',()=>showStatus('تعذر تحميل الفيديو. تحققي من الاتصال وحاولي مرة أخرى.'));
 video.addEventListener('waiting',()=>showStatus('جارِ تحميل الفيديو…'));
 for(const event of ['playing','canplay'])video.addEventListener(event,()=>showStatus(''));
 const toggle=()=>video.paused?play():video.pause();overlay.addEventListener('click',toggle);video.addEventListener('click',toggle);bar.querySelector('.toggle').addEventListener('click',toggle);
 const seek=delta=>{if(Number.isFinite(video.duration))video.currentTime=Math.min(video.duration,Math.max(0,video.currentTime+delta));};
 bar.querySelector('.rewind').addEventListener('click',()=>seek(-10));bar.querySelector('.forward').addEventListener('click',()=>seek(10));
 const format=t=>Math.floor((t||0)/60)+':'+String(Math.floor((t||0)%60)).padStart(2,'0');
 const update=()=>{bar.querySelector('.player-time').textContent=format(video.currentTime)+' / '+format(video.duration);const progress=video.duration?video.currentTime/video.duration*100:0;bar.querySelector('.seek').value=progress;shell.style.setProperty('--played',progress+'%');const end=video.buffered.length?video.buffered.end(video.buffered.length-1):0;shell.style.setProperty('--buffered',(video.duration?end/video.duration*100:0)+'%');};
 for(const event of ['timeupdate','loadedmetadata','progress'])video.addEventListener(event,update);
 video.addEventListener('play',()=>{shell.classList.add('is-playing','has-started');bar.querySelector('.toggle').innerHTML=icon('pause');overlay.setAttribute('aria-label','إيقاف الفيديو');});
 video.addEventListener('pause',()=>{shell.classList.remove('is-playing');bar.querySelector('.toggle').innerHTML=icon('play');overlay.setAttribute('aria-label','تشغيل الفيديو');});
 video.addEventListener('ended',()=>{showStatus('انتهى الفيديو · اضغطي التشغيل للمشاهدة مرة أخرى');});
 bar.querySelector('.seek').addEventListener('input',e=>{if(Number.isFinite(video.duration))video.currentTime=Number(e.target.value)/100*video.duration;});
 const mute=()=>{video.muted=!video.muted;};bar.querySelector('.mute').addEventListener('click',mute);
 video.addEventListener('volumechange',()=>{bar.querySelector('.mute').innerHTML=icon(video.muted?'muted':'sound');bar.querySelector('.mute').setAttribute('aria-pressed',String(video.muted));bar.querySelector('.mute').setAttribute('aria-label',video.muted?'تشغيل الصوت':'كتم الصوت');});
 bar.querySelector('.speed').addEventListener('change',e=>video.playbackRate=Number(e.target.value));
 const full=async()=>{try{if(document.fullscreenElement)await document.exitFullscreen();else if(shell.requestFullscreen)await shell.requestFullscreen();else if(video.webkitEnterFullscreen)video.webkitEnterFullscreen();else shell.classList.toggle('expanded-player');}catch{shell.classList.toggle('expanded-player');}};
 bar.querySelector('.fullscreen').addEventListener('click',full);
 const pip=bar.querySelector('.pip');pip.hidden=!document.pictureInPictureEnabled;pip.addEventListener('click',async()=>{try{if(document.pictureInPictureElement)await document.exitPictureInPicture();else await video.requestPictureInPicture();}catch{showStatus('وضع الصورة المصغرة غير متاح في هذا المتصفح.');}});
 shell.addEventListener('keydown',e=>{if(e.target!==shell)return;if(e.key===' '){e.preventDefault();toggle();}if(e.key==='ArrowRight'){e.preventDefault();seek(10);}if(e.key==='ArrowLeft'){e.preventDefault();seek(-10);}if(e.key==='m')mute();if(e.key==='f')full();});
 playerShells.set(video,shell);return shell;
}

document.querySelectorAll('dialog').forEach(dialog=>{dialog.querySelector('.close')?.addEventListener('click',()=>{if(dialog===videoDialog)document.querySelector('#video-container')?.replaceChildren();dialog.close();});dialog.addEventListener('click',event=>{if(event.target===dialog)dialog.close();});});
document.querySelectorAll('video').forEach(video=>createPlayer(video));
document.querySelectorAll('[data-reel]').forEach(button=>{
 const warm=()=>{if(!vimeoSource(button) && (button.dataset.reel || button.dataset.src) && !navigator.connection?.saveData)prepareVideo(button);};
 button.addEventListener('pointerenter',warm);button.addEventListener('focus',warm);button.addEventListener('pointerdown',warm,{passive:true});
 button.addEventListener('click',()=>{
 if(!videoDialog || (!button.dataset.reel && !button.dataset.src))return;
 document.querySelectorAll('video').forEach(v=>v.pause());
 const external=vimeoSource(button);
 if(external){
  const match=external.match(/^https:\/\/(?:player\.vimeo\.com\/video\/|(?:www\.)?vimeo\.com\/)([0-9]+)/);
  if(!match)return;
  const url=new URL('https://player.vimeo.com/video/'+match[1]);
  const hash=new URL(external).searchParams.get('h');if(hash)url.searchParams.set('h',hash);
  url.searchParams.set('autoplay','1');url.searchParams.set('dnt','1');
  for(const key of ['title','byline','portrait','badge'])url.searchParams.set(key,'0');
  url.searchParams.set('color','008b91');url.searchParams.set('playsinline','1');
  const frame=document.createElement('iframe');frame.src=url.href;frame.title=button.dataset.title || 'فيديو الدكتور';frame.allow='autoplay; fullscreen; picture-in-picture';frame.allowFullscreen=true;frame.referrerPolicy='strict-origin-when-cross-origin';frame.className='vimeo-player';
  document.querySelector('#video-container').replaceChildren(frame);
  videoDialog.querySelector('h3').textContent=frame.title;
  const link=videoDialog.querySelector('a');link.href='https://vimeo.com/'+match[1];link.textContent='مشاهدة على Vimeo';link.hidden=false;
  videoDialog.showModal();return;
 }
 const video=prepareVideo(button);
 if(!playerShells.has(video)){document.querySelector('#video-container').replaceChildren(video);createPlayer(video);}
 else document.querySelector('#video-container').replaceChildren(playerShells.get(video));
 video.currentTime=0;
 videoDialog.querySelector('h3').textContent=button.dataset.title || 'فيديو الدكتور';
 const sourceLink=videoDialog.querySelector('a');sourceLink.textContent='مشاهدة على فيسبوك';sourceLink.hidden=!/^\d+$/.test(button.dataset.reel || '');if(!sourceLink.hidden)sourceLink.href='https://www.facebook.com/reel/'+encodeURIComponent(button.dataset.reel)+'/';
 videoDialog.showModal();video.play().catch(()=>{ /* The visible play button permits playback if autoplay is blocked. */ });
});});
videoDialog?.addEventListener('close',()=>{videoDialog.querySelectorAll('video').forEach(v=>v.pause());document.querySelector('#video-container')?.replaceChildren();});
const form=document.querySelector('#booking-form');
let showBookingStep=()=>{};
if(form){
 const date=document.querySelector('#date'), today=new Date();
 date.min=new Intl.DateTimeFormat('en-CA',{timeZone:'Africa/Cairo',year:'numeric',month:'2-digit',day:'2-digit'}).format(today);
 let bookingStep=1,selectedTime='',offer='كشف',calendarMonth=new Date(today.getFullYear(),today.getMonth(),1);
 const errorBox=document.querySelector('#booking-error');
 function summary(){ const clinic=document.querySelector('#clinic'),service=document.querySelector('#service');return clinic.selectedOptions[0].textContent+' · '+service.selectedOptions[0].textContent+'\n'+offer+' · '+date.value+' · الوقت بالتنسيق مع العيادة'; }
 showBookingStep=function(step){document.querySelector('#appointment-success').hidden=true;document.querySelector('.appointment-actions').hidden=false;document.querySelector('.booking-progress').hidden=false;bookingStep=step;errorBox.textContent='';document.querySelectorAll('.booking-step').forEach(el=>el.hidden=Number(el.dataset.step)!==step);document.querySelectorAll('[data-step-label]').forEach(el=>el.classList.toggle('active',Number(el.dataset.stepLabel)===step));document.querySelector('#booking-back').hidden=step===1;document.querySelector('#booking-next').hidden=step===3;document.querySelector('#booking-confirm').hidden=step!==3;document.querySelector('#patient').required=step===3;document.querySelector('#appointment-phone').required=step===3;if(step===3)document.querySelector('#appointment-summary').textContent=summary();};
 function renderCalendar(){document.querySelector('#calendar-month').textContent=calendarMonth.toLocaleDateString('ar-EG',{month:'long',year:'numeric'});const grid=document.querySelector('#calendar-days');grid.replaceChildren();const offset=(calendarMonth.getDay()+1)%7;for(let i=0;i<offset;i++)grid.append(document.createElement('span'));const days=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()+1,0).getDate();for(let d=1;d<=days;d++){const value=[calendarMonth.getFullYear(),String(calendarMonth.getMonth()+1).padStart(2,'0'),String(d).padStart(2,'0')].join('-');const button=document.createElement('button');button.type='button';button.textContent=d.toLocaleString('ar-EG');button.disabled=value<date.min;button.setAttribute('aria-label',value);button.setAttribute('aria-pressed',String(date.value===value));button.addEventListener('click',()=>{date.value=value;renderCalendar();});grid.append(button);}}
 document.querySelectorAll('[data-offer]').forEach(button=>button.addEventListener('click',()=>{offer=button.dataset.offer;document.querySelectorAll('[data-offer]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));}));
 document.querySelectorAll('[data-time]').forEach(button=>button.addEventListener('click',()=>{selectedTime=button.dataset.time;document.querySelectorAll('[data-time]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));}));
 document.querySelector('#month-prev').addEventListener('click',()=>{calendarMonth=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()-1,1);renderCalendar();});
 document.querySelector('#month-next').addEventListener('click',()=>{calendarMonth=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()+1,1);renderCalendar();});
 document.querySelector('#booking-next').addEventListener('click',()=>{if(bookingStep===1&&(!document.querySelector('#clinic').value||!document.querySelector('#service').value)){errorBox.textContent='اختاري العيادة والخدمة أولًا.';return;}if(bookingStep===2&&!date.value){errorBox.textContent='اختاري اليوم المفضل أولًا.';return;}showBookingStep(bookingStep+1);});
 document.querySelector('#booking-back').addEventListener('click',()=>showBookingStep(bookingStep-1));
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(bookingStep!==3 || !form.reportValidity())return;
  if(!document.querySelector('#patient').value.trim()){errorBox.textContent='اكتبي الاسم أولًا.';return;}
  const submit=document.querySelector('#booking-confirm');submit.disabled=true;errorBox.textContent='';
  try {
   const response=await fetch(window.helalBooking.url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({patient_name:document.querySelector('#patient').value.trim(),patient_phone:document.querySelector('#appointment-phone').value,booking_date:date.value,service_id:document.querySelector('#service').value,clinic_branch_id:document.querySelector('#clinic').value})});
   const result=await response.json();if(!response.ok)throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'تعذر إرسال الطلب. حاولي مجددًا.');
   document.querySelectorAll('.booking-step,.appointment-actions,.booking-progress').forEach(el=>el.hidden=true);
   document.querySelector('#appointment-success').hidden=false;document.querySelector('#success-details').textContent=result.message+'\n'+summary();
   const link=document.querySelector('#booking-whatsapp');link.href=result.whatsapp_url;link.hidden=false;
  } catch(error){errorBox.textContent=error.message || 'تعذر الاتصال. حاولي مجددًا.';} finally {submit.disabled=false;}
 });
 document.querySelector('#booking-restart').addEventListener('click',()=>showBookingStep(1));
 showBookingStep(1);renderCalendar();
}
document.querySelectorAll('[data-service],[data-clinic]').forEach(button=>button.addEventListener('click',()=>{if(!form){location.href='/appointment'+(button.dataset.service?'?service='+encodeURIComponent(button.dataset.service):'?branch='+encodeURIComponent(button.dataset.clinic));return;}selectByText(button.dataset.service?'#service':'#clinic',button.dataset.service || button.dataset.clinic);showBookingStep(1);document.querySelector('#booking').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});}));
if(document.querySelector('#journey-name') && document.querySelector('[data-stage]')) {
const stages=window.helalJourney || {};
let selectedStage=null;
document.querySelectorAll('[data-stage]').forEach(button=>button.addEventListener('click',()=>{selectedStage=stages[button.dataset.stage];document.querySelectorAll('[data-stage]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));document.querySelector('#journey-placeholder').hidden=true;document.querySelector('#journey-result').hidden=false;document.querySelector('.personal-journey').classList.add('journey-active');document.querySelector('.journey-status').textContent='رحلتكِ جاهزة.. اختاري خطوتكِ التالية.';document.querySelector('#journey-tag').textContent=selectedStage.tag;document.querySelector('#journey-title').textContent=selectedStage.title;document.querySelector('#journey-description').textContent=selectedStage.description;document.querySelector('#journey-questions').replaceChildren(...selectedStage.questions.map(text=>{let li=document.createElement('li');li.textContent=text;return li;}));const videoButton=document.querySelector('#journey-video');videoButton.dataset.reel=selectedStage.reel||'';videoButton.dataset.title=selectedStage.video;videoButton.textContent=(selectedStage.reel?'▶ ':'')+selectedStage.video;document.querySelector('#journey-video-note').textContent=selectedStage.note;}));
document.querySelector('#journey-video').addEventListener('click',event=>{if(!selectedStage?.reel){event.stopImmediatePropagation();window.open('https://www.facebook.com/HelalClinic0','_blank','noopener');}},true);
document.querySelector('#journey-book').addEventListener('click',()=>{if(!selectedStage)return;if(!form){location.href='/appointment?service='+encodeURIComponent(selectedStage.service)+'&name='+encodeURIComponent(document.querySelector('#journey-name').value.trim());return;}selectByText('#service',selectedStage.service);const name=document.querySelector('#journey-name').value.trim();if(name)document.querySelector('#patient').value=name;if(typeof showBookingStep==='function')showBookingStep(1);document.querySelector('#booking').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});});
document.querySelector('#journey-save').addEventListener('click',async()=>{if(!selectedStage)return;await document.fonts.ready;const canvas=document.createElement('canvas');canvas.width=1080;canvas.height=1080;const ctx=canvas.getContext('2d');ctx.fillStyle='#edf7ed';ctx.fillRect(0,0,1080,1080);ctx.fillStyle='#ed8b19';ctx.fillRect(890,180,100,6);ctx.direction='rtl';ctx.textAlign='right';ctx.fillStyle='#006f76';ctx.font='600 46px "IBM Plex Sans Arabic",sans-serif';ctx.fillText('رحلتكِ مع د. محمد هلال',990,330);const name=document.querySelector('#journey-name').value.trim();ctx.font='500 38px "IBM Plex Sans Arabic",sans-serif';ctx.fillText(name?'يا '+name+'، كل رحلة تبدأ بخطوة.':'كل رحلة تبدأ بخطوة.',990,445);ctx.fillStyle='#ed8b19';ctx.font='600 44px "IBM Plex Sans Arabic",sans-serif';ctx.fillText(selectedStage.tag,990,555);ctx.fillStyle='#547276';ctx.font='400 27px "IBM Plex Sans Arabic",sans-serif';selectedStage.questions.forEach((q,i)=>ctx.fillText(q,990,665+i*65,900));ctx.font='500 30px "IBM Plex Sans Arabic",sans-serif';ctx.fillText('للحجز: 01017900354 · 01509011313',990,900);ctx.font='400 21px "IBM Plex Sans Arabic",sans-serif';ctx.fillText('بطاقة لترتيب أسئلتكِ قبل الزيارة',990,980);canvas.toBlob(blob=>{const url=URL.createObjectURL(blob);const a=document.createElement('a');a.href=url;a.download='رحلتك-مع-د-محمد-هلال.png';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);},'image/png');});


 document.querySelector('[data-stage="planning"]')?.click();
}
const menuToggle=document.querySelector('.menu-toggle'),mainMenu=document.querySelector('#main-menu');
menuToggle?.addEventListener('click',()=>{const open=menuToggle.getAttribute('aria-expanded')!=='true';menuToggle.setAttribute('aria-expanded',String(open));mainMenu?.classList.toggle('menu-open',open);});
mainMenu?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{menuToggle.setAttribute('aria-expanded','false');mainMenu.classList.remove('menu-open');}));
document.addEventListener('keydown',event=>{if(event.key==='Escape'){menuToggle?.setAttribute('aria-expanded','false');mainMenu?.classList.remove('menu-open');}});
const year=document.querySelector('#footer-year');if(year)year.textContent=new Date().getFullYear();

if(form){const query=new URLSearchParams(location.search);if(query.has('service'))selectByText('#service',query.get('service'));if(query.has('branch'))selectByText('#clinic',query.get('branch'));if(query.has('name'))document.querySelector('#patient').value=query.get('name');}
