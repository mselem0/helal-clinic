import { test } from '@e2e-dev/web';
import { expect, credentials } from 'e2e';

test.beforeEach(async ({ app, browser }) => {
  await app.open('/__e2e/reset');
  await browser.route(/^https?:\/\/(?!127\.0\.0\.1|localhost)/, route => route.abort());
});

async function login(app: any, screen: any, browser: any, name = 'admin') {
  await app.open('/codeAiAdminDash/login');
  await browser.locator('input[id="data.email"]').fill(credentials.user(name).username);
  await browser.locator('input[id="data.password"]').fill(credentials.user(name).password);
  await screen.getByRole('button', 'تسجيل الدخول', { exact: true }).click();
  if(name === 'admin') await expect(browser).toHaveURL(/codeAiAdminDash$/);
}

for (const width of [320, 390, 768, 1024, 1440]) {
  test(`classic and modern themes fit viewport ${width}px`, async ({ app, browser, screen }) => {
    await browser.setViewport({ width, height: 1000 });
    for (const theme of ['helal_classic', 'helal_modern']) {
      await app.open('/');
      const url = await browser.evaluate(async (theme) => (await (await fetch('/__e2e/preview?theme='+theme)).json()).url, theme);
      await app.open(url);
      await expect(browser.locator('h1')).toBeVisible();
      expect(await browser.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
      expect(await browser.evaluate(() => [...document.images].filter(i=>i.complete && i.naturalWidth===0).map(i=>i.src))).toEqual([]);
    }
  });
}

test('mobile menu opens, follows a section and closes', async ({ app, browser, screen }) => {
  await browser.setViewport({width:390,height:844});await app.open('/');
  await browser.locator('.menu-toggle').click();
  await expect(browser.locator('#main-menu')).toHaveAttribute('class', /menu-open/);
  await browser.locator('#main-menu a[href$="/services"]').click();
  await expect(browser).toHaveURL(/\/services$/);
  await expect(browser.locator('.menu-toggle')).toHaveAttribute('aria-expanded','false');
});

test('video opens in the viewer and closes back to its preview', async ({ app, browser }) => {
  await app.open('/');await browser.locator('#videos .tile-art button').first().click();
  await expect(browser.locator('#video-dialog')).toBeVisible();
  expect(await browser.evaluate(() => document.querySelector('#video-dialog iframe')?.getAttribute('src')?.startsWith('https://player.vimeo.com/video/'))).toBe(true);
  await browser.locator('#video-dialog .close').click();await expect(browser.locator('#video-dialog')).not.toBeVisible();
  await expect(browser.locator('#videos .tile-art button').first()).toBeVisible();
});

test('Vimeo videos load only on click and closing removes the external player', async ({ app, browser }) => {
  for (const theme of ['helal_classic', 'helal_modern']) {
    await app.open('/');
    const preview = await browser.evaluate(async theme => (await (await fetch('/__e2e/preview?theme='+theme)).json()).url, theme);
    await app.open(preview);
    expect(await browser.locator('iframe[src*="vimeo.com"]').count()).toBe(0);
    const count = await browser.locator('#videos .tile-art button').count();
    for (let index = -1; index < count; index++) {
      await (index < 0 ? browser.locator('.feature-preview') : browser.locator('#videos .tile-art button').nth(index)).click();
      await expect(browser.locator('#video-dialog iframe')).toHaveAttribute('src', /^https:\/\/player\.vimeo\.com\/video\/12367865[0-9]+\?autoplay=1&dnt=1&title=0&byline=0&portrait=0&badge=0&color=008b91&playsinline=1$/);
      expect(await browser.locator('#video-dialog video').count()).toBe(0);
      await browser.locator('#video-dialog .close').click();
      expect(await browser.locator('#video-dialog iframe').count()).toBe(0);
    }
  }
});

test('video arrows navigate the full library circularly on desktop and mobile', async ({ app, browser }) => {
  for (const width of [390, 1440]) {
    await browser.setViewport({width,height:900});
    await app.open('/');
    const titles = await browser.evaluate(() => [...document.querySelectorAll('[data-video-library] [data-reel]')].map(item => (item as HTMLElement).dataset.title));
    expect(titles.length).toBeGreaterThan(3);
    await browser.locator('#videos .tile-art button').first().click();
    await expect(browser.locator('#video-dialog-title')).toHaveText(titles[0]);
    await app.screenshot('video-navigation-'+width);
    await browser.locator('[data-video-step="-1"]').click();
    await expect(browser.locator('#video-dialog-title')).toHaveText(titles[titles.length-1]);
    await browser.locator('[data-video-step="1"]').click();
    await expect(browser.locator('#video-dialog-title')).toHaveText(titles[0]);
    for (let i=1;i<titles.length;i++) {
      await browser.locator('[data-video-step="1"]').click();
      await expect(browser.locator('#video-dialog-title')).toHaveText(titles[i]);
      expect(await browser.locator('#video-container iframe').count()).toBe(1);
    }
    expect(await browser.evaluate(() => [...document.querySelectorAll('.reel-nav')].every(button => {const r=button.getBoundingClientRect();return r.left>=0 && r.right<=innerWidth && r.top>=0 && r.bottom<=innerHeight;}))).toBe(true);
    await browser.locator('#video-dialog .close').click();
    expect(await browser.evaluate(() => document.activeElement===document.querySelector('#videos .tile-art button'))).toBe(true);
  }
});

test('journey stages update the questions and select the booking service', async ({ app, browser }) => {
  await app.open('/patient-journey');await browser.locator('[data-stage="health"]').click();
  await expect(browser.locator('#journey-title')).toContainText('ابدئي');
  await browser.locator('#journey-book').click();await expect(browser).toHaveURL(/patient-journey/);await expect(browser.locator('#journey-booking-panel')).toBeVisible();
  expect(await browser.evaluate(() => (document.querySelector('#service') as HTMLSelectElement).selectedOptions[0].textContent)).toContain('المناظير');
});

test('journey retains personal questions per stage and when returning from booking', async({app,browser})=>{
 await app.open('/patient-journey');
 await browser.locator('#journey-personal-question').fill('سؤالي الخاص بالحمل؟');await browser.locator('#journey-question-form button').click();
 await browser.locator('[data-stage="waiting"]').click();await expect(browser.locator('#journey-questions')).not.toContainText('سؤالي الخاص بالحمل؟');
 await browser.locator('#journey-personal-question').fill('سؤالي الخاص بتأخر الإنجاب؟');await browser.locator('#journey-question-form button').click();
 await browser.locator('[data-stage="planning"]').click();await expect(browser.locator('#journey-questions')).toContainText('سؤالي الخاص بالحمل؟');
 await expect(browser.locator('#journey-questions')).not.toContainText('سؤالي الخاص بتأخر الإنجاب؟');
 await browser.locator('#journey-name').fill('سارة');await browser.locator('#journey-book').click();
 expect(await browser.evaluate(()=>(document.querySelector('#patient') as HTMLInputElement).value)).toBe('سارة');
 await browser.locator('#journey-return').click();await expect(browser.locator('#journey-questions')).toContainText('سؤالي الخاص بالحمل؟');
 expect(await browser.locator('#video-dialog iframe').count()).toBe(0);await browser.locator('#journey-video').click();await expect(browser.locator('#video-dialog')).toBeVisible();
});

test('admin branch schedule controls reveal weekly hours', async ({app,browser,screen})=>{
 await login(app,screen,browser);await app.open('/codeAiAdminDash/clinic-branches/1/edit');
 await browser.locator('[id="data.booking_schedule_enabled"]').click();
 await browser.locator('[id="data.weekly_schedule.0.open"]').click();
 await browser.locator('[id="data.weekly_schedule.0.from"]').fill('17:00');
 await browser.locator('[id="data.weekly_schedule.0.to"]').fill('21:00');
 await expect(browser.locator('[id="data.weekly_schedule.0.to"]')).toBeVisible();
});

test('branch schedules show daily hours and disable closed dates on mobile and desktop', async ({app,browser})=>{
 for(const width of [390,1440]) {
  await browser.setViewport({width,height:1000});await app.open('/appointment');
  const day=await browser.evaluate(()=>{
   const day=(document.querySelector('#date') as HTMLInputElement).min;
   (window as any).helalBooking.schedules={'1':{enabled:true,weekly:{},exceptions:[{date:day,closed:false,from:'17:00',to:'21:00'}]},'2':{enabled:true,weekly:{},exceptions:[]}};
   return day;
  });
  await browser.locator('#clinic-choice').click();await browser.locator('#clinic-choice-options-1').click();
  await browser.locator('#service-choice').click();await browser.locator('#service-choice-options-1').click();
  await browser.locator('#booking-next').click();
  expect(await browser.locator('#calendar-days button:not([disabled])').count()).toBe(1);
  await browser.locator('[data-date="'+day+'"]').click();
  await expect(browser.locator('#booking-hours')).toContainText('من 5:00 مساءً إلى 9:00 مساءً');
  await app.screenshot('branch-hours-'+width);
  await browser.locator('#booking-next').click();await expect(browser.locator('#appointment-summary')).toContainText('من 5:00');
  await browser.locator('#booking-back').click();await browser.locator('#booking-back').click();
  await browser.locator('#clinic-choice').click();await browser.locator('#clinic-choice-options-2').click();
  await browser.locator('#booking-next').click();
  expect(await browser.locator('#calendar-days button:not([disabled])').count()).toBe(0);
  expect(await browser.evaluate(()=>(document.querySelector('#date') as HTMLInputElement).value)).toBe('');
 }
});

test('booking validates, hands off to WhatsApp and resets the wizard', async ({ app, browser }) => {
  await browser.route(/^https:\/\/wa\.me\//, route => route.fulfill({status:200,contentType:'text/html',body:'<p>WhatsApp handoff intercepted for testing.</p>'}));
  await app.open('/appointment');await browser.locator('#booking-next').click();await expect(browser.locator('#booking-error')).toContainText('اختاري');
  await browser.locator('#clinic-choice').click();await browser.locator('#clinic-choice-options-1').click();await browser.locator('#service-choice').click();await browser.locator('#service-choice-options-1').click();await browser.locator('#booking-next').click();
  await browser.locator('#calendar-days button:not([disabled])').first().click();await browser.locator('#booking-next').click();
  await browser.locator('#patient').fill('مريضة اختبار');await browser.locator('#appointment-phone').fill('01012345678');
  await browser.evaluate(()=>{const popup={opener:window,closed:false,location:{href:''},close(){this.closed=true;}};(window as any).__bookingPopup=popup;window.open=()=>popup as any;});
  await browser.locator('#booking-confirm').click();await expect(browser.locator('#clinic-choice')).toBeVisible();
  await expect(browser).toHaveURL(/\/appointment$/);
  const reset=await browser.evaluate(()=>({clinic:(document.querySelector('#clinic') as HTMLSelectElement).value,service:(document.querySelector('#service') as HTMLSelectElement).value,patient:(document.querySelector('#patient') as HTMLInputElement).value,phone:(document.querySelector('#appointment-phone') as HTMLInputElement).value,date:(document.querySelector('#date') as HTMLInputElement).value,stepOne:!(document.querySelector('[data-step="1"]') as HTMLElement).hidden}));
  expect(reset).toEqual({clinic:'',service:'',patient:'',phone:'',date:'',stepOne:true});
  const popup=await browser.evaluate(()=>({url:(window as any).__bookingPopup.location.href,opener:(window as any).__bookingPopup.opener}));
  expect(popup.url).toContain('https://wa.me/201017900354');expect(popup.opener).toBe(null);
  const state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());
  expect(state.bookings.length).toBe(state.full ? 1 : 0);
  if(state.full)expect(state.bookings[0].status).toBe('new');
  await expect(browser.locator('#clinic-choice')).toBeVisible();
});

test('booking preserves details when the browser blocks the new tab or request fails',async({app,browser})=>{
 await app.open('/appointment');
 await browser.locator('#clinic-choice').click();await browser.locator('#clinic-choice-options-1').click();
 await browser.locator('#service-choice').click();await browser.locator('#service-choice-options-1').click();await browser.locator('#booking-next').click();
 await browser.locator('#calendar-days button:not([disabled])').first().click();await browser.locator('#booking-next').click();
 await browser.locator('#patient').fill('مريضة اختبار');await browser.locator('#appointment-phone').fill('01012345678');
 await browser.evaluate(()=>{window.open=()=>null;});
 await browser.locator('#booking-confirm').click();await expect(browser.locator('#booking-error')).toContainText('اسمحي بفتح تاب جديدة');
 expect(await browser.evaluate(()=>(document.querySelector('#patient') as HTMLInputElement).value)).toBe('مريضة اختبار');
 await browser.evaluate(()=>{const popup={opener:window,closed:false,location:{href:''},close(){this.closed=true;}};(window as any).__bookingPopup=popup;window.open=()=>popup as any;});
 await browser.route('**/bookings',route=>route.fulfill({status:422,contentType:'application/json',body:JSON.stringify({errors:{booking_date:['اليوم غير متاح']}})}));
 await browser.locator('#booking-confirm').click();await expect(browser.locator('#booking-error')).toContainText('اليوم غير متاح');
 expect(await browser.evaluate(()=>(window as any).__bookingPopup.closed)).toBe(true);
 expect(await browser.evaluate(()=>(document.querySelector('#patient') as HTMLInputElement).value)).toBe('مريضة اختبار');
});

test('contact message clears and panel closes only after opening WhatsApp', async({app,browser})=>{
 await app.open('/');await browser.locator('#clinic-chat-toggle').click();
 await browser.locator('#clinic-chat-message').fill('  سؤال عن المتابعة  ');
 await browser.evaluate(()=>{window.open=()=>null;});
 await browser.locator('.clinic-chat-form button[type="submit"]').click();
 await expect(browser.locator('#clinic-chat')).toBeVisible();
 expect(await browser.evaluate(()=>(document.querySelector('#clinic-chat-message') as HTMLTextAreaElement).value)).toBe('  سؤال عن المتابعة  ');
 await browser.evaluate(()=>{window.open=(url)=>{(window as any).__chatHandoff=String(url);return {opener:window} as any;};});
 await browser.locator('.clinic-chat-form button[type="submit"]').click();
 await expect(browser.locator('#clinic-chat')).not.toBeVisible();
 expect(await browser.evaluate(()=>new URL((window as any).__chatHandoff).searchParams.get('text'))).toBe('سؤال عن المتابعة');
 await browser.locator('#clinic-chat-toggle').click();
 expect(await browser.evaluate(()=>(document.querySelector('#clinic-chat-message') as HTMLTextAreaElement).value)).toBe('');
});

test('unsigned previews and disabled modules cannot be reached directly', async ({ app, browser }) => {
  await app.open('/');
  const statuses=await browser.evaluate(async()=>({preview:(await fetch('/?preview=1')).status,print:(await fetch('/admin/prescriptions/1/print')).status}));
  expect(statuses.preview).toBe(403);expect([200,404]).toContain(statuses.print);
});

test('real admin login opens dashboard and all registered content screens', async ({ app, browser, screen }) => {
  await login(app,screen,browser);await expect(browser).toHaveURL(/codeAiAdminDash$/);
  for(const path of ['site-pages','site-navigations','theme-editor','services','clinic-branches','videos','testimonials','articles','seo-management']){
    await app.open('/codeAiAdminDash/'+path);
    expect(await browser.evaluate(()=>document.body.innerText.includes('Server Error'))).toBe(false);
    expect(await browser.evaluate(()=>document.querySelector('main')!==null)).toBe(true);
  }
});

test('wrong password cannot open admin dashboard', async ({ app, browser, screen }) => {
  await app.open('/codeAiAdminDash/login');await browser.locator('input[id="data.email"]').fill('admin@example.test');
  await browser.locator('input[id="data.password"]').fill('wrong-password');await screen.getByRole('button','تسجيل الدخول',{exact:true}).click();
  await expect(browser).toHaveURL(/login/);await expect(browser.locator('form')).toContainText(/غير|not match|خطأ|متطابقة/);
});

test('patient cannot enter the admin panel with valid credentials', async ({ app, browser, screen }) => {
  await login(app,screen,browser,'visitor');await expect(browser).toHaveURL(/login/);
});

test('editing a theme is separate from publishing and revisions restore the previous theme', async ({ app, browser, screen }) => {
  await login(app,screen,browser);await app.open('/codeAiAdminDash/theme-editor');
  await browser.onDialog('accept');
  await browser.locator('[data-theme-choice="helal_modern"]').click();
  let state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());expect(state.theme).toBe('helal_classic');
  await screen.getByRole('button','نشر الموقع',{exact:true}).click();
  await expect(browser.locator('[data-theme-status="helal_modern"]')).toContainText('الثيم المنشور حاليًا');
  state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());expect(state.theme).toBe('helal_modern');expect(state.revisions).toBe(1);
  await browser.locator('[data-testid="revision-select"]').selectOption({value:'1'});
  await screen.getByRole('button','استعادة',{exact:true}).click();await expect(browser.locator('[data-theme-status="helal_classic"]')).toContainText('الثيم المنشور حاليًا');
  state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());expect(state.theme).toBe('helal_classic');expect(state.revisions).toBe(2);
});


