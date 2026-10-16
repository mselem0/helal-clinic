<?php

namespace Modules\AI\Filament\Pages;

use App\Support\GuardsClinicModule;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Modules\AI\Models\AiConversation;

class AiAssistant extends Page
{
    use GuardsClinicModule;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.ai-assistant';

    protected static ?string $title = 'المساعد الذكي';

    protected static ?string $navigationGroup = 'AI Assistant';

    protected static ?int $navigationSort = -1;

    public ?int $activeConversationId = null;

    public function mount(): void
    {
        $latest = AiConversation::where('user_id', auth()->id())
            ->latest()
            ->first();

        if ($latest) {
            $this->activeConversationId = $latest->id;
        }
    }

    public function selectConversation($conversationId): void
    {
        $this->activeConversationId = AiConversation::where('user_id',auth()->id())->findOrFail($conversationId)->id;
        $this->dispatch('loadConversation', conversationId: $conversationId);
    }

    #[On('loadConversation')]
    public function handleConversationLoaded($conversationId): void
    {
        $this->activeConversationId = AiConversation::where('user_id',auth()->id())->findOrFail($conversationId)->id;
    }
}
