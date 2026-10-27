<x-filament-panels::page>
    <x-filament::section heading="إدارة محتوى الموقع">
        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">اختر المحتوى الذي تريد تعديله، أو استخدم محرر الأقسام أدناه لتعديل النصوص والصور.</p>
        <div class="grid gap-6 md:grid-cols-2">
            @foreach(['الصفحات وبيانات العيادة' => $pageLinks, 'المحتوى المعروض للزوار' => $contentLinks] as $heading => $links)
                @if($links)
                    <div class="space-y-4">
                        <h2 class="font-semibold">{{ $heading }}</h2>
                        @foreach($links as $link)
                            <div>
                                <a href="{{ $link['url'] }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">{{ $link['label'] }} ←</a>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $link['description'] }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">تُحفظ الصفحات كمسودات. تُعرض الخدمات والفروع والفيديوهات والآراء والمقالات حسب حالة نشرها أو تفعيلها عند الحفظ.</p>
    </x-filament::section>
    <x-filament::section heading="نصوص وصور أقسام الصفحة">
        <label for="content-page-selector" class="mb-2 block text-sm font-medium">اختر الصفحة</label>
        <select id="content-page-selector" wire:model.live="selectedPageKey" data-testid="page-selector" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 sm:w-auto">
            @foreach($sitePages as $sitePage)<option value="{{ $sitePage->page_key }}">{{ $sitePage->title }}</option>@endforeach
        </select>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">تعديل القسم المشترك يظهر في كل الصفحات التي تستخدمه. يمكنك ترتيب أقسام كل صفحة من «الصفحات والقوائم».</p>
    </x-filament::section>
    <div class="grid gap-6 lg:grid-cols-4">
        <aside class="space-y-2" aria-label="أقسام الصفحة">
            @foreach($sections as $section)
                <button type="button" data-section-choice="{{ $section->section_key }}" wire:click="selectSection({{ $section->id }})" aria-pressed="{{ $selectedSectionId === $section->id ? 'true' : 'false' }}" class="w-full rounded-xl p-3 text-start {{ $selectedSectionId === $section->id ? 'bg-primary-100 text-primary-900 dark:bg-primary-900 dark:text-primary-100' : 'bg-white dark:bg-gray-900' }}">
                    {{ $section->section_label }} @if($section->draft_data)<span class="text-xs"> · مسودة</span>@endif
                </button>
            @endforeach
        </aside>
        <div class="space-y-4 lg:col-span-3">
            @if(isset($contentLinks[$selectedKey]))
                <x-filament::section>
                    <a href="{{ $contentLinks[$selectedKey]['url'] }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">إدارة {{ $contentLinks[$selectedKey]['label'] }} ←</a>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">عدّل عناصر هذا القسم من الرابط، وعدّل عنوان القسم والنصوص المحيطة به من هنا.</p>
                </x-filament::section>
            @endif
            @if($selectedSectionId){{ $this->form }}@else<p>هذه الصفحة بلا أقسام مشتركة. عدّل محتواها من «الصفحات والقوائم».</p>@endif
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($selectedSectionId)<x-filament::button wire:click="save" wire:loading.attr="disabled">حفظ المسودة</x-filament::button>@endif
        <x-filament::button tag="a" :href="$this->preview_url" target="_blank" rel="noopener" color="gray">معاينة الموقع</x-filament::button>
        <x-filament::button wire:click="publish" wire:loading.attr="disabled" wire:confirm="نشر كل المسودات وإظهارها للزوار؟" color="success">نشر الموقع</x-filament::button>
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400">حفظ المسودة لا يغيّر الموقع المنشور. المعاينة تعرض المسودات المحفوظة، والنشر يطبّق كل مسودات الصفحات والأقسام.</p>
    <x-filament::section heading="خيارات إضافية: المظهر والإصدارات" collapsible collapsed>
        <div class="space-y-6">
            <div>
                <h2 class="mb-3 font-semibold">تصميم الموقع</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach($themes as $key => $label)
                        <div>
                            <h3 data-theme-card="{{ $key }}" class="font-semibold">{{ $label }}</h3>
                            <p data-theme-status="{{ $key }}" class="my-2 text-sm text-gray-500 dark:text-gray-400">{{ $publishedTheme === $key ? 'التصميم المنشور حاليًا' : 'متاح للمعاينة' }}</p>
                            <x-filament::button color="{{ $editingTheme === $key ? 'primary' : 'gray' }}" data-theme-choice="{{ $key }}" wire:click="editThemeOnly('{{ $key }}')">{{ $editingTheme === $key ? 'التصميم المحدد' : 'اختيار للمعاينة' }}</x-filament::button>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">اختيار التصميم يعرضه في المعاينة؛ يصبح ظاهرًا للزوار عند نشر الموقع.</p>
            </div>
            <div>
                <h2 class="mb-3 font-semibold">الإصدارات السابقة</h2>
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">الاستعادة تعيد المحتوى والمظهر مع حفظ نسخة من الحالة الحالية.</p>
                <label for="content-revision-select" class="sr-only">الإصدار المراد استعادته</label>
                <select id="content-revision-select" data-testid="revision-select" wire:model="revisionId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">اختر إصدارًا</option>
                    @foreach($revisions as $revision)<option value="{{ $revision->id }}">#{{ $revision->id }} · {{ $revision->created_at }} · {{ $revision->summary }}</option>@endforeach
                </select>
                <x-filament::button class="mt-3" wire:click="restore" wire:confirm="استعادة هذا الإصدار وإلغاء المسودات الحالية؟" color="gray">استعادة</x-filament::button>
                @error('revisionId')<p>{{ $message }}</p>@enderror
            </div>
            <x-filament::button wire:click="discardDrafts" wire:confirm="إلغاء كل التعديلات غير المنشورة؟" color="danger">إلغاء المسودات</x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