test('service create, edit, upload and delete update the actual public site', async ({ app, browser, screen }) => {
  await login(app,screen,browser);await app.open('/codeAiAdminDash/services/create');
  await browser.locator('input[id="data.name"]').fill('خدمة اختبار شاملة');
  await browser.locator('input[id="data.slug"]').fill('e2e-service');
  await browser.locator('textarea[id="data.description"]').fill('وصف خدمة اختبار من لوحة التحكم');
  await browser.locator('.fi-fo-file-upload .filepond--browser').first().setInputFiles('public/assets/service-pregnancy.jpg');
  await expect(browser.locator('.filepond--item[data-filepond-item-state="processing-complete"]')).toBeVisible({timeout:10000});
  await screen.getByRole('button','إضافة',{exact:true}).click();await expect(browser).toHaveURL(/services\/\d+\/edit/);
  const state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());const item=state.services.find((s:any)=>s.slug==='e2e-service');expect(item).toBeDefined();
  await app.open('/');await expect(screen.getByRole('heading','خدمة اختبار شاملة')).toBeVisible();
  await app.open('/codeAiAdminDash/services/'+item.id+'/edit');
  await browser.locator('input[id="data.name"]').fill('خدمة معدلة');await browser.locator('input[id="data.card_title"]').fill('خدمة معدلة');
  await screen.getByRole('button','حفظ التغييرات',{exact:true}).click();
  await expect(screen.getByText('تم الحفظ',{exact:true})).toBeVisible({timeout:10000});
  await app.open('/');await expect(screen.getByRole('heading','خدمة معدلة')).toBeVisible();
  await app.open('/codeAiAdminDash/services/'+item.id+'/edit');await screen.getByRole('button','حذف',{exact:true}).click();
  await browser.locator('.fi-modal-window button[type="submit"]').click();await expect(browser).toHaveURL(/services$/);
  await app.open('/');await expect(screen.getByRole('heading','خدمة معدلة')).not.toBeVisible();
});

