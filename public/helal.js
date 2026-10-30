'use strict';
function displayNumbers(value){return String(value).replace(/[٠-٩۰-۹]/g,digit=>String('٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)%10)).replace(/([0-9])[٫٬](?=[0-9])/g,(match,digit)=>digit+(match.includes('٫')?'.':','));}

function selectByText(selector,text) { const select=document.querySelector(selector); if(!select)return; const option=[...select.options].find(o=>o.textContent===text); if(option){select.value=option.value;select.dispatchEvent(new Event('change',{bubbles:true}));} }
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
 const bar=document.createElement('div');bar.className='player-controls';bar.innerHTML='<div class="player-progress"><input class="seek" type="range" min="0" max="100" step="0.1" value="0" aria-label="تقديم الفيديو"></div><div class="player-toolbar"><button class="toggle" type="button" aria-label="تشغيل أو إيقاف الفيديو">'+icon('play')+'</button><button class="rewind" type="button" aria-label="رجوع 10 ثوانٍ"><svg viewBox="0 0 24 24" width="25" height="25" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7a8 8 0 111 12M5 3v5h5"/></svg><small>10</small></button><button class="forward" type="button" aria-label="تقديم 10 ثوانٍ"><svg viewBox="0 0 24 24" width="25" height="25" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19 7a8 8 0 10-1 12M19 3v5h-5"/></svg><small>10</small></button><span class="player-time">0:00 / 0:00</span><button class="mute" type="button" aria-label="كتم الصوت" aria-pressed="false">'+icon('sound')+'</button><button class="speed" type="button" aria-label="سرعة التشغيل: 1، اضغطي للتغيير">1×</button><button class="pip" type="button" aria-label="صورة داخل صورة">'+icon('pip')+'</button><button class="fullscreen" type="button" aria-label="ملء الشاشة">'+icon('expand')+'</button></div>';shell.append(bar);
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
 const rates=[1,1.25,1.5,2,0.75];const speed=bar.querySelector('.speed');speed.addEventListener('click',()=>{video.playbackRate=rates[(rates.indexOf(video.playbackRate)+1)%rates.length];speed.textContent=video.playbackRate+'×';speed.setAttribute('aria-label','سرعة التشغيل: '+video.playbackRate+'×، اضغطي للتغيير');});
 const full=async()=>{try{if(document.fullscreenElement)await document.exitFullscreen();else if(shell.requestFullscreen)await shell.requestFullscreen();else if(video.webkitEnterFullscreen)video.webkitEnterFullscreen();else shell.classList.toggle('expanded-player');}catch{shell.classList.toggle('expanded-player');}};
 bar.querySelector('.fullscreen').addEventListener('click',full);
 const pip=bar.querySelector('.pip');pip.hidden=!document.pictureInPictureEnabled;pip.addEventListener('click',async()=>{try{if(document.pictureInPictureElement)await document.exitPictureInPicture();else await video.requestPictureInPicture();}catch{showStatus('وضع الصورة المصغرة غير متاح في هذا المتصفح.');}});
 shell.addEventListener('keydown',e=>{if(e.target!==shell)return;if(e.key===' '){e.preventDefault();toggle();}if(e.key==='ArrowRight'){e.preventDefault();seek(10);}if(e.key==='ArrowLeft'){e.preventDefault();seek(-10);}if(e.key==='m')mute();if(e.key==='f')full();});
 playerShells.set(video,shell);return shell;
}

document.querySelectorAll('dialog').forEach(dialog=>{const pause=()=>dialog.querySelectorAll('video').forEach(video=>video.pause());const dismiss=()=>{pause();dialog.close();};dialog.querySelector('.close')?.addEventListener('click',dismiss);dialog.addEventListener('cancel',pause);dialog.addEventListener('click',event=>{if(event.target===dialog)dismiss();});});
document.querySelectorAll('video').forEach(video=>createPlayer(video));
let activeVideoTrigger=null, activeVideoItem=null;
const videoLibrary=[...document.querySelectorAll('[data-video-library] [data-reel]')];
function openVideoDialog(button,media){
 if(!videoDialog)return;
 activeVideoItem=button;
 document.querySelector('#video-container').replaceChildren(media);
 videoDialog.querySelector('h3').textContent=button.dataset.title||'فيديو الدكتور';
 const facebookUrl=/^\d+$/.test(button.dataset.reel||'')?'https://www.facebook.com/reel/'+encodeURIComponent(button.dataset.reel)+'/':'https://www.facebook.com/HelalClinic0';
 videoDialog.querySelectorAll('[data-reel-facebook]').forEach(link=>link.href=facebookUrl);
 if(videoDialog.open)return;
 videoDialog.showModal();
 videoDialog.querySelector('.close').focus({preventScroll:true});
}
document.querySelectorAll('button[data-reel]').forEach(button=>{
 button.addEventListener('click',()=>{activeVideoTrigger=button;playLibraryVideo(button);});
});
function playLibraryVideo(button){
  if(!videoDialog||(!button.dataset.reel&&!button.dataset.src))return;
  document.querySelectorAll('video').forEach(video=>video.pause());
  const external=vimeoSource(button);
  if(external){
   const match=external.match(/^https:\/\/(?:player\.vimeo\.com\/video\/|(?:www\.)?vimeo\.com\/)([0-9]+)/);if(!match)return;
   const url=new URL('https://player.vimeo.com/video/'+match[1]);const hash=new URL(external).searchParams.get('h');if(hash)url.searchParams.set('h',hash);
   url.searchParams.set('autoplay','1');url.searchParams.set('dnt','1');for(const key of ['title','byline','portrait','badge'])url.searchParams.set(key,'0');url.searchParams.set('color','008b91');url.searchParams.set('playsinline','1');
   const frame=document.createElement('iframe');frame.src=url.href;frame.title=button.dataset.title||'فيديو الدكتور';frame.allow='autoplay; fullscreen; picture-in-picture';frame.allowFullscreen=true;frame.referrerPolicy='strict-origin-when-cross-origin';frame.className='vimeo-player';openVideoDialog(button,frame);return;
  }
  const video=prepareVideo(button);let shell=playerShells.get(video);
  if(shell)openVideoDialog(button,shell);else{openVideoDialog(button,video);shell=createPlayer(video);}
  video.currentTime=0;video.play().catch(()=>{ /* A visible play control permits retrying a blocked autoplay. */ });
}
videoDialog?.querySelectorAll('[data-video-step]').forEach(button=>{
 button.hidden=videoLibrary.length<2;
 button.addEventListener('click',()=>{
  const index=videoLibrary.findIndex(item=>item.dataset.reel===activeVideoItem?.dataset.reel);
  const step=Number(button.dataset.videoStep);
  playLibraryVideo(videoLibrary[(index+step+videoLibrary.length)%videoLibrary.length]);
 });
});
function resetVideoDialog(){
 videoDialog.querySelectorAll('video').forEach(video=>video.pause());document.querySelector('#video-container')?.replaceChildren();activeVideoTrigger?.focus({preventScroll:true});activeVideoTrigger=null;activeVideoItem=null;
}
videoDialog?.addEventListener('close',resetVideoDialog);
videoDialog?.querySelector('.close').addEventListener('click',resetVideoDialog);
const tvInterviewDialog=document.querySelector('#tv-interview-dialog');
let tvInterviewTrigger=null;
document.addEventListener('error',event=>{if(event.target instanceof HTMLImageElement&&event.target.closest('.tv-interview-cover'))event.target.remove();},true);
function closeTvInterview(){tvInterviewDialog?.querySelector('[data-tv-interview-player]')?.replaceChildren();tvInterviewTrigger?.focus({preventScroll:true});tvInterviewTrigger=null;}
document.querySelectorAll('[data-tv-interview-id]').forEach(button=>button.addEventListener('click',()=>{
 const id=button.dataset.tvInterviewId||'';
 if(!/^[A-Za-z0-9_-]{11}$/.test(id)||!tvInterviewDialog)return;
 tvInterviewTrigger=button;
 const title=button.dataset.tvInterviewTitle||'لقاء تلفزيوني';
 tvInterviewDialog.querySelector('#tv-interview-dialog-title').textContent=title;
 const frame=document.createElement('iframe');
 frame.src='https://www.youtube-nocookie.com/embed/'+encodeURIComponent(id)+'?autoplay=1&playsinline=1';
 frame.title=title;frame.allow='autoplay; encrypted-media; picture-in-picture; fullscreen';frame.allowFullscreen=true;
 frame.referrerPolicy='strict-origin-when-cross-origin';frame.loading='eager';
 tvInterviewDialog.querySelector('[data-tv-interview-player]').replaceChildren(frame);
 const link=tvInterviewDialog.querySelector('[data-tv-interview-link]');link.href='https://www.youtube.com/watch?v='+encodeURIComponent(id);link.hidden=false;
 tvInterviewDialog.showModal();tvInterviewDialog.querySelector('.tv-interview-close').focus({preventScroll:true});
}));
tvInterviewDialog?.querySelector('.tv-interview-close').addEventListener('click',()=>tvInterviewDialog.close());
tvInterviewDialog?.addEventListener('close',closeTvInterview);
const form=document.querySelector('#booking-form');
let showBookingStep=()=>{};
if(form){
 const date=document.querySelector('#date'), today=new Date();
 date.min=new Intl.DateTimeFormat('en-CA',{timeZone:'Africa/Cairo',year:'numeric',month:'2-digit',day:'2-digit'}).format(today);
 let submitting=false,requestKey=null,requestPayload=null;
 let bookingStep=1,selectedTime='',offer='كشف',calendarMonth=new Date(Number(date.min.slice(0,4)),Number(date.min.slice(5,7))-1,1);
 const errorBox=document.querySelector('#booking-error'),bookingHeading=form.querySelector('.form-head'),fertilityQuestions=document.querySelector('#fertility-questions'),serviceSelect=document.querySelector('#service');
 const isFertility=()=>serviceSelect.selectedOptions[0]?.dataset.serviceSlug==='service-2';
 function syncFertilityQuestions(){const enabled=isFertility();fertilityQuestions.hidden=!enabled;fertilityQuestions.querySelectorAll('input').forEach(input=>{input.disabled=!enabled;if(!enabled)input.checked=false;});}
 bookingHeading?.setAttribute('tabindex','-1');
 function availability(value){
  const schedule=window.helalBooking.schedules?.[document.querySelector('#clinic').value];
  if(!schedule?.enabled)return {available:true,label:schedule?.legacy_hours||'لم تُضف مواعيد هذا الفرع بعد.'};
  const override=(schedule.exceptions||[]).find(item=>item.date===value);
  const day=new Date(value+'T12:00:00Z').getUTCDay();
  const entry=override||schedule.weekly?.[day];
  const open=override?override.closed===false:entry?.open===true;
  const valid=entry&&/^([01]\d|2[0-3]):[0-5]\d$/.test(entry.from||'')&&/^([01]\d|2[0-3]):[0-5]\d$/.test(entry.to||'')&&(entry.to>entry.from||(entry.to==='00:00'&&entry.from>'00:00'));
  if(!open||!valid)return {available:false,label:'العيادة مغلقة في اليوم ده. اختاري يومًا آخر.'};
  const format=time=>{if(time==='00:00')return '12 منتصف الليل';const [hour,minute]=time.split(':').map(Number);return (hour%12||12)+':'+String(minute).padStart(2,'0')+' '+(hour<12?'صباحًا':'مساءً');};
  return {available:true,from:entry.from,to:entry.to,label:'من '+format(entry.from)+' إلى '+format(entry.to)};
 }
 const timeField=document.querySelector('#booking-time-field'),timeSlots=document.querySelector('#booking-time-slots');
 const timeEnabled=()=>{const s=window.helalBooking.schedules?.[document.querySelector('#clinic').value];return Boolean(s?.enabled&&s?.time_enabled);};
 function slotList(range){
  const toMinutes=t=>Number(t.slice(0,2))*60+Number(t.slice(3));
  const close=range.to==='00:00'?1440:toMinutes(range.to);
  let start=toMinutes(range.from);if(start%30)start+=30-start%30;
  const list=[];for(let slot=start;slot<close;slot+=30)list.push(String(Math.floor(slot/60)).padStart(2,'0')+':'+String(slot%60).padStart(2,'0'));
  return list;
 }
 function updateTimePicker(){
  const day=availability(date.value),enabled=Boolean(date.value&&day.available&&timeEnabled());
  timeField.hidden=!enabled;
  if(!enabled){selectedTime='';timeSlots.replaceChildren();return;}
  const slots=slotList(day);
  timeSlots.replaceChildren(...slots.map(slot=>{
   const button=document.createElement('button');button.type='button';button.dataset.time=slot;button.textContent=slot;button.setAttribute('aria-pressed',String(slot===selectedTime));
   button.addEventListener('click',()=>{selectedTime=slot;timeSlots.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(b.dataset.time===slot)));});
   return button;}));
  if(selectedTime&&!slots.includes(selectedTime))selectedTime='';
 }
 function updateHours(){updateTimePicker();const hours=document.querySelector('#booking-hours');if(hours)hours.textContent=date.value?availability(date.value).label:(document.querySelector('#calendar-days button:not([disabled])')?'اختاري اليوم لعرض مواعيد العيادة.':'لا توجد أيام متاحة في الشهر ده. جرّبي شهرًا آخر.');}
 document.querySelector('#clinic').addEventListener('change',()=>{date.value='';selectedTime='';renderCalendar();});
 serviceSelect.addEventListener('change',syncFertilityQuestions);
 syncFertilityQuestions();
 function summary(){ const clinic=document.querySelector('#clinic'),service=document.querySelector('#service');let text=clinic.selectedOptions[0].textContent+' · '+service.selectedOptions[0].textContent+'\n'+offer+' · '+date.value+(selectedTime?' · الساعة '+selectedTime+' بتوقيت القاهرة':' · '+availability(date.value).label);if(isFertility()){for(const [name,label] of [['previous_pregnancy','حمل سابق'],['pregnancy_related_surgery','عملية مرتبطة بالحمل']]){const answer=form.querySelector(`input[name="${name}"]:checked`)?.value;if(answer)text+='\n'+label+': '+(answer==='yes'?'نعم':'لا');}}return text; }
 showBookingStep=function(step,focusHeading=false){document.querySelector('.appointment-actions').hidden=false;document.querySelector('.booking-progress').hidden=false;bookingStep=step;errorBox.textContent='';const stepCaption=document.querySelector('#booking-step-caption');if(stepCaption)stepCaption.textContent='الخطوة '+String(step)+' من 3';const stepHint=form.querySelector('.booking-step-hint');if(stepHint)stepHint.textContent=JSON.parse(stepHint.dataset.hints)[step-1];form.querySelectorAll('[data-step-label]').forEach(label=>{if(Number(label.dataset.stepLabel)===step)label.setAttribute('aria-current','step');else label.removeAttribute('aria-current');label.classList.toggle('completed',Number(label.dataset.stepLabel)<step);});document.querySelectorAll('.booking-step').forEach(el=>el.hidden=Number(el.dataset.step)!==step);document.querySelectorAll('[data-step-label]').forEach(el=>el.classList.toggle('active',Number(el.dataset.stepLabel)===step));document.querySelector('#booking-back').hidden=step===1;document.querySelector('#booking-next').hidden=step===3;document.querySelector('#booking-confirm').hidden=step!==3;document.querySelector('#patient').required=step===3;document.querySelector('#appointment-phone').required=step===3;if(step===3){document.querySelector('#appointment-summary').textContent=summary();document.querySelector('#visit-window-acknowledged').checked=false;document.querySelector('#visit-window-confirmation').textContent=selectedTime?'الساعة المختارة '+selectedTime+' بتوقيت القاهرة':availability(date.value).label;}if(focusHeading&&bookingHeading){bookingHeading.focus({preventScroll:true});bookingHeading.scrollIntoView({block:'start',behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});}};
 function renderCalendar(){document.querySelector('#calendar-month').textContent=calendarMonth.toLocaleDateString('ar-EG-u-nu-latn',{month:'long',year:'numeric'});const grid=document.querySelector('#calendar-days');grid.replaceChildren();const offset=(calendarMonth.getDay()+1)%7;for(let i=0;i<offset;i++)grid.append(document.createElement('span'));const days=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()+1,0).getDate();for(let d=1;d<=days;d++){const value=[calendarMonth.getFullYear(),String(calendarMonth.getMonth()+1).padStart(2,'0'),String(d).padStart(2,'0')].join('-');const button=document.createElement('button');button.type='button';button.textContent=String(d);button.disabled=value<date.min||!availability(value).available;button.dataset.date=value;button.setAttribute('aria-label',new Date(calendarMonth.getFullYear(),calendarMonth.getMonth(),d).toLocaleDateString('ar-EG-u-nu-latn',{day:'numeric',month:'long',year:'numeric'}));button.setAttribute('aria-pressed',String(date.value===value));button.addEventListener('click',()=>{date.value=value;selectedTime='';renderCalendar();});grid.append(button);}updateHours();}
 document.querySelectorAll('[data-offer]').forEach(button=>button.addEventListener('click',()=>{offer=button.dataset.offer;document.querySelectorAll('[data-offer]').forEach(b=>b.setAttribute('aria-pressed',String(b===button)));}));
 document.querySelector('#month-prev').addEventListener('click',()=>{calendarMonth=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()-1,1);renderCalendar();});
 document.querySelector('#month-next').addEventListener('click',()=>{calendarMonth=new Date(calendarMonth.getFullYear(),calendarMonth.getMonth()+1,1);renderCalendar();});
 document.querySelector('#booking-next').addEventListener('click',()=>{if(bookingStep===1&&(!document.querySelector('#clinic').value||!document.querySelector('#service').value)){errorBox.textContent='اختاري العيادة والخدمة أولًا.';return;}if(bookingStep===2&&(!date.value||!availability(date.value).available)){errorBox.textContent='اختاري اليوم المفضل أولًا.';return;}if(bookingStep===2&&timeEnabled()&&!selectedTime){errorBox.textContent='اختاري موعدًا من مواعيد الفرع.';return;}showBookingStep(bookingStep+1,true);});
 document.querySelector('#booking-back').addEventListener('click',()=>showBookingStep(bookingStep-1,true));
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(submitting)return;if(bookingStep!==3 || !form.reportValidity())return;
  if(!document.querySelector('#patient').value.trim()){errorBox.textContent='اكتبي الاسم أولًا.';return;}
  const submit=document.querySelector('#booking-confirm');
  submitting=true;submit.disabled=true;errorBox.textContent='';
  const payload={patient_name:document.querySelector('#patient').value.trim(),patient_phone:document.querySelector('#appointment-phone').value,booking_date:date.value,service_id:document.querySelector('#service').value,clinic_branch_id:document.querySelector('#clinic').value,visit_type:offer==='متابعة'?'follow_up':'first_visit',visit_window:availability(date.value).label,visit_window_acknowledged:document.querySelector('#visit-window-acknowledged').checked};
  if(timeEnabled())payload.booking_time=selectedTime;
  if(isFertility())Object.assign(payload,{previous_pregnancy:form.querySelector('input[name="previous_pregnancy"]:checked')?.value||null,pregnancy_related_surgery:form.querySelector('input[name="pregnancy_related_surgery"]:checked')?.value||null});
  if(!payload.visit_window_acknowledged){errorBox.textContent='برجاء تأكيد قراءة فترة الزيارة.';submitting=false;submit.disabled=false;return;}
  const signature=JSON.stringify(payload);if(signature!==requestPayload){requestPayload=signature;requestKey=crypto.randomUUID();}payload.request_key=requestKey;
  try {
   const response=await fetch(window.helalBooking.url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify(payload)});

   const result=await response.json();if(!response.ok)throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'تعذر إرسال الطلب. حاولي مجددًا.');
   const confirmationUrl=new URL(result.confirmation_url,location.origin);
   if(confirmationUrl.origin!==location.origin||confirmationUrl.pathname!=='/booking-confirmation'||confirmationUrl.search||confirmationUrl.hash)throw new Error('تعذر تجهيز صفحة متابعة الطلب. حاولي مجددًا.');
   location.assign(confirmationUrl.href);
  } catch(error){errorBox.textContent=error.message || 'تعذر الاتصال. حاولي مجددًا.';} finally {submitting=false;submit.disabled=false;}
 });
 showBookingStep(1);renderCalendar();
}
document.querySelectorAll('[data-service],[data-clinic]').forEach(button=>button.addEventListener('click',()=>{if(!form){location.href='/appointment'+(button.dataset.service?'?service='+encodeURIComponent(button.dataset.service):'?branch='+encodeURIComponent(button.dataset.clinic));return;}selectByText(button.dataset.service?'#service':'#clinic',button.dataset.service || button.dataset.clinic);showBookingStep(1);document.querySelector('#booking').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});}));
if(document.querySelector('#journey-name') && document.querySelector('[data-stage]')) {
 const stages=window.helalJourney||{},personalQuestions={},questionDrafts={};
 let selectedStage=null,selectedKey=null;
 const input=document.querySelector('#journey-personal-question'),questionStatus=document.querySelector('#journey-question-status');
 function questions(){return [...(selectedStage?.questions||[]),...(personalQuestions[selectedKey]||[])];}
 function renderQuestions(){
  const list=document.querySelector('#journey-questions');list.replaceChildren();
  for(const question of selectedStage?.questions||[]){const li=document.createElement('li');li.textContent=question;list.append(li);}
  (personalQuestions[selectedKey]||[]).forEach((question,index)=>{const li=document.createElement('li');li.className='personal-question';const text=document.createElement('span');text.textContent=question;const remove=document.createElement('button');remove.type='button';remove.textContent='×';remove.setAttribute('aria-label','حذف السؤال: '+question);remove.addEventListener('click',()=>{personalQuestions[selectedKey].splice(index,1);renderQuestions();questionStatus.textContent='تم حذف السؤال.';});li.append(text,remove);list.append(li);});
 }
 document.querySelectorAll('[data-stage]').forEach(button=>button.addEventListener('click',()=>{
  const stage=stages[button.dataset.stage];if(!stage)return;
  if(selectedKey)questionDrafts[selectedKey]=input.value;
  selectedKey=button.dataset.stage;selectedStage={...stage,questions:(stage.questions||[]).map(displayNumbers)};
  for(const key of ['tag','title','description','video','note','service'])if(selectedStage[key])selectedStage[key]=displayNumbers(selectedStage[key]);
  document.querySelectorAll('[data-stage]').forEach(item=>item.setAttribute('aria-pressed',String(item===button)));
  document.querySelector('#journey-placeholder').hidden=true;document.querySelector('#journey-result').hidden=false;document.querySelector('.personal-journey').classList.add('journey-active');
  document.querySelector('.journey-status').textContent='جهّزي أسئلتكِ، وبعدها كمّلي لطلب الموعد.';
  document.querySelector('#journey-tag').textContent=selectedStage.tag||'';document.querySelector('#journey-title').textContent=selectedStage.title||'';document.querySelector('#journey-description').textContent=selectedStage.description||'';
  input.value=questionDrafts[selectedKey]||'';input.setCustomValidity('');questionStatus.textContent='';renderQuestions();
  const videoButton=document.querySelector('#journey-video');videoButton.dataset.reel=selectedStage.reel||'';videoButton.dataset.src=selectedStage.source||'';videoButton.dataset.poster=selectedStage.poster||'';videoButton.dataset.title=selectedStage.video||'';videoButton.hidden=!selectedStage.reel;
  videoButton.setAttribute('aria-label','تشغيل '+(selectedStage.video||'فيديو الدكتور'));
  const poster=document.querySelector('#journey-video-poster');if(selectedStage.poster)poster.src=selectedStage.poster;else poster.removeAttribute('src');
  document.querySelector('#journey-video-title').textContent=selectedStage.video||'';
  document.querySelector('#journey-video-fallback').hidden=!!selectedStage.reel;document.querySelector('#journey-video-note').textContent=selectedStage.note||'';
 }));
 input.addEventListener('input',()=>{input.setCustomValidity('');if(selectedKey)questionDrafts[selectedKey]=input.value;});
 document.querySelector('#journey-question-form').addEventListener('submit',event=>{
  event.preventDefault();if(!selectedKey)return;const question=input.value.trim();
  if(!question){input.setCustomValidity('اكتبي سؤالكِ الأول.');input.reportValidity();return;}
  const list=personalQuestions[selectedKey]||=[];
  if(list.length>=10){questionStatus.textContent='يمكنكِ إضافة 10 أسئلة لكل مرحلة. احذفي سؤالًا لو حابة تستبدليه.';return;}
  list.push(question);input.value='';questionDrafts[selectedKey]='';renderQuestions();questionStatus.textContent='اتضاف سؤالكِ، وهيظهر في بطاقة رحلتكِ.';input.focus();
 });
 function showJourneyBooking(open){
  const panel=document.querySelector('#journey-booking-panel');if(!panel)return;
  panel.hidden=!open;document.querySelector('#journey-preparation-panel').hidden=open;
  document.querySelectorAll('[data-journey-step]').forEach(item=>{if(Number(item.dataset.journeyStep)===(open?2:1))item.setAttribute('aria-current','step');else item.removeAttribute('aria-current');});
  const target=open?panel.querySelector('.form-head'):document.querySelector('#journey-title');if(target){target.tabIndex=-1;target.focus({preventScroll:true});target.scrollIntoView({block:'start',behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});}
 }
 document.querySelector('#journey-book').addEventListener('click',()=>{
  if(!selectedStage)return;
  if(!form){location.href='/appointment?service='+encodeURIComponent(selectedStage.service||'')+'&name='+encodeURIComponent(document.querySelector('#journey-name').value.trim());return;}
  selectByText('#service',selectedStage.service);const name=document.querySelector('#journey-name').value.trim();if(name)document.querySelector('#patient').value=name;showBookingStep(1);
  if(document.querySelector('#journey-booking-panel'))showJourneyBooking(true);else document.querySelector('#booking').scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'instant':'smooth'});
 });
 document.querySelector('#journey-return')?.addEventListener('click',()=>showJourneyBooking(false));
 document.querySelector('#journey-save').addEventListener('click',async()=>{
  if(!selectedStage)return;const button=document.querySelector('#journey-save');button.disabled=true;
  try{await window.saveHelalJourneyCard({tag:selectedStage.tag,name:document.querySelector('#journey-name').value.trim(),questions:questions()});}
  catch{questionStatus.textContent='تعذر حفظ البطاقة. حاولي مرة تانية.';}finally{button.disabled=false;}
 });
 document.querySelector('[data-stage]:not([disabled])')?.click();
}
const menuToggle=document.querySelector('.menu-toggle'),mainMenu=document.querySelector('#main-menu');
function setMenuOpen(open){if(!menuToggle)return;menuToggle.setAttribute('aria-expanded',String(open));menuToggle.setAttribute('aria-label',open?'إغلاق قائمة الموقع':'فتح قائمة الموقع');mainMenu?.classList.toggle('menu-open',open);}
menuToggle?.addEventListener('click',()=>setMenuOpen(menuToggle.getAttribute('aria-expanded')!=='true'));
mainMenu?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>setMenuOpen(false)));
document.addEventListener('keydown',event=>{if(event.key==='Escape')setMenuOpen(false);});
const year=document.querySelector('#footer-year');if(year)year.textContent=new Date().getFullYear();

