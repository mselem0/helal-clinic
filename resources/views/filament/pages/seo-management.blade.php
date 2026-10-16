<x-filament-panels::page>
    <div class="flex flex-col gap-4">

        {{-- ═══════════════════════════════════════════════════════
             STATS SUMMARY BAR
        ════════════════════════════════════════════════════════════ --}}
        @php
            $stats = $this->getStats();
        @endphp

        <div style="display:grid; grid-template-columns: repeat(4,1fr); gap:12px;">

            {{-- Avg SEO Score --}}
            <div style="background:var(--default-bg,#1e293b); border:1px solid #334155; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:14px;">
                <div style="width:44px;height:44px;border-radius:50%;background:{{ $stats['score_color'] }}22;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="{{ $stats['score_color'] }}" stroke-width="2" width="22" height="22"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:700;color:{{ $stats['score_color'] }};line-height:1;">{{ $stats['avg_score'] }}<span style="font-size:12px;color:#94a3b8;">/100</span></div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">متوسط درجة SEO</div>
                </div>
            </div>

            {{-- Pages --}}
            <div style="background:var(--default-bg,#1e293b); border:1px solid #334155; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:14px; cursor:pointer;" wire:click="setActiveTab('pages')">
                <div style="width:44px;height:44px;border-radius:50%;background:#3b82f622;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" width="22" height="22"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10,9 9,9 8,9"/></svg>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:700;color:#3b82f6;line-height:1;">{{ $stats['page_count'] }}</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">صفحات مُحسَّنة</div>
                </div>
            </div>

            {{-- 404 Errors --}}
            <div style="background:var(--default-bg,#1e293b); border:1px solid {{ $stats['active_404'] > 0 ? '#ef444444' : '#334155' }}; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:14px; cursor:pointer;" wire:click="setActiveTab('redirects')">
                <div style="width:44px;height:44px;border-radius:50%;background:{{ $stats['active_404'] > 0 ? '#ef444422' : '#22c55e22' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="{{ $stats['active_404'] > 0 ? '#ef4444' : '#22c55e' }}" stroke-width="2" width="22" height="22"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:700;color:{{ $stats['active_404'] > 0 ? '#ef4444' : '#22c55e' }};line-height:1;">{{ $stats['active_404'] }}</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">روابط 404 نشطة</div>
                </div>
            </div>

            {{-- Redirects --}}
            <div style="background:var(--default-bg,#1e293b); border:1px solid #334155; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:14px; cursor:pointer;" wire:click="setActiveTab('redirects')">
                <div style="width:44px;height:44px;border-radius:50%;background:#8b5cf622;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" width="22" height="22"><polyline points="17,1 21,5 17,9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7,23 3,19 7,15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </div>
                <div>
                    <div style="font-size:22px;font-weight:700;color:#8b5cf6;line-height:1;">{{ $stats['redirects'] }}</div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">تحويلات نشطة</div>
                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════════════════════════════
             TABS
        ════════════════════════════════════════════════════════════ --}}
        <x-filament::tabs label="SEO Management Tabs">
            <x-filament::tabs.item
                :active="$activeTab === 'overview'"
                wire:click="setActiveTab('overview')"
                icon="heroicon-o-chart-bar"
            >
                نظرة عامة
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'pages'"
                wire:click="setActiveTab('pages')"
                icon="heroicon-o-document-text"
            >
                تحسين الصفحات
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'redirects'"
                wire:click="setActiveTab('redirects')"
                icon="heroicon-o-arrows-right-left"
            >
                <span class="flex items-center gap-2">
                    التحويلات و 404
                    @if($stats['active_404'] > 0)
                        <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:999px;line-height:1.5;">{{ $stats['active_404'] }}</span>
                    @endif
                </span>
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'settings'"
                wire:click="setActiveTab('settings')"
                icon="heroicon-o-cog-6-tooth"
            >
                الإعدادات والأدوات
            </x-filament::tabs.item>
        </x-filament::tabs>

        {{-- ═══════════════════════════════════════════════════════
             TAB CONTENT
        ════════════════════════════════════════════════════════════ --}}
        <div>
            @if ($activeTab === 'overview')
                @livewire(\App\Livewire\Seo\OverviewTab::class)
            @elseif ($activeTab === 'pages')
                @livewire(\App\Livewire\Seo\PagesTab::class)
            @elseif ($activeTab === 'redirects')
                @livewire(\App\Livewire\Seo\RedirectsTab::class)
            @elseif ($activeTab === 'settings')
                @livewire(\App\Livewire\Seo\SettingsTab::class)
            @endif
        </div>

    </div>
</x-filament-panels::page>
