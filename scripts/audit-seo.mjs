import {mkdtempSync,mkdirSync,copyFileSync,writeFileSync,existsSync,readdirSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join,resolve} from 'node:path';
import {spawn} from 'node:child_process';
import {createServer} from 'node:net';
import {chromium} from 'playwright-core';
import lighthouse from 'lighthouse';
import {launch} from 'chrome-launcher';
const installedChrome=chromium.executablePath();
const cache=join(process.env.HOME,'Library/Caches/ms-playwright');
const chromePath=process.env.CHROME_PATH || (existsSync(installedChrome)?installedChrome:join(cache,readdirSync(cache).filter(p=>/^chromium-/.test(p)).sort().at(-1),'chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing'));
const temp=mkdtempSync(join(tmpdir(),'helal-e2e-audit-'));
copyFileSync('modules_statuses.json',join(temp,'modules.json'));
for(const path of ['framework/cache/data','framework/sessions','framework/views','logs','app/public'])mkdirSync(join(temp,'storage',path),{recursive:true});
const port=await new Promise(resolve=>{const server=createServer();server.listen(0,'127.0.0.1',()=>{const port=server.address().port;server.close(()=>resolve(port));});});
const base='http://127.0.0.1:'+port;
const env={...process.env,APP_ENV:'testing',CLINIC_E2E:'1',CLINIC_MODULE_STATUSES:join(temp,'modules.json'),APP_DEBUG:'false',APP_KEY:'base64:MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDA=',APP_URL:base,ASSET_URL:base,DB_CONNECTION:'sqlite',DB_DATABASE:join(temp,'database.sqlite'),LARAVEL_STORAGE_PATH:join(temp,'storage'),SESSION_DRIVER:'file',CACHE_STORE:'array',QUEUE_CONNECTION:'sync',MAIL_MAILER:'array',AI_ENABLED:'false',WHATSAPP_ENABLED:'false'};
const server=spawn('php',['tests/e2e-support/serve.php',String(port)],{env,stdio:'ignore',detached:true});
const output=resolve('.e2e/audits');mkdirSync(output,{recursive:true});
let browser,chrome;
try {
 for(let i=0;i<100;i++){try{if((await fetch(base+'/up')).ok)break;}catch{}await new Promise(r=>setTimeout(r,200));}
 await fetch(base+'/__e2e/multipage');
 const state=await(await fetch(base+'/__e2e/state')).json();
 // Audit published pages, never noindex previews. Changes affect only this disposable test DB.
 browser=await chromium.launch({headless:true,executablePath:chromePath});
 const page=await browser.newPage({viewport:{width:1440,height:1000}});
 await page.route(/^https?:\/\/(?!127\.0\.0\.1|localhost)/,r=>r.abort());
 chrome=await launch({chromePath,chromeFlags:['--headless','--no-sandbox','--disable-dev-shm-usage','--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1, EXCLUDE localhost']});
 const reports=[];
 for(const theme of ['helal_classic','helal_modern']) {
  await fetch(base+'/__e2e/audit-theme?theme='+theme);
  for(const [kind,path] of [['home','/'],['service','/services/'+state.services[0].slug],['article','/blog/'+state.articles[0].slug],['branch','/branches/'+state.branches[0].slug]]) {
   const {lhr}=await lighthouse(base+path,{port:chrome.port,output:'json',logLevel:'error',onlyCategories:['performance','seo'],throttlingMethod:'simulate',disableStorageReset:true});
   const report={theme,kind,path,seo:Math.round(lhr.categories.seo.score*100),performance:Math.round(lhr.categories.performance.score*100),lcp:lhr.audits['largest-contentful-paint'].numericValue,cls:lhr.audits['cumulative-layout-shift'].numericValue,issues:Object.values(lhr.audits).filter(a=>a.score!==null&&a.score<1&&a.scoreDisplayMode!=='informative').map(a=>({id:a.id,title:a.title,score:a.score}))};
   reports.push(report);writeFileSync(join(output,theme+'-'+kind+'.json'),JSON.stringify(lhr,null,2));console.log(JSON.stringify(report));
   await page.goto(base+path);await page.screenshot({path:join(output,theme+'-'+kind+'-1440.png'),fullPage:true});
   if(kind==='home'||kind==='branch'){await page.setViewportSize({width:390,height:844});await page.screenshot({path:join(output,theme+'-'+kind+'-390.png'),fullPage:true});await page.setViewportSize({width:1440,height:1000});}
  }
 }
 writeFileSync(join(output,'summary.json'),JSON.stringify({date:new Date().toISOString(),environment:'Local disposable SQLite, Lighthouse simulated mobile, external network blocked; not field Core Web Vitals.',reports},null,2));
 if(reports.some(r=>r.seo<95))process.exitCode=1;
} finally {await browser?.close();await chrome?.kill();try{process.kill(-server.pid,'SIGTERM');}catch{}}