if(form){const query=new URLSearchParams(location.search);if(query.has('service'))selectByText('#service',query.get('service'));if(query.has('branch'))selectByText('#clinic',query.get('branch'));if(query.has('name'))document.querySelector('#patient').value=query.get('name');}

// The website prepares a message; WhatsApp handles the final send.
const chatToggle=document.querySelector('#clinic-chat-toggle'),chatPanel=document.querySelector('#clinic-chat');
if(chatToggle&&chatPanel){
 const message=chatPanel.querySelector('textarea');
 const chatWidget=chatToggle.closest('.clinic-chat-widget');
 const closeChat=(restoreFocus=false)=>{chatWidget.classList.remove('chat-is-open');chatPanel.hidden=true;chatToggle.setAttribute('aria-expanded','false');if(restoreFocus)chatToggle.focus();};
 chatToggle.addEventListener('click',event=>{event.preventDefault();const open=chatPanel.hidden;chatPanel.hidden=!open;chatWidget.classList.toggle('chat-is-open',open);chatWidget.classList.add('chat-has-opened');chatToggle.setAttribute('aria-expanded',String(open));if(open)chatPanel.querySelector('.clinic-chat-close').focus();});
 chatPanel.querySelector('.clinic-chat-close').addEventListener('click',()=>closeChat(true));
 document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!chatPanel.hidden){closeChat(true);}});
 document.addEventListener('click',event=>{if(!chatPanel.hidden&&!chatWidget.contains(event.target))closeChat();});
 message.addEventListener('input',()=>message.setCustomValidity(''));
 const chatForm=chatPanel.querySelector('form'),chatStatus=chatPanel.querySelector('#clinic-chat-privacy');
 const chatStatusText=chatStatus.textContent;
 chatForm.addEventListener('submit',event=>{
  event.preventDefault();
  const text=message.value.trim();
  if(!text){message.setCustomValidity('اكتبي رسالتكِ الأول علشان نقدر نساعدكِ.');message.reportValidity();return;}
  const url=new URL(chatForm.action);url.searchParams.set('text',text);
  const handoff=window.open(url.href,'_blank');
  if(!handoff){chatStatus.textContent='اسمحي بفتح تاب جديدة، وبعدها اضغطي إرسال مرة تانية.';return;}
  handoff.opener=null;
  chatForm.reset();message.setCustomValidity('');chatStatus.textContent=chatStatusText;closeChat(true);
 });
}

