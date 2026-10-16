{{-- resources/views/filament/resources/booking-resource/pages/booking-calendar.blade.php --}}
<x-filament-panels::page>
    <div class="bg-white rounded-3xl shadow-sm border overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b flex items-center justify-between bg-gray-50/50">
            <h2 class="text-xl font-bold text-navy flex items-center gap-2">
                <x-heroicon-o-calendar class="w-6 h-6 text-primary-500" />
                {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->translatedFormat('F Y') }}
            </h2>
            <div class="flex gap-2">
                <x-filament::button color="gray" icon="heroicon-o-chevron-right" wire:click="previousMonth" />
                <x-filament::button color="gray" wire:click="$set('currentMonth', {{ now()->month }}); $set('currentYear', {{ now()->year }}); calculateCalendar()">اليوم</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-o-chevron-left" wire:click="nextMonth" />
            </div>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-7 border-b bg-gray-50">
            @foreach(['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'] as $dayName)
                <div class="p-3 text-center text-xs font-bold text-gray-500 border-e last:border-0">{{ $dayName }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 auto-rows-[120px]">
            @foreach($days as $day)
                <div @class(['p-2 border-b border-e group hover:bg-gray-50 transition-colors relative', 'bg-white' => $day['is_current_month'], 'bg-gray-50/30' => !$day['is_current_month']])>
                    <div @class(['text-xs font-bold mb-1 px-2 py-0.5 rounded-full inline-block', 'bg-primary-600 text-white shadow-md' => $day['date']->isToday(), 'text-gray-400' => !$day['is_current_month'], 'text-gray-700' => $day['is_current_month'] && !$day['date']->isToday()])>
                        {{ $day['date']->format('d') }}
                    </div>

                    <div class="mt-1 space-y-1 overflow-y-auto max-h-[80px] custom-scrollbar">
                        @foreach($day['bookings'] as $booking)
                            <a 
                                href="{{ route('filament.admin.resources.bookings.edit', $booking) }}"
                                @class(['block text-[9px] p-1.5 rounded-lg border shadow-sm truncate font-medium hover:scale-[1.02] transition transform', 'bg-warning-50 border-warning-100 text-warning-700' => $booking->status === 'new', 'bg-success-50 border-success-100 text-success-700' => $booking->status === 'confirmed', 'bg-primary-50 border-primary-100 text-primary-700' => $booking->status === 'in_progress', 'bg-gray-100 border-gray-200 text-gray-700 line-through' => $booking->status === 'completed', 'bg-danger-50 border-danger-100 text-danger-700' => $booking->status === 'cancelled'])
                            >
                                <span class="font-bold opacity-60">{{ $booking->booking_time }}</span> | {{ $booking->patient_name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <style>
            .custom-scrollbar::-webkit-scrollbar { width: 3px; }
            .custom-scrollbar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
            .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #CBD5E1; }
        </style>
    </div>
</x-filament-panels::page>
