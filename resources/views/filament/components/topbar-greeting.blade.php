{{-- resources/views/filament/components/topbar-greeting.blade.php --}}
@php
    $hour = now()->hour;
    if ($hour < 12) {
        $greeting = 'صباح الخير';
    } elseif ($hour < 17) {
        $greeting = 'طاب يومك';
    } else {
        $greeting = 'مساء الخير';
    }
    
    $hero = \App\Models\HeroSetting::first();
    $avatarUrl = $hero?->getFirstMediaUrl('doctor-photo') ?: asset('assets/doctor.jpg');
@endphp

<div class="flex items-center gap-3 pr-2 select-none">
    <!-- Compact Avatar in Topbar -->
    <div class="relative shrink-0 hidden sm:block">
        <div class="w-10 h-10 rounded-xl bg-white p-[2px] shadow-sm border border-navy/5 overflow-hidden">
            <div class="w-full h-full rounded-[0.5rem] overflow-hidden bg-navy/5">
                <img src="{{ $avatarUrl }}" class="w-full h-full object-cover object-top opacity-95">
            </div>
        </div>
        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white shadow-sm"></div>
    </div>
    
    <!-- Greeting Text -->
    <div class="flex flex-col text-right">
        <span class="text-[10px] font-bold text-navy/40 leading-none mb-1">{{ $greeting }}،</span>
        <span class="text-xs font-black text-cyan-brand leading-none">د. محمد هلال</span>
    </div>
</div>
