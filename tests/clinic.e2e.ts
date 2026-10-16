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
      await expect(screen.getByRole('heading', 'كل مرحلة لها اهتمامها.')).toBeVisible();
      expect(await browser.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
      expect(await browser.evaluate(() => [...document.images].filter(i=>i.complete && i.naturalWidth===0).map(i=>i.src))).toEqual([]);
    }
  });
}

test('mobile menu opens, follows a section and closes', async ({ app, browser, screen }) => {
  await browser.setViewport({width:390,height:844});await app.open('/');
  await browser.locator('.menu-toggle').click();
  await expect(browser.locator('#main-menu')).toHaveAttribute('class', /menu-open/);
  await browser.locator('#main-menu a[href="#services"]').click();
  await expect(browser.locator('.menu-toggle')).toHaveAttribute('aria-expanded','false');
});

test('video player opens the selected local video and closes', async ({ app, browser }) => {
  await app.open('/');await browser.locator('#videos .tile-art button').first().click();
  await expect(browser.locator('#video-dialog')).toBeVisible();
  expect(await browser.evaluate(() => document.querySelector('#video-dialog video')?.getAttribute('src')?.includes('.mp4'))).toBe(true);
  await browser.locator('#video-dialog .close').click();await expect(browser.locator('#video-dialog')).not.toBeVisible();
});

test('journey stages update the questions and select the booking service', async ({ app, browser }) => {
  await app.open('/');await browser.locator('[data-stage="health"]').click();
  await expect(browser.locator('#journey-title')).toContainText('ابدئي');
  await browser.locator('#journey-book').click();
  expect(await browser.evaluate(() => (document.querySelector('#service') as HTMLSelectElement).selectedOptions[0].textContent)).toContain('المناظير');
});

test('booking validates, submits once and makes the pending status explicit', async ({ app, browser, screen }) => {
  await app.open('/');await browser.locator('#booking-next').click();await expect(browser.locator('#booking-error')).toContainText('اختاري');
  await browser.locator('#clinic').selectOption({value:'1'});await browser.locator('#service').selectOption({value:'1'});await browser.locator('#booking-next').click();
  await browser.locator('#calendar-days button:not([disabled])').first().click();await browser.locator('#booking-next').click();
  await browser.locator('#patient').fill('مريضة اختبار');await browser.locator('#appointment-phone').fill('01012345678');
  await browser.locator('#booking-confirm').click();await expect(browser.locator('#appointment-success')).toBeVisible();
  await expect(browser.locator('#success-details')).toContainText(/غير مؤكد|لم يُسجل/);
  await expect(browser.locator('#booking-whatsapp')).toHaveAttribute('href',/^https:\/\/wa.me\/201017900354/);
  const state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());
  expect(state.bookings.length).toBe(state.full ? 1 : 0);
  if(state.full)expect(state.bookings[0].status).toBe('new');
  await browser.locator('#booking-restart').click();await expect(browser.locator('#clinic')).toBeVisible();
});

test('unsigned previews and disabled modules cannot be reached directly', async ({ app, browser }) => {
  await app.open('/');
  const statuses=await browser.evaluate(async()=>({preview:(await fetch('/?preview=1')).status,print:(await fetch('/admin/prescriptions/1/print')).status}));
  expect(statuses.preview).toBe(403);expect([200,404]).toContain(statuses.print);
});

test('real admin login opens dashboard and all registered content screens', async ({ app, browser, screen }) => {
  await login(app,screen,browser);await expect(browser).toHaveURL(/codeAiAdminDash$/);
  for(const path of ['theme-editor','services','clinic-branches','videos','testimonials','articles','seo-management']){
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
  const url=await browser.evaluate(async()=> (await(await fetch('/__e2e/preview')).json()).url);await app.open(url);
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
   await app.open('/');await expect(screen.getByText(scenario.text,{exact:true}).first()).toBeVisible();
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
 await app.open('/');await expect(screen.getByText('سؤال جديد للاختبار؟',{exact:true})).toBeVisible();
});