// Keep the form's original values while presenting a consistent accessible listbox.
document.querySelectorAll('select').forEach((select,selectIndex)=>{
 if(select.multiple||select.size>1)return;
 const wrapper=document.createElement('div');wrapper.className='clinic-select';
 select.before(wrapper);wrapper.append(select);
 const trigger=document.createElement('button');trigger.type='button';trigger.className='clinic-select-trigger';trigger.id=(select.id||'select-'+selectIndex)+'-choice';
 trigger.setAttribute('role','combobox');trigger.setAttribute('aria-haspopup','listbox');trigger.setAttribute('aria-expanded','false');trigger.setAttribute('aria-required',String(select.required));trigger.disabled=select.disabled;
 const label=document.querySelector('label[for="'+select.id+'"]');
 if(label){label.htmlFor=trigger.id;if(!label.id)label.id=trigger.id+'-label';trigger.setAttribute('aria-labelledby',label.id+' '+trigger.id+'-text');}
 const text=document.createElement('span');text.id=trigger.id+'-text';
 const arrow=document.createElementNS('http://www.w3.org/2000/svg','svg');arrow.setAttribute('viewBox','0 0 24 24');arrow.setAttribute('aria-hidden','true');arrow.innerHTML='<path d="m6 9 6 6 6-6"/>';trigger.append(text,arrow);
 const list=document.createElement('div');list.className='clinic-select-options';list.id=trigger.id+'-options';list.setAttribute('role','listbox');list.setAttribute('aria-label',label?.textContent||select.getAttribute('aria-label')||'الاختيارات');list.hidden=true;trigger.setAttribute('aria-controls',list.id);
 let active=select.selectedIndex,search='',searchTimer;
 let options=[];
 function rebuildOptions(){
 list.replaceChildren();
 options=[...select.options].map((option,index)=>{
  const row=document.createElement('div');row.id=list.id+'-'+index;row.className='clinic-select-option';row.tabIndex=-1;row.setAttribute('role','option');row.textContent=option.textContent;row.setAttribute('aria-disabled',String(option.disabled));
  row.addEventListener('click',()=>choose(index));list.append(row);return row;
 });
 active=select.selectedIndex;close();sync();
 }
 function highlight(index){active=index;options.forEach((row,i)=>row.classList.toggle('is-active',i===index));if(index>=0){trigger.setAttribute('aria-activedescendant',options[index].id);options[index].scrollIntoView({block:'nearest'});}}
 function sync(){trigger.disabled=select.disabled;trigger.setAttribute('aria-required',String(select.required));if(select.disabled)close();text.textContent=select.selectedOptions[0]?.textContent||'';options.forEach((row,i)=>row.setAttribute('aria-selected',String(i===select.selectedIndex)));trigger.classList.toggle('is-placeholder',!select.value);}
 function close(){list.hidden=true;trigger.setAttribute('aria-expanded','false');trigger.removeAttribute('aria-activedescendant');wrapper.classList.remove('is-open');}
 function open(){document.dispatchEvent(new CustomEvent('clinic-select-open',{detail:wrapper}));list.hidden=false;const rect=trigger.getBoundingClientRect(),below=innerHeight-rect.bottom,above=rect.top;const upwards=below<Math.min(240,list.scrollHeight+14)&&above>below;wrapper.classList.toggle('opens-up',upwards);list.style.maxHeight=Math.min(240,Math.max(88,(upwards?above:below)-12))+'px';wrapper.classList.add('is-open');trigger.setAttribute('aria-expanded','true');highlight(select.selectedIndex);}
 function choose(index){if(select.options[index].disabled)return;select.selectedIndex=index;select.dispatchEvent(new Event('input',{bubbles:true}));select.dispatchEvent(new Event('change',{bubbles:true}));close();trigger.focus({preventScroll:true});}
 function move(step){let index=active;for(let n=0;n<options.length;n++){index=(index+step+options.length)%options.length;if(!select.options[index].disabled){highlight(index);break;}}}
 trigger.addEventListener('click',()=>list.hidden?open():close());
 trigger.addEventListener('keydown',event=>{
  if(['ArrowDown','ArrowUp','Home','End','Enter',' ','Escape'].includes(event.key))event.preventDefault();
  if(event.key==='Escape'){close();return;}
  if(event.key==='Tab'){close();return;}
  if(['Enter',' '].includes(event.key)){if(list.hidden)open();else if(active>=0)choose(active);return;}
  if(['ArrowDown','ArrowUp'].includes(event.key)){if(list.hidden)open();move(event.key==='ArrowDown'?1:-1);return;}
  if(event.key==='Home'||event.key==='End'){if(list.hidden)open();active=event.key==='Home'?-1:0;move(event.key==='Home'?1:-1);return;}
  if(event.key.length===1&&!event.ctrlKey&&!event.metaKey&&!event.altKey){if(list.hidden)open();search+=event.key;clearTimeout(searchTimer);searchTimer=setTimeout(()=>search='',700);const index=[...select.options].findIndex(option=>!option.disabled&&option.textContent.trim().startsWith(search));if(index>=0)highlight(index);}
 });
 document.addEventListener('clinic-select-open',event=>{if(event.detail!==wrapper)close();});
 document.addEventListener('click',event=>{if(!wrapper.contains(event.target))close();});
 wrapper.addEventListener('focusout',event=>{if(!wrapper.contains(event.relatedTarget))close();});
 select.addEventListener('change',sync);
 select.addEventListener('invalid',event=>{event.preventDefault();trigger.focus();trigger.setAttribute('aria-invalid','true');});
 select.addEventListener('change',()=>trigger.removeAttribute('aria-invalid'));
 select.form?.addEventListener('reset',()=>queueMicrotask(()=>{sync();close();trigger.removeAttribute('aria-invalid');}));
 wrapper.append(trigger,list);select.hidden=true;select.tabIndex=-1;rebuildOptions();
 // Async catalogues replace native options after enhancement; mirror them and disabled state.
 new MutationObserver(rebuildOptions).observe(select,{childList:true,subtree:true,characterData:true,attributes:true,attributeFilter:['disabled','required','selected','label','value']});
});