test('content drafts stay private, preview shows them and discard keeps published content', async ({ app, browser, screen }) => {
  await login(app,screen,browser);await app.open('/codeAiAdminDash/theme-editor');await browser.onDialog('accept');
  await browser.locator('[data-section-choice="faq"]').click();
  await screen.getByRole('button','توسيع الكل',{exact:true}).click();
  await browser.locator('textarea').first().fill('عنوان FAQ للمراجعة');
  await screen.getByRole('button','حفظ المسودة',{exact:true}).click();
  await expect(browser.locator('[data-section-choice="faq"]')).toContainText('مسودة');
  await app.open('/');await expect(screen.getByText('عنوان FAQ للمراجعة',{exact:true})).not.toBeVisible();
  const url=await browser.evaluate(async()=> (await(await fetch('/__e2e/preview?page=faq')).json()).url);await app.open(url);
  await expect(screen.getByText('عنوان FAQ للمراجعة',{exact:true})).toBeVisible();
  await app.open('/codeAiAdminDash/theme-editor');await screen.getByRole('button','إلغاء المسودات',{exact:true}).click();
  await expect(browser.locator('[data-section-choice="faq"]')).not.toContainText('مسودة');
  await app.open('/');await expect(screen.getByText('عنوان FAQ للمراجعة',{exact:true})).not.toBeVisible();
});

