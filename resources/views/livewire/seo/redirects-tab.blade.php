<div class="space-y-12">

    <x-filament::section icon="heroicon-o-information-circle" collapsible collapsed>
        <x-slot name="heading">
            دليل المستخدم: التحويلات و 404
        </x-slot>
        <x-slot name="description">
            كيفية إدارة الروابط المكسورة وتحويل الزوار (انقر للتوسيع)
        </x-slot>

        <div class="prose prose-sm dark:prose-invert max-w-none">
            <p><strong>ما وظيفته؟</strong> إدارة تحويل الروابط القديمة للجديدة، ومراقبة الصفحات المكسورة (404) التي يصل إليها زوارك.</p>

            <hr class="my-4 border-gray-200 dark:border-white/10" />

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">🔀 مدير التحويلات (Redirects)</h4>
            <p class="mb-2"><strong>متى تحتاجه؟</strong> إذا غيّرت رابط صفحة قديمة وتريد توجيه الزوار للصفحة الجديدة (حتى لا تفقدهم).</p>
            <p class="mb-2"><strong>كيف تضيف تحويلاً جديداً؟</strong> اضغط "إضافة تحويل جديد" وأدخل:</p>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li><strong>المصدر (Source):</strong> الرابط القديم الذي لا يعمل (مثال: <code>/old-page</code>).</li>
                <li><strong>الهدف (Target):</strong> الرابط الجديد الصحيح (مثال: <code>/</code> أو <code>/#services</code>).</li>
                <li><strong>النوع:</strong> اختر "301" للتحويل الدائم (ينقل قوة الـ SEO).</li>
            </ul>

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2">⚠️ مراقب روابط 404</h4>
            <p class="mb-2"><strong>ما وظيفته؟</strong> يسجل تلقائياً كل رابط وصل إليه زائر ووجده مكسوراً — بدون أي إعداد منك.</p>
            <p class="mb-2"><strong>كيف تعالج رابطاً مكسوراً؟</strong></p>
            <ol class="list-decimal list-inside space-y-1 mb-4">
                <li>في جدول 404، اضغط <strong>"إنشاء تحويل"</strong> بجوار الرابط المكسور.</li>
                <li>ستظهر لك نافذة، اكتب فيها <strong>الهدف</strong> (أين تريد توجيه الزائر؟).</li>
                <li>اضغط حفظ &larr; يُحوَّل الزائر فوراً وتُحل المشكلة.</li>
            </ol>
        </div>
    </x-filament::section>

    <div>
        <h3 class="text-lg font-bold mb-4">مدير التحويلات (Redirects)</h3>
        @livewire(\App\Livewire\Seo\RedirectsTable::class)
    </div>

    <hr class="border-gray-200" />

    <div>
        <h3 class="text-lg font-bold mb-4">مراقب روابط 404</h3>
        @livewire(\App\Livewire\Seo\MonitorTable::class)
    </div>
</div>
