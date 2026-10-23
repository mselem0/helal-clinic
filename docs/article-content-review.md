# المقالات الستة — تحقق المصادر وتجهيز النشر

تاريخ التحقق من المصادر: 2026-10-23.

أُعد المحتوى بصياغة أصلية وتحققت معلوماته التثقيفية من المصادر الرسمية المبينة أدناه. هذا تحقق تحريري من المصادر، وليس إقرارًا بمراجعة طبيب بعينه. لم تُملأ حقول اسم المؤلف أو المراجع الطبي أو تاريخ المراجعة الطبية.

## 1. أول موعد لمتابعة الحمل: كيف تستعدين؟

الرابط المحلي: `/blog/article-1`.

- [NHS: مواعيد ورعاية الحمل](https://www.nhs.uk/pregnancy/your-pregnancy-care/your-antenatal-care-and-appointments/)

## 2. تقييم تأخر الإنجاب: ما الذي يحدث في البداية؟

الرابط المحلي: `/blog/article-2`.

- [ACOG: تقييم العقم](https://www.acog.org/womens-health/faqs/evaluating-infertility)
- [NHS: تشخيص تأخر الإنجاب والفحوص](https://www.nhs.uk/conditions/infertility/diagnosis/)
- [WHO: تعريفات متعددة للعقم](https://www.who.int/teams/sexual-and-reproductive-health-and-research-%28srh%29/areas-of-work/fertility-care/multiple-definitions-of-infertility)

## 3. قبل منظار الرحم أو البطن: أسئلة تساعدكِ على اتخاذ قرار واعٍ

الرابط المحلي: `/blog/article-3`.

- [NHS: منظار الرحم](https://www.nhs.uk/tests-and-treatments/hysteroscopy/)
- [NHS: منظار البطن](https://www.nhs.uk/tests-and-treatments/laparoscopy/)

## 4. سكر الحمل والولادة: هل يعني التشخيص ولادة قيصرية؟

الرابط المحلي: `/blog/gestational-diabetes-birth-options`.

- [Gestational Diabetes — ACOG](https://www.acog.org/womens-health/faqs/gestational-diabetes)
- [Gestational diabetes — Treatment and birth — NHS](https://www.nhs.uk/conditions/gestational-diabetes/treatment/)

## 5. العناية بجرح القيصرية وغرز العجان بعد الولادة

الرابط المحلي: `/blog/c-section-perineal-wound-care`.

- [Caesarean section recovery — NHS](https://www.nhs.uk/tests-and-treatments/caesarean-section/recovery/)
- [NHS: غرز وتمزقات العجان](https://www.nhs.uk/pregnancy/labour-and-birth/episiotomy-and-perineal-tears/)

## 6. بعد الولادة: علامات خطر لا تنتظري معها

الرابط المحلي: `/blog/postpartum-emergency-warning-signs`.

- [NHS: جسمكِ بعد الولادة وعلامات المشكلات الخطيرة](https://www.nhs.uk/pregnancy/labour-and-birth/your-body/)
- [NHS: تسمم الحمل وأعراضه بعد الولادة](https://www.nhs.uk/conditions/pre-eclampsia/)
- [NHS: ذهان ما بعد الولادة والحاجة للمساعدة العاجلة](https://www.nhs.uk/mental-health/conditions/post-partum-psychosis/)

## القرارات والتحقق

- توقيت الفحوص وخطط العلاج والولادة فردي، ولا تُقدَّم جرعات أو ضمانات نتائج.
- التمييز بين منظار الرحم ومنظار البطن، وبين جرح القيصرية وغرز العجان.
- توضيح طلب الطوارئ فورًا للأعراض الشديدة بعد الولادة، وعدم انتظار رد العيادة عبر الإنترنت.
- بعض صفحات ACOG تعذر فتحها مباشرة (403)؛ استُخدمت مقتطفات البحث الرسمية فقط مع مقابلة المعلومات بمصادر NHS المتاحة. لا يُدَّعى الاطلاع الكامل على صفحات ACOG المحجوبة.
- عناوين ووصف SEO مخصصان لكل مقال، ومصادر ظاهرة، وعنوان H1 واحد. نظام الموقع الحالي ينتج canonical وArticle/MedicalWebPage ويدرج المقالات المنشورة في sitemap.
- الصور من الأصول الموجودة، بما فيها الصور التوضيحية المولّدة للفيديوهات؛ لا تمثل صور حالات سريرية.
- نُشرت المقالات في قاعدة البيانات المحلية فقط. لا نشر خارجي ولا طلب فهرسة لدى محركات البحث ضمن هذه الخطوة.

## قابلية الإدارة وإعادة الاستخدام

المحتوى والعنوان ووصف البحث والصورة والمصادر وحالة النشر قابلة للتعديل من إدارة المقالات الحالية.

الملف الأساسي هو `database/content/reviewed-articles.json`، والاستيراد المقصود يتم عبر `php artisan db:seed --class=ReviewedArticlesSeeder` بعد التأكد من قاعدة البيانات الهدف وأخذ نسخة احتياطية. لا ينفذ مع التهيئة العامة تلقائيًا؛ النشر على أي سيرفر يتطلب إذن المستخدم المحدد.

يحافظ الاستيراد على الروابط الثلاثة القديمة، ولا يكتب فوق محتوى أو بيانات تحريرية معدلة أو مقالات سبق استيرادها. عند تعديل المحتوى بعد الاستيراد تُستخدم لوحة التحكم؛ إعادة الاستيراد لا تفرض النسخة الأساسية على تعديلات الإدارة.
