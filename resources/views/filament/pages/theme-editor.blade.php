<x-filament-panels::page>
    <style>
        .content-hub { --hub-accent: #126d71; --hub-tint: #edf7f6; --hub-border: #e2e8ed; --hub-muted: #64748b; --hub-surface: #fff; --hub-text: #172b3a; display: grid; gap: 1.5rem; color: var(--hub-text); }
        .dark .content-hub { --hub-accent: #7dd3cf; --hub-tint: #153b3c; --hub-border: #374151; --hub-muted: #a1adbc; --hub-surface: #18212e; --hub-text: #f1f5f9; }
        .content-hub *, .content-hub *::before, .content-hub *::after { box-sizing: border-box; }
        .content-hub .hub-heading { margin: 0; font-size: 1.05rem; font-weight: 700; line-height: 1.7; }
        .content-hub .hub-copy { margin: .25rem 0 0; font-size: .82rem; line-height: 1.8; color: var(--hub-muted); }
        .content-hub .hub-heading-row { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
        .content-hub .hub-step { flex: 0 0 auto; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: .65rem; background: var(--hub-tint); color: var(--hub-accent); font-size: .85rem; font-weight: 700; }
        .content-hub .hub-links { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: .75rem; }
        .content-hub .hub-card { display: flex; align-items: flex-start; gap: .75rem; min-width: 0; padding: 1rem; border: 1px solid var(--hub-border); border-radius: .9rem; background: var(--hub-surface); text-decoration: none; transition: border-color .15s, box-shadow .15s; }
        .content-hub .hub-card:hover { border-color: var(--hub-accent); box-shadow: 0 4px 14px rgb(15 75 81 / 6%); }
        .content-hub .hub-card:focus-visible, .content-hub .hub-section-choice:focus-visible { outline: 3px solid var(--hub-accent); outline-offset: 3px; }
        .content-hub .hub-icon { display: grid; place-items: center; flex: 0 0 auto; width: 2.3rem; height: 2.3rem; border-radius: .65rem; color: var(--hub-accent); background: var(--hub-tint); }
        .content-hub .hub-icon svg { width: 1.25rem; height: 1.25rem; }
        .content-hub .hub-card-body { min-width: 0; flex: 1; }
        .content-hub .hub-card-title { display: flex; align-items: center; justify-content: space-between; gap: .4rem; font-size: .86rem; font-weight: 700; line-height: 1.8; }
        .content-hub .hub-card-arrow { flex: 0 0 auto; width: .9rem; height: .9rem; color: var(--hub-muted); }
        .content-hub .hub-card .hub-copy { font-size: .75rem; }
        .content-hub .hub-note { display: flex; align-items: flex-start; gap: .5rem; margin-top: .8rem; font-size: .75rem; line-height: 1.8; color: var(--hub-muted); }
        .content-hub .hub-note svg { flex: 0 0 auto; width: 1rem; height: 1rem; margin-top: .15rem; }
        .content-hub .hub-workspace { border: 1px solid var(--hub-border); border-radius: 1rem; background: var(--hub-surface); overflow: clip; }
        .content-hub .hub-workspace-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid var(--hub-border); }
        .content-hub .hub-workspace-top .hub-heading-row { margin: 0; }
        .content-hub .hub-page-control { flex: 0 0 230px; }
        .content-hub .hub-page-control label { display: block; margin-bottom: .35rem; font-size: .75rem; font-weight: 600; }
        .content-hub .hub-select { width: 100%; border: 1px solid var(--hub-border); border-radius: .6rem; background-color: var(--hub-surface); color: var(--hub-text); font-size: .85rem; }
        .content-hub .hub-editor-grid { display: grid; grid-template-columns: 220px minmax(0,1fr); align-items: start; }
        .content-hub .hub-section-nav { position: sticky; top: 5rem; padding: 1rem .75rem; max-height: calc(100vh - 8rem); overflow-y: auto; }
        .content-hub .hub-nav-title { padding: 0 .5rem .65rem; font-size: .75rem; color: var(--hub-muted); font-weight: 600; }
        .content-hub .hub-section-list { display: grid; gap: .35rem; }
        .content-hub .hub-section-choice { display: flex; align-items: center; justify-content: space-between; gap: .5rem; width: 100%; padding: .75rem; text-align: start; font-size: .82rem; line-height: 1.7; border: 1px solid transparent; border-radius: .65rem; color: var(--hub-muted); background: transparent; }
        .content-hub .hub-section-choice:hover { background: var(--hub-tint); color: var(--hub-accent); }
        .content-hub .hub-section-choice[aria-pressed="true"] { background: var(--hub-tint); border-color: var(--hub-accent); color: var(--hub-accent); font-weight: 700; }
        .content-hub .hub-draft { flex: 0 0 auto; padding: .1rem .4rem; border-radius: .4rem; background: var(--hub-surface); font-size: .65rem; font-weight: 500; }
        .content-hub .hub-form { min-width: 0; display: grid; gap: 1rem; padding: 1.25rem; border-inline-start: 1px solid var(--hub-border); }
        .content-hub .hub-related { padding: .85rem 1rem; border-radius: .7rem; background: var(--hub-tint); }
        .content-hub .hub-related a { color: var(--hub-accent); font-size: .85rem; font-weight: 700; }
        .content-hub .hub-empty { padding: 2rem 1rem; text-align: center; color: var(--hub-muted); font-size: .85rem; line-height: 1.9; }
        .content-hub .hub-actions { position: sticky; bottom: .75rem; z-index: 20; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .85rem 1.5rem; padding: 1rem 1.15rem; border: 1px solid var(--hub-border); border-radius: .9rem; background: var(--hub-surface); box-shadow: 0 6px 28px rgb(15 23 42 / 9%); }
        .content-hub .hub-action-buttons { display: flex; flex-wrap: wrap; gap: .5rem; }
        .content-hub .hub-action-copy { flex: 1 1 280px; }
        .content-hub .hub-action-copy strong { display: block; font-size: .8rem; }
        .content-hub .hub-action-copy .hub-copy { margin-top: .1rem; font-size: .73rem; }
        @media (max-width: 1199px) { .content-hub .hub-links { grid-template-columns: repeat(2,minmax(0,1fr)); } }
        @media (max-width: 767px) {
            .content-hub { gap: 1.15rem; }
            .content-hub .hub-workspace-top { align-items: stretch; flex-direction: column; padding: 1rem; }
            .content-hub .hub-page-control { flex-basis: auto; }
            .content-hub .hub-editor-grid { grid-template-columns: minmax(0,1fr); }
            .content-hub .hub-section-nav { position: static; max-height: none; overflow: hidden; padding: .8rem 1rem; border-bottom: 1px solid var(--hub-border); }
            .content-hub .hub-section-list { display: flex; overflow-x: auto; padding: .2rem .2rem .5rem; }
            .content-hub .hub-section-choice { width: auto; flex: 0 0 auto; padding: .55rem .75rem; border-color: var(--hub-border); }
            .content-hub .hub-nav-title { padding: 0 0 .4rem; }
            .content-hub .hub-form { border-inline-start: 0; padding: 1rem; }
            .content-hub .hub-actions { bottom: .4rem; padding: .8rem; gap: .65rem; }
            .content-hub .hub-action-copy { flex-basis: 100%; }
        }
        @media (max-width: 479px) { .content-hub .hub-links { grid-template-columns: minmax(0,1fr); } .content-hub .hub-card { padding: .85rem; } }
        @media (prefers-reduced-motion: reduce) { .content-hub .hub-card { transition: none; } }
    </style>
    <div class="content-hub">
        <section aria-labelledby="hub-content-heading">
            <div class="hub-heading-row">
                <span class="hub-step" aria-hidden="true">1</span>
                <div>
                    <h2 id="hub-content-heading" class="hub-heading">إدارة محتوى الموقع</h2>
                    <p class="hub-copy">كل محتوى العيادة في مكان واحد. اختر ما تريد تعديله للبدء.</p>
                </div>
            </div>
            @php
                $hubIcons = ['services' => 'heroicon-o-heart', 'clinics' => 'heroicon-o-map-pin', 'videos' => 'heroicon-o-play-circle', 'reviews' => 'heroicon-o-chat-bubble-left-right', 'articles' => 'heroicon-o-document-text'];
                $hubPageIcons = ['الصفحات والقوائم' => 'heroicon-o-squares-2x2', 'قوائم التصفح' => 'heroicon-o-bars-3', 'التواصل وواتساب' => 'heroicon-o-phone'];
            @endphp
            <div class="hub-links">
                @foreach([$pageLinks, $contentLinks] as $links)
                    @foreach($links as $key => $link)
                        <a href="{{ $link['url'] }}" class="hub-card">
                            <span class="hub-icon" aria-hidden="true"><x-dynamic-component :component="$hubIcons[$key] ?? $hubPageIcons[$link['label']] ?? 'heroicon-o-document-text'" /></span>
                            <div class="hub-card-body">
                                <div class="hub-card-title"><span>{{ $link['label'] }}</span><x-heroicon-o-arrow-left class="hub-card-arrow" aria-hidden="true" /></div>
                                <p class="hub-copy">{{ $link['description'] }}</p>
                            </div>
                        </a>
                    @endforeach
                @endforeach
            </div>
            <p class="hub-note"><x-heroicon-o-information-circle aria-hidden="true" /><span>تُحفظ الصفحات كمسودات. تُعرض الخدمات والفروع والفيديوهات والآراء والمقالات حسب حالة نشرها أو تفعيلها عند الحفظ.</span></p>
        </section>

        <section class="hub-workspace" aria-labelledby="hub-editor-heading">
            <div class="hub-workspace-top">
                <div class="hub-heading-row">
                    <span class="hub-step" aria-hidden="true">2</span>
                    <div>
                        <h2 id="hub-editor-heading" class="hub-heading">نصوص وصور أقسام الصفحة</h2>
                        <p class="hub-copy">اختر الصفحة، ثم القسم الذي تريد تعديل محتواه.</p>
                    </div>
                </div>
                <div class="hub-page-control">
                    <label for="content-page-selector">اختر الصفحة</label>
                    <select id="content-page-selector" wire:model.live="selectedPageKey" data-testid="page-selector" class="hub-select">
                        @foreach($sitePages as $sitePage)<option value="{{ $sitePage->page_key }}">{{ $sitePage->title }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="hub-editor-grid">
                <aside class="hub-section-nav" aria-label="أقسام الصفحة">
                    <div class="hub-nav-title">أقسام الصفحة</div>
                    <div class="hub-section-list">
                        @forelse($sections as $section)
                            <button type="button" data-section-choice="{{ $section->section_key }}" wire:click="selectSection({{ $section->id }})" aria-pressed="{{ $selectedSectionId === $section->id ? 'true' : 'false' }}" class="hub-section-choice">
                                <span>{{ $section->section_label }}</span> @if($section->draft_data)<span class="hub-draft">مسودة</span>@endif
                            </button>
                        @empty
                            <p class="hub-copy">لا توجد أقسام مشتركة.</p>
                        @endforelse
                    </div>
                </aside>
                <div class="hub-form">
                    <p class="hub-copy">تعديل القسم المشترك يظهر في كل الصفحات التي تستخدمه. يمكنك ترتيب أقسام كل صفحة من «الصفحات والقوائم».</p>
                    @if(isset($contentLinks[$selectedKey]))
                        <div class="hub-related">
                            <a href="{{ $contentLinks[$selectedKey]['url'] }}">إدارة {{ $contentLinks[$selectedKey]['label'] }} ←</a>
                            <p class="hub-copy">عدّل عناصر هذا القسم من الرابط، وعدّل عنوان القسم والنصوص المحيطة به من هنا.</p>
                        </div>
                    @endif
                    @if($selectedSectionId){{ $this->form }}@else<p class="hub-empty">هذه الصفحة بلا أقسام مشتركة. عدّل محتواها من «الصفحات والقوائم».</p>@endif
                </div>
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
    </div>
</x-filament-panels::page>
