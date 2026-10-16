<x-filament-widgets::widget>
    <x-filament::section heading="مراقبة التتبع والتحليلات">
        <div class="grid grid-cols-1 gap-4">
            @foreach($this->getAnalyticsData() as $key => $data)
                <div class="flex items-center justify-between p-4 bg-white dark:bg-white/5 rounded-3xl border border-gray-100/80 dark:border-white/10 shadow-premium transition duration-500 hover:border-[#00B4D8]/30 group">
                    <div class="flex items-center gap-4">
                        <div @class([
                            'w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-500 group-hover:scale-110 shadow-inner',
                            'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' => $data['status'],
                            'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500' => !$data['status'],
                        ])>
                            @if($key === 'google') <x-heroicon-o-chart-bar class="w-6 h-6" /> @endif
                            @if($key === 'facebook') <x-heroicon-o-presentation-chart-line class="w-6 h-6" /> @endif
                            @if($key === 'gtm') <x-heroicon-o-tag class="w-6 h-6" /> @endif
                        </div>
                        <div>
                            <div class="text-sm font-bold text-navy dark:text-white">{{ $data['label'] }}</div>
                            <div class="flex items-center gap-1.5 mt-1">
                                @if($data['status'])
                                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                    <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 truncate max-w-[150px]">
                                        {{ $data['live_data'] ?? $data['id'] }}
                                    </span>
                                @else
                                    <span class="w-1.5 h-1.5 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                                    <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 italic">بانتظار الإعداد</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    @if(!$data['status'])
                        <button 
                            wire:click="mountAction('connect')"
                            class="text-[10px] font-black uppercase tracking-tighter px-3 py-1.5 bg-gray-50 dark:bg-white/5 text-gray-500 hover:bg-[#002855] hover:text-white transition-all duration-300 rounded-lg"
                        >
                            توصيل
                        </button>
                    @else
                        <button 
                            wire:click="mountAction('connect')"
                            class="p-1.5 bg-emerald-50 dark:bg-emerald-500/10 rounded-full hover:bg-emerald-100 dark:hover:bg-emerald-500/20 transition-colors"
                        >
                            <x-heroicon-m-check-badge class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        <x-filament-actions::modals />
    </x-filament::section>
</x-filament-widgets::widget>
