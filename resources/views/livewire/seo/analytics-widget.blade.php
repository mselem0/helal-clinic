<div wire:poll.60s>
    @if($stats['connected'])
        <div class="bg-[#001229] rounded-[2rem] border border-[#00B4D8]/20 shadow-2xl shadow-black/40 overflow-hidden relative group">
            {{-- Background Glow --}}
            <div class="absolute -top-24 -right-24 w-64 h-64 bg-[#00B4D8]/5 rounded-full blur-3xl group-hover:bg-[#00B4D8]/10 transition-all duration-1000"></div>
            
            <div class="relative p-8">
                <div class="flex flex-col lg:flex-row gap-12 items-center">
                    
                    {{-- Pulse: Realtime Vitals --}}
                    <div class="w-full lg:w-1/3 flex flex-col items-center lg:items-start text-center lg:text-right border-l border-white/5 pr-0 lg:pr-8">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#00B4D8] opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-[#00B4D8]"></span>
                            </span>
                            <span class="text-[10px] font-black text-[#00B4D8] uppercase tracking-[0.2em]">النبض الحي للموقع</span>
                        </div>
                        
                        <div class="flex items-baseline gap-4">
                            <div class="text-7xl font-black text-white tracking-tighter tabular-nums drop-shadow-[0_0_20px_rgba(0,180,216,0.3)]">
                                {{ $stats['realtime_users'] }}
                            </div>
                            <div class="text-sm font-bold text-[#94a3b8] uppercase tracking-widest pb-2">مستخدم نشط</div>
                        </div>

                        <div class="mt-6 flex items-center gap-6 w-full justify-center lg:justify-start">
                            <div>
                                <div class="text-xs text-[#64748b] font-bold mb-1">زيارات اليوم</div>
                                <div class="text-xl font-bold text-white flex items-center gap-2">
                                    {{ $stats['sessions_today'] }}
                                    @if($stats['change'] !== null)
                                        <span @class([
                                            'text-[10px] px-1.5 py-0.5 rounded-md flex items-center gap-0.5',
                                            'bg-emerald-500/10 text-emerald-400' => $stats['change'] >= 0,
                                            'bg-rose-500/10 text-rose-400' => $stats['change'] < 0,
                                        ])>
                                            {{ $stats['change'] >= 0 ? '+' : '' }}{{ abs($stats['change']) }}%
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="w-px h-8 bg-white/5"></div>
                            <div>
                                <div class="text-xs text-[#64748b] font-bold mb-1">أداء أمس</div>
                                <div class="text-xl font-bold text-white/60">{{ $stats['sessions_yesterday'] }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Trend Chart: The Surgical Precision --}}
                    <div class="w-full lg:w-2/3 h-48 relative">
                        <div class="absolute inset-0 flex items-end justify-between px-2">
                            @php
                                $maxData = max($stats['trend']['data'] ?: [1]);
                                $chartData = $stats['trend']['data'] ?: [0,0,0,0,0,0,0];
                                $count = count($chartData);
                                $width = 100 / max($count - 1, 1);
                                $points = [];
                                foreach($chartData as $i => $val) {
                                    $x = $i * $width;
                                    $y = 100 - (($val / $maxData) * 80); // Leave space at top
                                    $points[] = "$x,$y";
                                }
                                $path = implode(' ', $points);
                                $areaPath = "0,100 " . $path . " 100,100";
                            @endphp
                            
                            <svg viewBox="0 0 100 100" class="w-full h-full preserve-3d" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="cyanGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                        <stop offset="0%" stop-color="#00B4D8" stop-opacity="0.3" />
                                        <stop offset="100%" stop-color="#00B4D8" stop-opacity="0" />
                                    </linearGradient>
                                </defs>
                                
                                {{-- Area --}}
                                <polyline points="{{ $areaPath }}" fill="url(#cyanGradient)" />
                                
                                {{-- Line --}}
                                <polyline points="{{ $path }}" fill="none" stroke="#00B4D8" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                
                                {{-- Points --}}
                                @foreach($points as $point)
                                    @php list($px, $py) = explode(',', $point); @endphp
                                    <circle cx="{{ $px }}" cy="{{ $py }}" r="0.8" fill="#001229" stroke="#00B4D8" stroke-width="0.5" />
                                @endforeach
                            </svg>
                        </div>
                        
                        {{-- Labels --}}
                        <div class="absolute -bottom-6 inset-x-0 flex justify-between px-2 text-[8px] font-bold text-[#64748b] uppercase tracking-tighter">
                            @foreach($stats['trend']['labels'] as $label)
                                <span>{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>

                </div>

                <div class="mt-12 flex items-center justify-between pt-6 border-t border-white/5">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-[#00B4D8]/10 rounded-lg">
                            <x-heroicon-s-check-badge class="w-4 h-4 text-[#00B4D8]" />
                        </div>
                        <span class="text-[10px] font-bold text-[#64748b] uppercase tracking-widest">
                            نظام المراقبة متصل بـ GA4 API
                        </span>
                    </div>
                    <div class="text-[10px] font-medium text-[#475569] italic">
                        تحديث تلقائي: {{ now()->translatedFormat('h:i A') }}
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="bg-[#001229] rounded-[2rem] border border-dashed border-[#00B4D8]/20 p-12 flex flex-col items-center justify-center text-center group">
            <div class="w-20 h-20 bg-[#00B4D8]/5 rounded-[2rem] flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500">
                <x-heroicon-o-cpu-chip class="w-10 h-10 text-[#00B4D8]/40" />
            </div>
            <h3 class="text-xl font-bold text-white mb-3">تنشيط نظام المراقبة</h3>
            <p class="text-sm text-[#64748b] max-w-sm mb-8 leading-relaxed">
                لم يتم تفعيل الاتصال بـ Google API بعد. يرجى ربط حسابك لتتمكن من رؤية الرسوم البيانية وإحصائيات الزيارات مباشرة هنا.
            </p>
            <button 
                wire:click="$dispatch('open-connect-modal')"
                class="bg-[#00B4D8] text-[#001229] px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-[#00B4D8]/20 hover:bg-white hover:shadow-[#00B4D8]/40 transition-all duration-300"
            >
                بدء الربط الآن
            </button>
        </div>
    @endif

    <x-filament-actions::modals />
</div>

