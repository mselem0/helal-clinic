<div class="space-y-6">

    <x-filament::section icon="heroicon-o-information-circle" collapsible collapsed>
        <x-slot name="heading">
            دليل المستخدم: تحسين الصفحات
        </x-slot>
        <x-slot name="description">
            كيفية تحسين ظهور كل صفحة في موقعك (انقر للتوسيع)
        </x-slot>

        <div class="prose prose-sm dark:prose-invert max-w-none">
            <p><strong>ما وظيفته؟</strong> تحسين ظهور كل صفحة في موقعك على Google — <strong>هذا هو القسم الأهم</strong>.</p>
            <p>اضغط زر <strong>"تحسين"</strong> بجوار الصفحة التي تريد تحسينها، وستظهر لك نافذة بها 4 تابات:</p>

            <hr class="my-4 border-gray-200 dark:border-white/10" />

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">📌 Tab 1: عام (الأهم)</h4>
            <ul class="list-disc list-inside space-y-2 mb-4">
                <li><strong>الكلمات المفتاحية الأساسية:</strong> الكلمات التي تريد أن يجد بها المريض موقعك (مثال: "طبيب جراحة تجميلية"). فكر: "لو المريض بيبحث عن الدكتور، ماذا يكتب في Google؟".</li>
                <li><strong>عنوان الميتا (Meta Title):</strong> هذا هو <strong>العنوان الأزرق</strong> الذي يظهر في نتائج Google. القاعدة: بين 30 و 60 حرفاً. ضع الكلمة المفتاحية الأهم في البداية.</li>
                <li><strong>وصف الميتا (Meta Description):</strong> النص الرمادي الذي يظهر أسفل العنوان. القاعدة: بين 120 و 160 حرفاً. اكتب بأسلوب مقنع يشجع الزائر على النقر.</li>
            </ul>

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">📌 Tab 2: السوشيال ميديا</h4>
            <p class="mb-4">تحكم في مظهر الرابط عند مشاركته على Facebook، WhatsApp، أو Twitter. يمكنك تحديد صورة مخصصة (الحجم المثالي: 1200×630) وعنوان ووصف مختلفين للشبكات الاجتماعية.</p>

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">📌 Tab 3: Schema Markup</h4>
            <p class="mb-2">لغة متقدمة تساعد Google في فهم محتواك وإظهاره بشكل مميز.</p>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li><strong>طبيب (Physician):</strong> استخدمه للصفحة الرئيسية لظهور التخصص وأرقام التواصل.</li>
                <li><strong>أسئلة شائعة (FAQPage):</strong> لظهور الأسئلة مباشرة في نتائج البحث (يضاعف مساحتك!).</li>
                <li><strong>فيديو (VideoObject):</strong> إذا كان الموقع يحتوي على فيديو تعريفي.</li>
            </ul>

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">📌 Tab 4: متقدم</h4>
            <p class="mb-4">يحتوي على تعليمات العناكب (Robots). الخيار الافتراضي "Index, Follow" هو الصحيح للغالبية. ⚠️ <strong>تحذير:</strong> اختيار "NoIndex" يُخفي الصفحة تماماً من Google.</p>
        </div>
    </x-filament::section>

    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold">تحسين الـ SEO لصفحات الموقع</h3>
    </div>

    {{ $this->table }}
</div>
