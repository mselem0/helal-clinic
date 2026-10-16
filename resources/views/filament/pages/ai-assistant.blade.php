<x-filament-panels::page>
@if(!config('clinic-integrations.ai'))<x-filament::section heading="التكامل غير مفعّل"><p>المساعد جاهز للإعداد. أكمل بيانات الاتصال وفعّل الخدمة قبل بدء المحادثات.</p></x-filament::section>@else
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
        {{-- Sidebar / History --}}
        <div class="lg:col-span-1">
            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                <h3 class="mb-4 text-sm font-bold text-gray-500">المحادثات السابقة</h3>
                <div class="flex flex-col gap-2">
                    @php
                        $conversations = \Modules\AI\Models\AiConversation::where('user_id', auth()->id())->latest()->take(10)->get();
                    @endphp
                    @foreach($conversations as $conv)
                        <button wire:click="selectConversation({{ $conv->id }})"
                            @class([
                                'w-full rounded-xl border px-4 py-3 text-right text-sm transition-all duration-200 focus:outline-none',
                                'border-primary-500 bg-primary-50/40 text-primary-700 font-extrabold shadow-sm dark:border-primary-400 dark:bg-primary-900/10 dark:text-primary-400' => $activeConversationId === $conv->id,
                                'border-gray-100 text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800/50' => $activeConversationId !== $conv->id,
                            ])>
                            <div class="flex items-center justify-between">
                                <span class="truncate">{{ $conv->title ?: 'محادثة جديدة' }}</span>
                                @if($activeConversationId === $conv->id)
                                    <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                                @endif
                            </div>
                            <div class="mt-1 text-[9px] font-semibold text-gray-400 dark:text-gray-500">{{ $conv->created_at->diffForHumans() }}</div>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Main Chat Area --}}
        <div class="lg:col-span-3">
            <div class="flex h-[700px] flex-col overflow-hidden rounded-3xl bg-white shadow-sm dark:bg-gray-800">
                @livewire('ai-chat-widget', ['isInline' => true])
            </div>
        </div>
    </div>
@endif
</x-filament-panels::page>
