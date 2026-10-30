<x-filament-panels::page>
    <style>
        .content-hub { --hub-accent: #126d71; --hub-tint: #edf7f6; --hub-border: #e2e8ed; --hub-muted: #64748b; --hub-surface: #fff; --hub-text: #172b3a; display: grid; gap: 1.5rem; color: var(--hub-text); }
        .dark .content-hub { --hub-accent: #7dd3cf; --hub-tint: #153b3c; --hub-border: #374151; --hub-muted: #a1adbc; --hub-surface: #18212e; --hub-text: #f1f5f9; }
        .content-hub *, .content-hub *::before, .content-hub *::after { box-sizing: border-box; }
        .content-hub .hub-heading { margin: 0; font-size: 1.05rem; font-weight: 700; line-height: 1.7; }
        .content-hub .hub-copy { margin: .25rem 0 0; font-size: .82rem; line-height: 1.8; color: var(--hub-muted); }
        .content-hub .hub-heading-row { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
        .content-hub .hub-workspace { border: 1px solid var(--hub-border); border-radius: 1rem; background: var(--hub-surface); overflow: clip; }
        .content-hub .hub-workspace-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--hub-border); }
        .content-hub .hub-workspace-top .hub-heading-row { margin: 0; }
        .content-hub .hub-page-control { flex: 0 0 230px; }
        .content-hub .hub-page-control label { display: block; margin-bottom: .35rem; font-size: .75rem; font-weight: 600; }
        .content-hub .hub-select { width: 100%; border: 1px solid var(--hub-border); border-radius: .6rem; background-color: var(--hub-surface); color: var(--hub-text); font-size: .85rem; }
        .content-hub .hub-nav-title { padding: 0 .5rem .65rem; font-size: .75rem; color: var(--hub-muted); font-weight: 600; }
        .content-hub .hub-form { min-width: 0; display: grid; gap: 1rem; padding: 1.25rem; }
        .content-hub .hub-empty { padding: 2rem 1rem; text-align: center; color: var(--hub-muted); font-size: .85rem; line-height: 1.9; }
        .content-hub .hub-actions { position: sticky; bottom: .75rem; z-index: 20; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .85rem 1.5rem; padding: 1rem 1.15rem; border: 1px solid var(--hub-border); border-radius: .9rem; background: var(--hub-surface); box-shadow: 0 6px 28px rgb(15 23 42 / 9%); }
        .content-hub .hub-action-buttons { display: flex; flex-wrap: wrap; gap: .5rem; }
        .content-hub .hub-action-copy { flex: 1 1 280px; }
        .content-hub .hub-action-copy strong { display: block; font-size: .8rem; }
        .content-hub .hub-action-copy .hub-copy { margin-top: .1rem; font-size: .73rem; }
        @media (max-width: 767px) {
            .content-hub { gap: 1.15rem; }
            .content-hub .hub-workspace-top { align-items: stretch; flex-direction: column; padding: 1rem; }
            .content-hub .hub-page-control { flex-basis: auto; }
            .content-hub .hub-nav-title { padding: 0 0 .4rem; }
            .content-hub .hub-form { border-inline-start: 0; padding: 1rem; }
            .content-hub .hub-actions { bottom: .4rem; padding: .8rem; gap: .65rem; }
            .content-hub .hub-action-copy { flex-basis: 100%; }
        }
    </style>
    <div class="content-hub">
        <section class="hub-workspace" aria-labelledby="hub-editor-heading">
            <div class="hub-workspace-top">
                <div class="hub-heading-row">
                    
                    <div>
                        <h2 id="hub-editor-heading" class="hub-heading">محتوى الصفحة</h2>
                        <p class="hub-copy">اختر الصفحة والقسم، ثم عدّل النصوص والصور.</p>
                    </div>
                </div>
                <div class="hub-page-control">
                    <label for="content-page-selector">اختر الصفحة</label>
                    <select id="content-page-selector" wire:model.live="selectedPageKey" data-testid="page-selector" class="hub-select">
                        @foreach($sitePages as $sitePage)<option value="{{ $sitePage->page_key }}">{{ $sitePage->title }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="hub-form">
                <div>
                    <label for="content-section-selector" class="hub-nav-title">القسم</label>
                    <select id="content-section-selector" wire:change="selectSection($event.target.value)" class="hub-select" @disabled($sections->isEmpty())>
                        @forelse($sections as $section)
                            <option value="{{ $section->id }}" @selected($selectedSectionId === $section->id)>{{ $section->section_key === 'section-5' ? 'خطوات أول زيارة' : $section->section_label }}{{ $section->draft_data ? ' · مسودة' : '' }}</option>
                        @empty
                            <option>لا توجد أقسام مشتركة</option>
                        @endforelse
                    </select>
                </div>

                    <p class="hub-copy">تعديل القسم المشترك يظهر في كل الصفحات التي تستخدمه. يمكنك ترتيب أقسام كل صفحة من قائمة «صفحات الموقع».</p>
                    @if($selectedSectionId){{ $this->form }}@else<p class="hub-empty">هذه الصفحة بلا أقسام مشتركة. عدّل محتواها من قائمة «صفحات الموقع».</p>@endif
            </div>
        </section>
        <div class="hub-actions">
            <div class="hub-action-copy">
                <strong>راجع تعديلاتك ثم انشرها</strong>
                <p class="hub-copy">حفظ المسودة لا يغيّر الموقع المنشور. المعاينة تعرض المسودات المحفوظة، والنشر يطبّق كل مسودات الصفحات والأقسام.</p>
            </div>
            <div class="hub-action-buttons">
                @if($selectedSectionId)<x-filament::button wire:click="save" wire:loading.attr="disabled">حفظ المسودة</x-filament::button>@endif
                <x-filament::button tag="a" :href="$this->preview_url" target="_blank" rel="noopener" color="gray">معاينة الموقع</x-filament::button>
                <x-filament::button wire:click="publish" wire:loading.attr="disabled" wire:confirm="نشر كل المسودات وإظهارها للزوار؟" color="success">نشر الموقع</x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
