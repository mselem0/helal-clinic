import {test} from '@e2e-dev/web';
import {expect,credentials} from 'e2e';

test.beforeEach(async({app,browser})=>{
 await browser.route(/^https?:\/\/(?!127\.0\.0\.1|localhost)/,route=>route.abort());
 await app.open('/__e2e/reset');await app.open('/__e2e/multipage');
});
async function login(app:any,browser:any,screen:any){
 await app.open('/codeAiAdminDash/login');await browser.locator('[id="data.email"]').fill(credentials.user('admin').username);await browser.locator('[id="data.password"]').fill(credentials.user('admin').password);await screen.getByRole('button','تسجيل الدخول',{exact:true}).click();await expect(browser).toHaveURL(/codeAiAdminDash$/);
}
for(const width of [320,390,768,1024,1440]) {
 test(`all page templates and both themes are usable at ${width}px`,async({app,browser})=>{
  await browser.setViewport({width,height:1000});
  for(const theme of ['helal_classic','helal_modern']){
   for(const key of ['home','about','services','journey','videos','articles','branches','faq','contact','appointment','kind:service','kind:article','kind:branch']){
    const query=key.startsWith('kind:')?'kind='+key.split(':')[1]:'page='+key;
    const url=await browser.evaluate(async(query)=>(await(await fetch('/__e2e/preview?'+query)).json()).url,query+'&theme='+theme);
    await app.open(url);await expect(browser.locator('h1')).toBeVisible();
    const state=await browser.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth+1,h1:document.querySelectorAll('h1').length,title:document.querySelectorAll('title').length,canonical:document.querySelectorAll('link[rel=canonical]').length,theme:document.documentElement.dataset.theme,broken:[...document.images].filter(i=>i.complete&&i.naturalWidth===0&&i.getAttribute('src')).map(i=>i.src)}));
    expect(state.overflow).toBe(false);expect(state.h1).toBe(1);expect(state.title).toBe(1);expect(state.canonical).toBe(1);expect(state.theme).toBe(theme);expect(state.broken).toEqual([]);
   }
  }
 });
}
test('page and menu drafts publish together, rename redirects and revision restores them',async({app,browser,screen})=>{
 await login(app,browser,screen);await app.open('/codeAiAdminDash/site-pages/create');
 await browser.locator('[id="data.title"]').fill('صفحة اختبار متعددة');await browser.locator('[id="data.slug"]').fill('e2e-custom-page');await browser.locator('[id="data.intro"]').fill('مقدمة اختبار الصفحة');await browser.locator('[id="data.meta_title"]').fill('عنوان بحث اختبار مستقل');await browser.locator('[id="data.meta_description"]').fill('وصف بحث خاص بالصفحة الجديدة');await browser.locator('[id="data.is_published"]').click();
 await screen.getByRole('button','إضافة',{exact:true}).click();await expect(browser).toHaveURL(/site-pages\/\d+\/edit/);
 let state=await browser.evaluate(async()=>await(await fetch('/__e2e/state')).json());let page=state.pages.find((p:any)=>p.slug==='e2e-custom-page');expect(page.is_published).toBe(false);
 expect(await browser.evaluate(async()=> (await fetch('/e2e-custom-page')).status)).toBe(404);
 await app.open('/codeAiAdminDash/theme-editor');await browser.onDialog('accept');await screen.getByRole('button','نشر الموقع',{exact:true}).click();await expect(browser.locator('[data-testid="revision-select"] option')).toHaveCount(2);
 await app.open('/e2e-custom-page');await expect(browser.locator('h1')).toContainText('صفحة اختبار متعددة');
 await app.open('/codeAiAdminDash/site-pages/'+page.id+'/edit');await browser.locator('[id="data.slug"]').fill('e2e-renamed-page');await screen.getByRole('button','حفظ التغييرات',{exact:true}).click();await expect(screen.getByText('تم حفظ المسودة — النشر من استوديو الموقع',{exact:true})).toBeVisible();
 await app.open('/codeAiAdminDash/site-navigations/1/edit');await browser.locator('input[id$=".label"]').first().fill('رئيسية الاختبار');await screen.getByRole('button','حفظ التغييرات',{exact:true}).click();await expect(screen.getByText('تم حفظ المسودة — النشر من استوديو الموقع',{exact:true})).toBeVisible();
 await app.open('/');await expect(browser.locator('#main-menu')).not.toContainText('رئيسية الاختبار');
 await app.open('/codeAiAdminDash/theme-editor');await screen.getByRole('button','نشر الموقع',{exact:true}).click();await expect(browser.locator('[data-testid="revision-select"] option')).toHaveCount(3);
 await app.open('/e2e-custom-page');await expect(browser).toHaveURL(/e2e-renamed-page$/);await expect(browser.locator('#main-menu')).toContainText('رئيسية الاختبار');
 await app.open('/codeAiAdminDash/theme-editor');await browser.locator('[data-testid="revision-select"]').selectOption({value:'2'});await screen.getByRole('button','استعادة',{exact:true}).click();await expect(browser.locator('[data-testid="revision-select"] option')).toHaveCount(4);
 await app.open('/e2e-custom-page');await expect(browser).toHaveURL(/e2e-custom-page$/);await expect(browser.locator('#main-menu')).not.toContainText('رئيسية الاختبار');
});
test('public structured metadata, sitemap, related links and draft protection work',async({app,browser})=>{
 await app.open('/blog/article-1');
 const metadata=await browser.evaluate(()=>({title:document.title,canonical:document.querySelector('link[rel=canonical]')?.getAttribute('href'),schemas:JSON.parse(document.querySelector('script[type="application/ld+json"]')!.textContent!)['@graph']}));
 expect(metadata.canonical).toContain('/blog/article-1');expect(metadata.schemas.some((s:any)=>s['@type']==='Article')).toBe(true);
 await expect(browser.locator('.article-meta')).toContainText('مراجع اختبار');
 const responses=await browser.evaluate(async()=>({sitemap:await(await fetch('/sitemap.xml')).text(),robots:await(await fetch('/robots.txt')).text(),unsigned:(await fetch('/site-preview/1?preview=1')).status,missing:(await fetch('/not-a-real-page')).status}));
 expect(responses.sitemap).toContain('/branches/');expect(responses.sitemap).toContain('lastmod');expect(responses.robots).toContain('Disallow: /site-preview/');expect(responses.unsigned).toBe(403);expect(responses.missing).toBe(404);
 await app.open('/services/service-1');await expect(browser.locator('.related-panel')).toContainText('السادس من أكتوبر');
});

test('doctor qualifications and specialties render clearly in both themes',async({app,browser})=>{
 for(const theme of ['helal_classic','helal_modern']){
  const url=await browser.evaluate(async theme=>(await(await fetch('/__e2e/preview?page=about&theme='+theme)).json()).url,theme);
  await app.open(url);
  await expect(browser.locator('.doctor-profile')).toContainText('دكتوراه أمراض النساء والتوليد');
  await expect(browser.locator('.profile-memberships')).toContainText('الجمعية الأوروبية للخصوبة والعقم');
  expect(await browser.locator('.profile-specialties li').count()).toBe(12);
  expect(await browser.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1)).toBe(true);
 }
});
