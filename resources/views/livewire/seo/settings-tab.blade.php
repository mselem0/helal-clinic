<div class="space-y-6">

    <x-filament::section icon="heroicon-o-information-circle" collapsible collapsed>
        <x-slot name="heading">
            دليل المستخدم: الإعدادات والأدوات
        </x-slot>
        <x-slot name="description">
            كيفية ضبط إعدادات الموقع العامة (انقر للتوسيع)
        </x-slot>

        <div class="prose prose-sm dark:prose-invert max-w-none">
            <p><strong>ما وظيفته؟</strong> إعدادات عامة تؤثر على كامل الموقع.</p>

            <hr class="my-4 border-gray-200 dark:border-white/10" />

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">🌐 الإعدادات العامة</h4>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li><strong>اسم الموقع:</strong> يظهر في نهاية كل عنوان صفحة (مثال: الخدمات | عيادة د. محمد هلال).</li>
                <li><strong>وصف الموقع:</strong> وصف مختصر عام للموقع.</li>
            </ul>

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">📱 السوشيال ميديا</h4>
            <p class="mb-4">حدد روابط صفحاتك على السوشيال ميديا و<strong>الصورة الافتراضية للمشاركة</strong> (الحجم المثالي 1200×630). هذه الصورة ستظهر عند مشاركة أي رابط للموقع على WhatsApp أو Facebook إذا لم تحدد صورة مخصصة لتلك الصفحة.</p>

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">🗺️ Sitemap (خريطة الموقع)</h4>
            <p class="mb-2">ملف يشرح لـ Google جميع صفحات موقعك ويساعدها على اكتشافها أسرع.</p>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li>تأكد من <strong>تفعيل Sitemap</strong>.</li>
                <li>حدد الأقسام التي تريد تضمينها (الصفحة الرئيسية، الخدمات، إلخ).</li>
            </ul>

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">🏢 النشاط التجاري (Local Business)</h4>
            <p class="mb-4">هذه البيانات تساعد موقعك في الظهور في <strong>Google Maps</strong> وبحث "بالقرب مني". أدخل اسم النشاط (مثال: عيادة د. محمد هلال للجراحة التجميلية)، التخصص (Physician)، أرقام التواصل، والعنوان والإحداثيات (خط الطول والعرض من خرائط جوجل).</p>

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">🤖 أدوات المشرفين و Robots.txt</h4>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li><strong>ملف Robots.txt:</strong> ⛔ <strong>تحذير مهم جداً:</strong> لا تعدّل هذا الملف إلا إذا كنت تعرف ما تفعله. خطأ واحد يمكنه إخفاء موقعك كاملاً من Google.</li>
                <li><strong>ربط Google Analytics (GA4):</strong> أدخل معرف الخاصية من Google Analytics لتبدأ في تتبع زيارات موقعك.</li>
            </ul>
        </div>
    </x-filament::section>

    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex items-center gap-4">
            {{ $this->saveAction }}
            {{ $this->disconnectGoogleAction }}
        </div>
    </form>

    <x-filament-actions::modals />
</div>