test('all registered basic management routes have working list and create forms', async ({ app, browser, screen }) => {
  await login(app,screen,browser);
  for(const resource of ['services','clinic-branches','videos','testimonials','articles','seo-pages','seo-redirects','seo-schemas']) {
    for(const suffix of ['', '/create']) {
      await app.open('/codeAiAdminDash/'+resource+suffix);
      expect(await browser.evaluate(()=>document.querySelector('main')!==null)).toBe(true);
    }
  }
});


for(const scenario of [
  {resource:'clinic-branches', fields:{name:'فرع اختبار',address:'عنوان تجريبي للاختبار'}, text:'فرع اختبار'},
  {resource:'testimonials',fields:{patient_name:'صاحبة مراجعة اختبار',review_text:'مراجعة اختبار من لوحة الإدارة'},text:'مراجعة اختبار من لوحة الإدارة'},
  {resource:'videos',fields:{title:'فيديو اختبار جديد',description:'وصف فيديو اختبار',reel:'1122351437148430'},text:'فيديو اختبار جديد'},
  {resource:'articles',fields:{title:'مقال اختبار منشور',slug:'e2e-article'},text:'مقال اختبار منشور'},
]) {
 test(`creating ${scenario.resource} updates its public section`,async({app,browser,screen})=>{
   await login(app,screen,browser);await app.open('/codeAiAdminDash/'+scenario.resource+'/create');
   for(const [field,value] of Object.entries(scenario.fields)) await browser.locator('[id="data.'+field+'"]').fill(value);
   await screen.getByRole('button','إضافة',{exact:true}).click();await expect(browser).toHaveURL(new RegExp(scenario.resource+'/\\d+/edit'));
   await app.open('/'+({'clinic-branches':'branches',testimonials:'',videos:'gallery',articles:'blog'}[scenario.resource]));await expect(screen.getByText(scenario.text,{exact:true}).first()).toBeVisible();
 });
}

