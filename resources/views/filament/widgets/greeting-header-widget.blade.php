{{-- resources/views/filament/widgets/greeting-header-widget.blade.php --}}
<x-filament-widgets::widget>
    <div class="relative glass-surface rounded-[2.5rem] p-8 shadow-premium overflow-hidden group transition-all duration-500 hover:shadow-glass bg-white/80">
        @php $pulse = $this->getPulseMetrics(); @endphp
        
        <!-- Refined Background Elements aligned with Dr. Mohamed's identity -->
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[radial-gradient(#002855_1px,transparent_1px)] [background-size:24px_24px]"></div>
        <div class="absolute -right-10 -top-10 w-80 h-80 bg-[#00B4D8]/5 rounded-full blur-[100px] group-hover:bg-[#00B4D8]/10 transition-colors duration-1000"></div>
        <div class="absolute -left-10 -bottom-10 w-64 h-64 bg-[#002855]/5 rounded-full blur-[80px]"></div>

        <div class="relative z-10 flex flex-col xl:flex-row xl:items-center justify-between gap-8 text-right">
            <!-- Personalized Arabic Greeting with Medical Theme -->
            <div class="flex items-center gap-5">
                <div class="p-4 rounded-3xl bg-[#00B4D8]/10 border border-[#00B4D8]/20 text-[#00B4D8] shadow-inner">
                    <x-heroicon-s-user-circle class="w-9 h-9" />
                </div>
                <div class="space-y-1">
                    <h2 class="text-2xl font-black text-[#002855] leading-tight">
                        {{ $this->getGreeting() }}، د. محمد هلال
                    </h2>
                    <div class="flex items-center gap-2.5 pt-1">
                        <span class="text-[9px] font-black uppercase tracking-[0.1em] text-[#002855]/40">Clinical Suite Portal</span>
                        <div class="h-px w-5 bg-[#002855]/10"></div>
                        <span class="text-[10px] font-bold text-gray-500">نظام المتابعة والتشغيل الطبي الذكي</span>
                    </div>
                </div>
            </div>

            <!-- Operational Clinic Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 lg:gap-12 xl:px-8 xl:border-r xl:border-l xl:border-gray-100">
                <!-- Today's Patients -->
                <div class="relative group/metric">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 flex items-center gap-2 justify-end">
                        حجوزات اليوم
                        <span @class([
                            'text-[9px] px-1.5 py-0.5 rounded-full font-bold',
                            'bg-emerald-50 text-emerald-600 border border-emerald-100' => $pulse['trend'] === 'up',
                            'bg-rose-50 text-rose-600 border border-rose-100' => $pulse['trend'] === 'down',
                        ])>
                            {{ $pulse['trend'] === 'up' ? '+' : '-' }}{{ $pulse['percentage'] }}
                        </span>
                    </div>
                    <div class="text-3xl font-black text-[#002855] group-hover/metric:text-[#00B4D8] transition-colors duration-300 tracking-tight">{{ $pulse['bookings_today'] }}</div>
                </div>

                <!-- Unconfirmed Bookings (Pending) -->
                <div class="relative group/metric">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 flex items-center gap-2 justify-end">
                        طلبات جديدة
                        @if($pulse['pending_bookings'] > 0)
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                            </span>
                        @endif
                    </div>
                    <div class="flex items-baseline gap-2 justify-end">
                        <div class="text-3xl font-black text-[#002855] group-hover/metric:text-amber-500 transition-colors duration-300 tracking-tight">
                            {{ $pulse['pending_bookings'] }}
                        </div>
                        @if($pulse['pending_bookings'] > 0)
                            <span class="text-[9px] font-black text-amber-500 tracking-tighter">بانتظار التأكيد</span>
                        @endif
                    </div>
                </div>

                <!-- Messaging Logs -->
                <div class="relative group/metric col-span-2 md:col-span-1">
                    <div class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 justify-end flex items-center gap-2">
                        محادثات واتساب
                    </div>
                    <div class="flex items-baseline gap-2 justify-end">
                        <div @class(['text-3xl font-black tracking-tight transition-colors duration-300', 'text-emerald-600 group-hover/metric:text-emerald-500' => $pulse['unread_whatsapp'] > 0, 'text-[#002855] group-hover/metric:text-[#00B4D8]' => $pulse['unread_whatsapp'] === 0])>
                            {{ $pulse['unread_whatsapp'] }}
                        </div>
                        @if($pulse['unread_whatsapp'] > 0)
                            <span class="text-[9px] font-black text-emerald-500 animate-pulse tracking-tighter">معلقة</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Dynamic Clock & Date -->
            <div class="flex items-center gap-6 justify-between xl:justify-end xl:pl-2">
                <div class="text-right">
                    <!-- Real-time Ticking digital clock in Arabic (Alpine.js) -->
                    <div x-data="{ 
                        time: '',
                        updateTime() {
                            const now = new Date();
                            this.time = now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', hour12: true });
                        }
                    }" x-init="updateTime(); setInterval(() => updateTime(), 1000)" 
                    class="text-2xl font-black text-[#002855] tracking-tight leading-none" 
                    x-text="time"></div>
                    
                    <div class="text-[10px] font-semibold text-gray-400 mt-1.5">
                        {{ $this->getArabicDate() }}
                    </div>
                </div>
                <button onclick="window.location.reload()" class="group/refresh p-4.5 rounded-2xl bg-white border border-gray-100 text-[#002855] hover:bg-[#002855] hover:text-white transition-all duration-500 shadow-premium">
                    <x-heroicon-o-arrow-path class="w-5 h-5 group-hover/refresh:rotate-180 transition-transform duration-700" />
                </button>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
