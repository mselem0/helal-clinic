<x-filament-panels::page>
    @include('filament.resources.website-booking-requests.sheets-status')
    <x-filament-panels::resources.tabs />
    <div class="overflow-x-auto"><x-filament::tabs label="الفروع" class="min-w-max">
        <x-filament::tabs.item :active="$branch === null" wire:click="$set('branch', null)">كل الفروع</x-filament::tabs.item>
        @foreach ($this->getBranches() as $clinicBranch)
            <x-filament::tabs.item :active="(string) $branch === (string) $clinicBranch->id" wire:click="$set('branch', '{{ $clinicBranch->id }}')">{{ $clinicBranch->name }}</x-filament::tabs.item>
        @endforeach
    </x-filament::tabs></div>
    {{ $this->table }}
</x-filament-panels::page>
