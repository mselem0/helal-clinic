# إطلاق نسخة الإعلانات — ads.drmohamedhelal.com

هذه تعليمات تنفيذ لاحق؛ لم يتم الاتصال بالاستضافة أو تغيير DNS. الفرع `codex/ads-site` مستقل عن `main`، ولا يُدمج فيه. يحتوي نفس التصميم والحجز ولوحة الإدارة، مع إسقاط محتوى الموضوعات المستبعدة عند العرض، دون حذف محتوى CMS أو مسوداته. قاعدة البيانات والملفات والكوكيز مستقلة. فيديو البداية ثابت على فيديو «سكر الحمل: طبيعي ولا قيصري؟» السابق، حتى لو ظلت إعدادات الفيديو القديم في القاعدة المنسوخة.

## 1. تجهيز cPanel قبل توجيه الزوار

1. افتح **Domains → Create A New Domain** واكتب `ads.drmohamedhelal.com`.
2. ألغِ **Share document root**. استخدم جذرًا مستقلًا: `/home/drmonwhs/public_html/ads`. لا تستخدم `/home/drmonwhs/public_html` نفسه. اترك أي Redirect فارغًا. إذا رفضت الواجهة المسار، اختر `public_html/ads` النسبي؛ يجب أن تكون النتيجة نفس المسار المستقل.
3. فعّل PHP **8.3** لهذا النطاق. لا تغيّر نسخة الموقع الرئيسي بلا حاجة.
4. من **MySQL Database Wizard** أنشئ قاعدة ومستخدمًا جديدين باسم مميز للإعلانات، واربطهما بصلاحيات القاعدة. لا تدخل بيانات قاعدة الموقع الأساسي في ملف النسخة الجديدة.
5. من **File Manager** أنشئ `/home/drmonwhs/apps/helal-ads` خارج `public_html`. ملفات Laravel و`.env` وGit والنسخ الاحتياطية لا توضع في مجلد الزوار.
6. لا تجعل الصب دومين متاحًا للزوار قبل تجهيز الملفات وحماية noindex. لو موجود بالفعل، فعّل **Directory Privacy** مؤقتًا حتى اكتمال الفحص، ثم أزلها قبل تشغيل الإعلانات كي يستطيع AdsBot الوصول.

مرجع cPanel: https://support.cpanel.net/hc/en-us/articles/360052780313-How-to-create-a-subdomain

## 2. الحصول على نسخة جاهزة من الفرع

الطريقة المفضلة على هذه الاستضافة: بعد رفع الفرع إلى GitHub، افتح **Actions → Test and package ads site (no deployment)** واختر الفرع `codex/ads-site`، ثم **Run workflow**. يمكن أيضًا أن يبدأ تلقائيًا عند دفع الفرع. هذا المسار يختبر ويبني الحزمة فقط؛ لا ينشر على الموقع الأساسي أو على ads.

بعد نجاحه نزّل artifact باسم `helal-ads-release-<commit>`، وافتح ملف ZIP الذي نزّله GitHub على جهازك. بداخله `helal-release.tar.gz` وملف SHA256. ارفع ملف tar.gz عبر File Manager إلى `/home/drmonwhs/apps/helal-ads` وفكّه هناك، ثم انقل الأرشيف وملف checksum إلى مجلد خاص خارج مجلد الزوار. الحزمة تشمل vendor وملفات الواجهة المبنية، ولا تحتاج npm/Composer على الاستضافة.

إذا كنت تريد **سحب الفرع نفسه عبر Git**: من cPanel → Git Version Control أنشئ clone خاصًا في `/home/drmonwhs/apps/helal-ads` باستخدام رابط المستودع الفعلي، ثم من Terminal:

```bash
cd /home/drmonwhs/apps/helal-ads
git fetch origin
git switch --track origin/codex/ads-site
```

المستودع الخاص يحتاج مفتاح وصول قراءة فقط من إعداد GitHub/cPanel. لا تكتب أي مفتاح في المستودع أو المحادثة. **Git pull وحده لا يجهز Laravel**: لا يحتوي Git على `vendor` أو `public/build`. إن لم تتوفر Composer وNode على الاستضافة، استخدم حزمة Actions الخاصة بنفس commit فوق ملفات النسخة الخاصة. إذا توفرت الأدوات، نفّذ داخل النسخة الخاصة فقط `composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader` ثم `npm ci` ثم `npm run build` باستخدام PHP 8.3. افحص commit قبل التشغيل بـ`git branch --show-current` و`git rev-parse HEAD`.