test('admin pages fit mobile viewport and content remains operable',async({app,browser,screen})=>{
 await browser.setViewport({width:390,height:844});await login(app,screen,browser);await app.open('/codeAiAdminDash/theme-editor');
 await expect(browser.locator('[data-section-choice="faq"]')).toBeVisible();
 expect(await browser.evaluate(()=>document.documentElement.scrollWidth <= innerWidth+1)).toBe(true);
 await browser.locator('[data-section-choice="faq"]').click();await screen.getByRole('button','حفظ المسودة',{exact:true}).click();
 await expect(browser.locator('[data-section-choice="faq"]')).toContainText('مسودة');
});


test('full system exposes all seven modules only on its dedicated branch',async({app,browser,screen})=>{
 await login(app,screen,browser);
 const state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());
 if(!state.full) {
  for(const path of ['patients','bookings','print-templates','users','roles','activity-logs','whatsapp-logs','knowledge-chunks']) {
   const status=await browser.evaluate(async(path)=>(await fetch('/codeAiAdminDash/'+path)).status,path);expect(status).toBe(404);
  }
  return;
 }
 expect(await browser.evaluate(async()=> (await fetch('/patient-auth')).status)).toBe(200);
 for(const path of ['patients','bookings','print-templates','users','roles','activity-logs','whatsapp-templates','knowledge-chunks','ai-assistant','ai-memory-management','whatsapp-conversations','clinic-system-status']) {
  await app.open('/codeAiAdminDash/'+path);
  expect(await browser.evaluate(()=>document.querySelector('main')!==null)).toBe(true);
 }
});

