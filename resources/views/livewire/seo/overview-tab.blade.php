<div class="space-y-6">
    
    @livewire('seo.analytics-widget')

    <x-filament::section icon="heroicon-o-information-circle" collapsible collapsed>
        <x-slot name="heading">
            دليل المستخدم: نظرة عامة
        </x-slot>
        <x-slot name="description">
            كيف تقرأ وتفهم هذه الصفحة (انقر للتوسيع)
        </x-slot>

        <div class="prose prose-sm dark:prose-invert max-w-none">
            <p><strong>ما وظيفته؟</strong> صفحة Dashboard سريعة تعطيك صورة فورية عن صحة الموقع على محركات البحث.</p>
            
            <hr class="my-4 border-gray-200 dark:border-white/10" />
            
            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2"> البطاقات الأربع في أعلى الصفحة</h4>
            
            <h5 class="font-bold text-gray-800 dark:text-gray-200 mt-4 mb-1">بطاقة 1 — متوسط التقييم الداخلي</h5>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li><strong>ماذا تعني؟</strong> تقييم إرشادي للسجلات الموجودة؛ لا يثبت الفهرسة أو ترتيب الموقع ولا يغني عن المراجعة.</li>
                <li><span class="text-success-600 font-bold">🟢 80–100</span> = ممتاز، الاختبارات الداخلية جيدة</li>
                <li><span class="text-warning-600 font-bold">🟡 50–79</span> = متوسط، يحتاج تحسين</li>
                <li><span class="text-danger-600 font-bold">🔴 0–49</span> = ضعيف، يحتاج عناية فورية</li>
            </ul>

            <h5 class="font-bold text-gray-800 dark:text-gray-200 mt-4 mb-1">بطاقة 2 — سجلات SEO تسمح بالفهرسة</h5>
            <p class="mb-4">عدد سجلات SEO التي تسمح بالفهرسة؛ العدد الفعلي للصفحات المفهرسة يُراجع في Search Console بعد النشر.</p>

            <h5 class="font-bold text-gray-800 dark:text-gray-200 mt-4 mb-1">بطاقة 3 — روابط 404 نشطة</h5>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li><strong>المشكلة:</strong> زوار يصلون لصفحات غير موجودة (خطأ 404).</li>
                <li><strong>لون أحمر</strong> = يوجد مشاكل تحتاج حل فوري.</li>
                <li>اضغط عليها للانتقال لقسم التحويلات ومعالجتها.</li>
            </ul>

            <h5 class="font-bold text-gray-800 dark:text-gray-200 mt-4 mb-1">بطاقة 4 — التحويلات النشطة</h5>
            <p class="mb-4">عدد قواعد التحويل المُفعَّلة حالياً (مثلاً: تحويل رابط قديم لرابط جديد).</p>

            <hr class="my-4 border-gray-200 dark:border-white/10" />

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2"> قائمة المشاكل المكتشفة</h4>
            <p class="mb-2">أسفل البطاقات ستجد تحليلاً تلقائياً للمشاكل:</p>
            <ul class="list-disc list-inside space-y-1 mb-4">
                <li><span class="text-danger-600 font-bold">🔴 خطأ أحمر</span> = مشكلة حرجة تؤثر مباشرة على ظهور الموقع</li>
                <li><span class="text-warning-600 font-bold">🟡 تحذير أصفر</span> = تحذير يُنصح بمعالجته</li>
            </ul>
            
            <h5 class="font-bold text-gray-800 dark:text-gray-200 mt-2 mb-1">أمثلة شائعة:</h5>
            <ul class="list-disc list-inside space-y-1 mb-4 text-gray-600 dark:text-gray-400">
                <li>"يوجد 2 صفحات بدون وصف ميتا" &larr; اذهب لتاب <strong>تحسين الصفحات</strong> وأضف الأوصاف.</li>
                <li>"خريطة الموقع (Sitemap) غير مفعّلة" &larr; اذهب لـ <strong>الإعدادات</strong> وفعّلها.</li>
                <li>"تم اكتشاف 3 روابط مكسورة" &larr; اذهب لـ <strong>التحويلات و 404</strong> وحلّها.</li>
            </ul>

            <hr class="my-4 border-gray-200 dark:border-white/10" />

            <h4 class="text-base font-bold text-gray-900 dark:text-white mb-2"> الروتين المثالي</h4>
            <p>راجع هذه الصفحة <strong>أسبوعياً</strong>. إذا رأيت روابط 404 جديدة، توجه لمعالجتها. إذا انخفضت درجة السيو، قم بفتح قائمة "تحسين الصفحات" وافحص الصفحات ذات التقييم المنخفض.</p>
        </div>
    </x-filament::section>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <x-filament::card class="p-4 flex flex-col items-center justify-center text-center">
            <div class="text-gray-500 text-sm mb-1">متوسط التقييم الداخلي</div>
            <div class="text-3xl font-bold @if($stats['avg_score'] > 80) text-success-600 @elseif($stats['avg_score'] > 50) text-warning-600 @else text-danger-600 @endif">
                {{ $stats['avg_score'] }}/100
            </div>
        </x-filament::card>

        <x-filament::card class="p-4 flex flex-col items-center justify-center text-center">
            <div class="text-gray-500 text-sm mb-1">سجلات SEO تسمح بالفهرسة</div>
            <div class="text-3xl font-bold text-primary-600">
                {{ $stats['indexed_pages'] }}
            </div>
        </x-filament::card>

        <x-filament::card class="p-4 flex flex-col items-center justify-center text-center">
            <div class="text-gray-500 text-sm mb-1">روابط 404 نشطة</div>
            <div class="text-3xl font-bold text-danger-600">
                {{ $stats['active_404'] }}
            </div>
        </x-filament::card>

        <x-filament::card class="p-4 flex flex-col items-center justify-center text-center">
            <div class="text-gray-500 text-sm mb-1">إجمالي التحويلات</div>
            <div class="text-3xl font-bold text-success-600">
                {{ $stats['redirects'] }}
            </div>
        </x-filament::card>
    </div>

    @if(count($healthIssues) > 0)
        <x-filament::card class="p-6 border-l-4 border-warning-500">
            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                <x-heroicon-o-shield-exclamation class="w-6 h-6 text-warning-500" />
                تحليل حالة الـ SEO والمشاكل المكتشفة
            </h3>
            <ul class="space-y-3">
                @foreach($healthIssues as $issue)
                    <li class="flex items-start gap-3">
                        @if($issue['type'] === 'error')
                            <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500 mt-0.5" />
                        @else
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-warning-500 mt-0.5" />
                        @endif
                        <span class="text-gray-700 dark:text-gray-300">{{ $issue['message'] }}</span>
                    </li>
                @endforeach
            </ul>
        </x-filament::card>
    @else
        <x-filament::card class="p-6 border-l-4 border-success-500 bg-success-50 dark:bg-success-900/10">
            <div class="flex items-center gap-3 text-success-700 dark:text-success-400">
                <x-heroicon-o-check-badge class="w-8 h-8" />
                <div>
                    <h3 class="text-lg font-bold">كل شيء يبدو رائعاً!</h3>
                    <p class="text-sm">لم نكتشف أي مشاكل حرجة في السيو حالياً.</p>
                </div>
            </div>
        </x-filament::card>
    @endif

    <div>
        <h3 class="text-lg font-bold mb-4">أداء صفحات الموقع</h3>
        {{ $this->table }}
    </div>
</div>