## 3. إعداد Laravel في النسخة الخاصة فقط

من cPanel Terminal، بعد وضع الحزمة:

```bash
cd /home/drmonwhs/apps/helal-ads
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
cp scripts/deploy/ads.env.example .env
```

من File Manager → Show Hidden Files افتح `.env` واملأ **بيانات القاعدة الجديدة**. تأكد من:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ads.drmohamedhelal.com
SESSION_COOKIE=helal_ads_session
SESSION_DOMAIN=ads.drmohamedhelal.com
CACHE_PREFIX=helal_ads_
```

لا تضبط `ASSET_URL` على الدومين الرئيسي؛ اتركه غير موجود. اترك التكاملات الخارجية معطلة كما في المثال. بعد حفظ القاعدة:

```bash
/opt/alt/php83/usr/bin/php artisan key:generate --force
/opt/alt/php83/usr/bin/php artisan migrate --force
/opt/alt/php83/usr/bin/php artisan db:seed --force
/opt/alt/php83/usr/bin/php artisan optimize:clear
/opt/alt/php83/usr/bin/php artisan config:cache
/opt/alt/php83/usr/bin/php artisan view:cache
/opt/alt/php83/usr/bin/php scripts/deploy/install-ads-webroot.php /home/drmonwhs/apps/helal-ads /home/drmonwhs/public_html/ads
/opt/alt/php83/usr/bin/php artisan clinic:create-admin YOUR_EMAIL
```

استبدل `YOUR_EMAIL` ببريد الإدارة. كلمة المرور تدخل تفاعليًا. سكربت جذر الويب يرفض جذر الموقع الرئيسي أو وجود ملفات تطبيق سابقة؛ لا تحذف ملفات عند رفضه قبل معرفة سببها. لا تستخدم سكربت `install-webroot.php` أو سكربتات نشر `main` لهذه النسخة.

`db:seed` يبني نسخة من المحتوى المرفق بالمستودع؛ قد لا تشمل تعديلات أحدث موجودة في CMS الإنتاج. لمطابقة المحتوى الحي، خذ نسخة احتياطية من cPanel واستورد **نسخة** القاعدة إلى القاعدة الجديدة، وانسخ uploads إلى storage الجديدة فقط، ثم شغّل migrate وامسح الكاش. لا تستورد القاعدة داخل قاعدة الموقع الأصلي ولا تربط النسختين بنفس storage. لا يلزم نسخ بيانات المرضى أو المواعيد أو جلسات الدخول؛ الأفضل نقل جداول محتوى الموقع فقط. إسقاط المحتوى المستبعد عند العرض يعمل أيضًا مع القاعدة المنسوخة، لكنه لا يراجع الكلام داخل أي فيديو أو صورة إضافية؛ راجع الوسائط المخصصة قبل نشرها.

للتحديثات اللاحقة: نسخة احتياطية لقاعدة ads أولًا، ثم اسحب **نفس الفرع**/حزمة commit الجديد إلى نفس التطبيق الخاص، دون استبدال `.env` أو `storage` ودون إعادة `key:generate` أو `db:seed`. نفّذ migrate ثم optimize:clear وconfig:cache وview:cache. روابط الجذر تشير إلى `public` الخاصة بالتطبيق وتعرض أصوله الجديدة تلقائيًا؛ لو أضيف مجلد عام جديد يلزم إضافة رابطه بعد مراجعة.

## 4. Cloudflare: منع الأرشفة ثم DNS وHTTPS

قبل تفعيل DNS:

1. اختر `drmohamedhelal.com` → **Rules → Transform Rules → Modify Response Header** (قد تظهر تحت Rules Overview).
2. أنشئ قاعدة باسم `Ads noindex`، شرطها:

```text
(http.host eq "ads.drmohamedhelal.com")
```

3. اختر **Set static**، Header name: `X-Robots-Tag`، Value:

```text
noindex, nofollow, noarchive, nosnippet, noimageindex
```

4. Deploy. الشرط يجب أن يطابق ads فقط حتى لا يؤثر على أرشفة الموقع الرئيسي. ضع القاعدة بعد أي قاعدة أخرى قد تستبدل نفس الهيدر.
5. **DNS → Records → Add record**: النوع `A`، الاسم `ads`، IPv4 هو **Shared IP Address الحقيقي الظاهر في cPanel**. لا تنسخ IP ظاهرًا من نتيجة بحث الدومين لأنها قد تكون IP لـCloudflare. TTL: Auto، وProxy: **Proxied** بعد تجهيز شهادة الاستضافة.
6. من cPanel → **SSL/TLS Status** تأكد أن شهادة الأصل تشمل `ads.drmohamedhelal.com`. شغّل AutoSSL إن كان متاحًا، أو استخدم إصدار الشهادة الذي توفره Namecheap. لو احتاج التحقق DNS only مؤقتًا، اجعله بعد تركيب الملفات و`.htaccess` وحماية التطبيق، ثم أعد Proxied فور صدور الشهادة.
7. Cloudflare → SSL/TLS: استخدم **Full (strict)** مع شهادة صالحة للصب دومين، ولا تخفض إعداد حماية الدومين الأساسي. شهادة Universal SSL تغطي عادةً الصب دومين الأول عند استخدام Proxied.
8. تأكد أن قواعد تحويل www/الدومين الأساسي لا تحول ads إلى الموقع الرئيسي. اجعل HTTPS إجباريًا للـads مع الاحتفاظ بالمسار. لا تضف Cache Everything للـHTML؛ الحجز والإدارة والجلسات يجب ألا تتخزن كصفحات عامة. امسح كاش ads فقط إذا كان قد عُرض عليه محتوى قديم.

مراجع:
- https://developers.cloudflare.com/dns/manage-dns-records/how-to/create-subdomain/
- https://developers.cloudflare.com/rules/transform/response-header-modification/create-dashboard/
- https://developers.cloudflare.com/ssl/origin-configuration/ssl-modes/full-strict/
- https://docs.cpanel.net/cpanel/security/ssl-tls-status/

## 5. فحص الإطلاق قبل الإعلانات

في DevTools → Network افتح الرئيسية، صفحة داخلية، رابطًا غير موجود، صورة وفيديو MP4. يجب أن تحتوي الردود على `X-Robots-Tag: noindex, nofollow, noarchive, nosnippet, noimageindex`، وHTML يحتوي `<meta name="robots" content="noindex,nofollow">`. اختبر كذلك HTTP ثم HTTPS وأي redirects.

- `/sitemap.xml` و`/sitemap-1.xml`: 404 دائمًا حتى لو فعّلت Sitemap من لوحة الإدارة.
- `/robots.txt`: لا توجد Sitemap، ولا `Disallow: /` عام. السماح بالزحف ضروري لكي يقرأ Google أمر noindex؛ منع الزحف وحده قد يترك الرابط في النتائج.
- `/services/service-2` و`/blog/article-2`: 404، والخدمة المستبعدة لا تظهر في الحجز أو الفوتر.
- `/assets/hero-pregnancy-ultrasound.mp4` وملفات `service-fertility` القديمة: 404 في Apache. الحزمة تستبعد هذه الأصول.
- فيديو البداية هو «سكر الحمل: طبيعي ولا قيصري؟»، التشغيل والإغلاق والصوت يعملون على الموبايل والكمبيوتر.
- الحجز والفروع وأرقام واتساب صحيحة، والطلب يستخدم قاعدة ads فقط، وروابط الموقع الداخلية تظل داخل ads.
- راجع كل الصور والفيديوهات/الوسائط المضافة يدويًا؛ تصفية النص لا تستطيع مراجعة كلام الفيديو أو النص المطبوع داخل الصورة.
- لا ترسل Sitemap إلى Search Console، ولا تستخدم Request indexing. يمكن استخدام URL Inspection للتأكد من اكتشاف noindex. لو سبق أن ظهر رابط ads في النتائج، اطلب إزالة مؤقتة من Search Console مع بقاء noindex حتى يعيد Google زحفه.

الحماية ثابتة في كود الفرع على مستوى HTML وكل استجابات Laravel، ومعها Apache للملفات الثابتة وCloudflare عند ضبط القاعدة. لا يمكن تأكيد النتيجة على الإنترنت قبل فحص النسخة المنشورة فعليًا؛ لا تُطلق الإعلانات قبل اجتياز الفحص. مرجع Google: https://developers.google.com/search/docs/crawling-indexing/robots-meta-tag

## حالة النشر الفعلية — 26 أكتوبر 2026

نُشرت نسخة commit `966a099` من فرع GitHub `codex/ads-site` (اسمه المحلي `ads-site`) من حزمة Actions الناجحة `40292660238`. التطبيق في `/home/drmonwhs/apps/helal-ads`، وجذر الصب دومين `/home/drmonwhs/public_html/ads`، والقاعدة والمستخدم `drmonwhs_ads`. نُقل محتوى CMS المنشور ومسوداته إلى القاعدة المستقلة، مع حساب الإدارة الحالي وصلاحياته، ودون نسخ المرضى أو المواعيد أو الجلسات أو الرسائل. تمت إعادة كتابة روابط الدومين الأساسي المخزنة إلى ads في النسخة الجديدة فقط.

تمت إضافة سجل Cloudflare `A ads → 104.207.79.182` وتفعيل Proxied، مع قاعدة `Ads permanent noindex` التي تطابق اسم ads فقط. شهادة Let’s Encrypt مستقلة صدرت وركبت بنجاح، وتنتهي **24 يناير 2027**؛ **التجديد التلقائي غير مُعدّ حاليًا** ويجب تجديدها وتركيبها قبل هذا التاريخ. أدوات Namecheap SSL كانت ترجع500 وAutoSSL غير متاح وقت النشر. لم يتم تخفيض حماية SSL أو تغيير إعداد الدومين الرئيسي.

حد عدد الملفات في الاستضافة منع تكرار vendor بالكامل. بعد التحقق من تطابق `composer.lock`، أُنشئت ملفات vendor في ads بروابط hard links إلى نفس مكتبات الإصدار المثبت، وليس رابطًا إلى مجلد `current`. تبقى هذه الملفات متاحة بعد حذف مسار الإصدار القديم، ولا تربط قاعدة البيانات أو storage أو كود التطبيق. **لا تشغّل Composer الذي يعدّل vendor في مكانه على السيرفر**؛ عند تحديث المكتبات استخدم مجلد إصدار جديدًا وحزمة نظيفة، أو وفّر مساحة ملفات كافية أولًا. باقي كود وبيئة وملفات النسختين منفصلة.

الفحص الحي أكد200 للصفحات العادية وnoindex للـHTML والاستجابات والملفات،206 لتحميل الفيديو الجزئي،404 للـSitemap والمحتوى المستبعد. تحقق تشغيل فيديو سكر الحمل وإغلاقه في المتصفح. الصفحة الرئيسية الأساسية ما زالت ترجع `index, follow, max-image-preview:large` وإصدارها `47fd8a0` لم يتغير.

## التحديث التلقائي من GitHub

فرع التحديث المعتمد الآن هو **`ads-site`**. كل Push عليه يشغّل `Test and deploy ads site`: اختبارات التطبيق والمتصفح، بناء حزمة الإنتاج، ثم نشرها تلقائيًا على `/home/drmonwhs/apps/helal-ads/releases/<commit>` وتحويل `current` إلى الإصدار الجديد. لا تستخدم الفرع القديم `codex/ads-site` للتحديثات الجديدة. لا يلزم Git pull يدوي على الاستضافة.

تبقى `.env` وقاعدة `drmonwhs_ads` وstorage المشتركة بين إصدارات ads محفوظة، ولا يُشغّل seeding. تؤخذ نسخة DB قبل migrations. تُستخدم مكتبات متطابقة بروابط hard links عند ثبات composer.lock مراعاةً لحد الملفات. فشل فحص الرئيسية بعد التحويل يعيد الإصدار السابق، ولا يعكس migrations تلقائيًا. المسار ثابت لنسخة ads ولا يستخدم DEPLOY_PATH للموقع الرئيسي. تابع نجاح مرحلتي build وdeploy في Actions قبل اعتبار التحديث منشورًا.
