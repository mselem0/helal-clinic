<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2">
        @foreach($themes as $key => $label)
            <x-filament::section>
                <h2 data-theme-card="{{ $key }}" class="text-lg font-bold">{{ $label }}</h2>
                <p data-theme-status="{{ $key }}" class="my-2 text-sm text-gray-500">{{ $publishedTheme === $key ? 'الثيم المنشور حاليًا' : 'متاح للتحرير والمعاينة' }}</p>
                <x-filament::button color="{{ $editingTheme === $key ? 'primary' : 'gray' }}" data-theme-choice="{{ $key }}" wire:click="editThemeOnly('{{ $key }}')">تحرير ومعاينة</x-filament::button>
            </x-filament::section>
        @endforeach
    </div>
    <div class="flex flex-wrap gap-2">
        <x-filament::button wire:click="save" wire:loading.attr="disabled">حفظ المسودة</x-filament::button>
        <x-filament::button tag="a" :href="$this->preview_url" target="_blank" color="gray">معاينة الثيم المحدد</x-filament::button>
        <x-filament::button wire:click="publish" wire:confirm="نشر كل المسودات وتفعيل الثيم المحدد؟" color="success">نشر الموقع</x-filament::button>
        <x-filament::button wire:click="discardDrafts" wire:confirm="إلغاء كل التعديلات غير المنشورة؟" color="danger">إلغاء المسودات</x-filament::button>
    </div>
    <div class="grid gap-6 lg:grid-cols-4">
        <aside class="space-y-2">
            @foreach($sections as $section)
                <button type="button" data-section-choice="{{ $section->section_key }}" wire:click="selectSection({{ $section->id }})" class="w-full rounded-xl p-3 text-start {{ $selectedSectionId === $section->id ? 'bg-primary-100 text-primary-900' : 'bg-white' }}">{{ $section->section_label }} @if($section->draft_data)<span class="text-xs"> · مسودة</span>@endif</button>
            @endforeach
        </aside>
        <div class="lg:col-span-3 space-y-4">@if(isset($contentRoutes[$selectedKey]) && \Illuminate\Support\Facades\Route::has($contentRoutes[$selectedKey]))<x-filament::section><a href="{{ route($contentRoutes[$selectedKey]) }}" class="text-primary-700 font-semibold">إضافة وتعديل وحذف عناصر هذا القسم ↗</a><p class="text-sm text-gray-500">إدارة العناصر من قائمتها؛ التغييرات هناك تُنشر مباشرة حسب حالة تفعيل العنصر.</p></x-filament::section>@endif{{ $this->form }}</div>
    </div>
    <x-filament::section heading="الإصدارات السابقة">
        <p class="mb-3 text-sm text-gray-500">الاستعادة تعيد المحتوى والمظهر مع حفظ نسخة من الحالة الحالية.</p>
        <select data-testid="revision-select" wire:model="revisionId" class="rounded-lg border-gray-300"><option value="">اختاري إصدارًا</option>@foreach($revisions as $revision)<option value="{{ $revision->id }}">#{{ $revision->id }} · {{ $revision->created_at }} · {{ $revision->summary }}</option>@endforeach</select>
        <x-filament::button wire:click="restore" wire:confirm="استعادة هذا الإصدار وإلغاء المسودات الحالية؟" color="gray">استعادة</x-filament::button>
        @error('revisionId')<p>{{ $message }}</p>@enderror
    </x-filament::section>
</x-filament-panels::page>
