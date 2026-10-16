{{-- resources/views/filament/pages/robots-txt-editor.blade.php --}}
<x-filament-panels::page>
    <x-filament::section>
        <div class="mb-4 text-sm text-gray-500">
            يمكنك هنا تحرير ملف robots.txt الخاص بموقعك لتوجيه برامج الزحف الخاصة بمحركات البحث.
        </div>
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-4 flex justify-end">
                @foreach($this->getFormActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </form>
    </x-filament::section>
</x-filament-panels::page>