// Keep visible introduction slides rotating unless reduced motion is requested.
document.querySelectorAll('[data-about-carousel]').forEach(carousel=>{
 const slides=[...carousel.querySelectorAll('[data-about-slide]')],dots=[...carousel.querySelectorAll('[data-about-dot]')];
 if(slides.length<2)return;
 const controls=carousel.querySelector('[data-about-controls]'),status=carousel.querySelector('[data-about-status]'),reduced=matchMedia('(prefers-reduced-motion: reduce)');
 let current=0,visible=false,timer;
 function show(index,announce=false){current=(index+slides.length)%slides.length;slides.forEach((slide,i)=>{slide.classList.toggle('is-current',i===current);slide.setAttribute('aria-hidden',String(i!==current));slide.inert=i!==current;});dots.forEach((dot,i)=>{if(i===current)dot.setAttribute('aria-current','true');else dot.removeAttribute('aria-current');});if(announce)status.textContent='المعلومة '+(current+1)+' من '+slides.length;}
 function schedule(){clearInterval(timer);if(visible&&!document.hidden&&!reduced.matches)timer=setInterval(()=>show(current+1),8000);}
 function manual(index){show(index,true);schedule();}
 controls.hidden=false;carousel.classList.add('is-enhanced');show(0);
 carousel.querySelector('[data-about-prev]').addEventListener('click',()=>manual(current-1));
 carousel.querySelector('[data-about-next]').addEventListener('click',()=>manual(current+1));
 dots.forEach((dot,i)=>dot.addEventListener('click',()=>manual(i)));
 document.addEventListener('visibilitychange',schedule);
 reduced.addEventListener('change',schedule);
 if('IntersectionObserver' in window)new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;schedule();},{threshold:.4}).observe(carousel);else{visible=true;schedule();}
});
