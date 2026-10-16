<x-filament-widgets::widget>
    @php 
        $score = $this->getReadinessScore(); 
        $issues = $this->getHealthIssues();
    @endphp
    <x-filament::section heading="مؤشر جاهزية المحتوى">
        <div class="flex flex-col gap-6">
            <!-- Readiness Meter -->
            <div class="flex items-center gap-6 p-4 bg-clinical-bg rounded-2xl border border-gray-100/50 shadow-inner">
                <div class="relative w-20 h-20 shrink-0">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                        <circle cx="18" cy="18" r="16" fill="none" class="stroke-gray-200" stroke-width="3"></circle>
                        <circle cx="18" cy="18" r="16" fill="none" @class(['transition-all duration-1000 ease-out', 'stroke-primary-500' => $score > 80, 'stroke-warning-500' => $score > 40 && $score <= 80, 'stroke-danger-500' => $score <= 40]) stroke-width="3" stroke-dasharray="{{ $score }}, 100" stroke-linecap="round"></circle>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center text-lg font-black text-navy">
                        {{ $score }}%
                    </div>
                </div>
                <div>
                    <div class="text-sm font-bold text-navy">مستوى اكتمال الموقع</div>
                    <p class="text-[10px] text-gray-500 mt-1 leading-relaxed">
                        @if($score === 100) محتوى الموقع مكتمل ومثالي للانطلاق! @else يحتاج الموقع لبعض التحسينات قبل الانطلاق الرسمي. @endif
                    </p>
                </div>
            </div>

            <!-- Alerts -->
            @if(count($issues) > 0)
                <div class="space-y-2">
                    @foreach($issues as $issue)
                        <div @class([
                            'flex items-center justify-between p-3 rounded-xl border text-[11px] font-medium transition',
                            'bg-danger-50 border-danger-100 text-danger-700' => $issue['type'] === 'danger',
                            'bg-warning-50 border-warning-100 text-warning-700' => $issue['type'] === 'warning',
                            'bg-blue-50 border-blue-100 text-blue-700' => $issue['type'] === 'info',
                        ])>
                            <div class="flex items-center gap-3">
                                @if($issue['type'] === 'danger') <x-heroicon-o-x-circle class="w-4 h-4 shrink-0" /> @endif
                                @if($issue['type'] === 'warning') <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0" /> @endif
                                @if($issue['type'] === 'info') <x-heroicon-o-information-circle class="w-4 h-4 shrink-0" /> @endif
                                <span>{{ $issue['text'] }}</span>
                            </div>
                            <a href="{{ $issue['link'] }}" class="font-bold underline decoration-dotted underline-offset-4 opacity-70 hover:opacity-100 transition">تعديل</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
