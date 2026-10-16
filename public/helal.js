'use strict';
function selectByText(selector,text) { const select=document.querySelector(selector); if(!select)return; const option=[...select.options].find(o=>o.textContent===text); if(option)select.value=option.value; }
const videoDialog=document.querySelector('#video-dialog');
function createPlayer(video){
 video.controls=false;
 const shell=document.createElement('div');shell.className='modern-player';video.parentNode.insertBefore(shell,video);shell.append(video);
 const overlay=document.createElement('button');overlay.className='player-center';overlay.type='button';overlay.setAttribute('aria-label','تشغيل الفيديو');overlay.textContent='▶';shell.append(overlay);
 const bar=document.createElement('div');bar.className='player-controls';bar.innerHTML='<input class="seek" type="range" min="0" max="100" step="0.1" value="0" aria-label="تقديم الفيديو"><div class="player-toolbar"><button class="toggle" type="button" aria-label="تشغيل أو إيقاف الفيديو">▶</button><span class="player-time">0:00 / 0:00</span><button class="mute" type="button" aria-label="كتم أو تشغيل الصوت">♪</button><input class="volume" type="range" min="0" max="1" step="0.05" value="1" aria-label="مستوى الصوت"><select class="speed" aria-label="سرعة التشغيل"><option value="1">1×</option><option value="1.25">1.25×</option><option value="1.5">1.5×</option><option value="2">2×</option></select><button class="fullscreen" type="button" aria-label="ملء الشاشة">⛶</button></div>';shell.append(bar);
 const status=document.createElement('p');status.className='player-status';status.setAttribute('role','status');status.hidden=true;shell.append(status);
 const showStatus=text=>{status.textContent=text;status.hidden=!text;};
 const play=()=>video.play().catch(error=>{if(error.name==='AbortError')return;showStatus(error.name==='NotAllowedError'?'اضغطي تشغيل لبدء الفيديو.':'تعذر تشغيل الفيديو. حاولي مرة أخرى.');});
 video.addEventListener('error',()=>showStatus('تعذر تحميل الفيديو. تحققي من الاتصال وحاولي مرة أخرى.'));
 video.addEventListener('playing',()=>showStatus(''));
 const toggle=()=>video.paused?play():video.pause();overlay.addEventListener('click',toggle);video.addEventListener('click',toggle);bar.querySelector('.toggle').addEventListener('click',toggle);
 const format=t=>Math.floor((t||0)/60)+':'+String(Math.floor((t||0)%60)).padStart(2,'0');
 const update=()=>{bar.querySelector('.player-time').textContent=format(video.currentTime)+' / '+format(video.duration);bar.querySelector('.seek').value=video.duration?video.currentTime/video.duration*100:0;};video.addEventListener('timeupdate',update);video.addEventListener('loadedmetadata',update);
 video.addEventListener('play',()=>{shell.classList.add('is-playing');bar.querySelector('.toggle').textContent='Ⅱ';overlay.setAttribute('aria-label','إيقاف الفيديو');});video.addEventListener('pause',()=>{shell.classList.remove('is-playing');bar.querySelector('.toggle').textContent='▶';overlay.setAttribute('aria-label','تشغيل الفيديو');});
 bar.querySelector('.seek').addEventListener('input',e=>{if(Number.isFinite(video.duration))video.currentTime=Number(e.target.value)/100*video.duration;});bar.querySelector('.mute').addEventListener('click',()=>{video.muted=!video.muted;bar.querySelector('.mute').textContent=video.muted?'×':'♪';});bar.querySelector('.volume').addEventListener('input',e=>{video.volume=Number(e.target.value);video.muted=video.volume===0;bar.querySelector('.mute').textContent=video.muted?'×':'♪';});bar.querySelector('.speed').addEventListener('change',e=>video.playbackRate=Number(e.target.value));bar.querySelector('.fullscreen').addEventListener('click',()=>{shell.classList.toggle('expanded-player');if(!shell.classList.contains('expanded-player')&&document.fullscreenElement)document.exitFullscreen();});
 shell.tabIndex=0;shell.addEventListener('keydown',e=>{if(e.target!==shell)return;if(e.key===' '){e.preventDefault();toggle();}if(e.key==='ArrowRight')video.currentTime=Math.min(video.duration,video.currentTime+5);if(e.key==='ArrowLeft')video.currentTime=Math.max(0,video.currentTime-5);});return shell;
}

document.querySelectorAll('dialog').forEach(dialog=>{dialog.querySelector('.close')?.addEventListener('click',()=>dialog.close());dialog.addEventListener('click',event=>{if(event.target===dialog)dialog.close();});});
document.querySelectorAll('video').forEach(video=>createPlayer(video));
document.querySelectorAll('[data-reel]').forEach(button=>button.addEventListener('click',()=>{
 if(!videoDialog || (!button.dataset.reel && !button.dataset.src))return;
 document.querySelectorAll('video').forEach(v=>v.pause());
 const video=document.createElement('video'); video.playsInline=true;video.preload='metadata';
 video.poster=button.dataset.poster || '/assets/reel-'+button.dataset.reel+'.jpg';
 video.src=button.dataset.src || '/assets/reel-'+button.dataset.reel+'.mp4';
 video.setAttribute('aria-label',button.dataset.title || 'فيديو الدكتور');
 document.querySelector('#video-container').replaceChildren(video);createPlayer(video);
 videoDialog.querySelector('h3').textContent=button.dataset.title || 'فيديو الدكتور';
 const sourceLink=videoDialog.querySelector('a');sourceLink.hidden=!/^\d+$/.test(button.dataset.reel || '');if(!sourceLink.hidden)sourceLink.href='https://www.facebook.com/reel/'+encodeURIComponent(button.dataset.reel)+'/';
 videoDialog.showModal();video.play().catch(()=>{ /* The visible play button permits playback if autoplay is blocked. */ });
}));
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