test('full system patient can be created and a clinical visit can be saved from profile',async({app,browser,screen})=>{
 await login(app,screen,browser);
 const state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());
 if(!state.full) { expect((await browser.evaluate(async()=>await fetch('/codeAiAdminDash/patients/create').then(r=>r.status)))).toBe(404);return; }
 await app.open('/codeAiAdminDash/patients/create');await browser.locator('input[id="data.name"]').fill('مريضة E2E');await browser.locator('input[id="data.phone"]').fill('01112345678');
 await screen.getByRole('button','إضافة',{exact:true}).click();await expect(browser).toHaveURL(/patients\/\d+\/edit/);
 const id=await browser.evaluate(()=>location.pathname.split('/').at(-2)!);await app.open('/codeAiAdminDash/patients/'+id+'/profile');
 await expect(screen.getByRole('heading',/الملف الطبي للمريض/)).toBeVisible();
 await browser.locator('[wire\\:click="openNewVisitModal"]').click();
 await browser.locator('[wire\\:model="chiefComplaint"]').fill('شكوى اختبار');
 await screen.getByRole('button','تسجيل وحفظ الكشف',{exact:true}).click();
 await expect(screen.getByText('شكوى اختبار',{exact:true}).first()).toBeVisible();
});


test('FAQ questions can be added and published from the editor',async({app,browser,screen})=>{
 await login(app,screen,browser);await app.open('/codeAiAdminDash/theme-editor');await browser.onDialog('accept');
 await browser.locator('[data-section-choice="faq"]').click();await screen.getByRole('button','إضافة سؤال',{exact:true}).click();await expect(browser.locator('input[id$=".question"]')).toHaveCount(4);
 await browser.locator('input[id$=".question"]').last().fill('سؤال جديد للاختبار؟');await browser.locator('textarea[id$=".answer"]').last().fill('إجابة جديدة للاختبار.');
 await screen.getByRole('button','نشر الموقع',{exact:true}).click();await expect(browser.locator('[data-testid="revision-select"] option')).toHaveCount(2);
 await app.open('/frequently-asked-questions');await expect(screen.getByText('سؤال جديد للاختبار؟',{exact:true})).toBeVisible();
});
