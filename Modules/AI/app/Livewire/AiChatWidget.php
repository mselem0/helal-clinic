<?php

namespace Modules\AI\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\AI\Models\AiConversation;
use Modules\AI\Services\AiAgent\AgentOrchestrator;

class AiChatWidget extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public bool $isInline = false;

    public bool $isOpen = false;

    public string $message = '';

    public ?AiConversation $conversation = null;

    public array $messages = [];

    public bool $isLoading = false;

    public function mount()
    {
        $this->conversation = AiConversation::where('user_id', Auth::id())
            ->latest()
            ->first();

        if ($this->conversation) {
            $this->loadMessages();
        }
    }

    public function toggleChat()
    {
        $this->isOpen = ! $this->isOpen;
        if ($this->isOpen && ! $this->conversation) {
            $this->startNewConversation();
        }
    }

    public function startNewConversation()
    {
        $this->conversation = AiConversation::create([
            'user_id' => Auth::id(),
            'title' => 'محادثة جديدة '.now()->format('Y-m-d H:i'),
        ]);
        $this->messages = [];
    }

    public function loadMessages()
    {
        $this->messages = $this->conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function sendMessage()
    {
        if (empty(trim($this->message))) {
            return;
        }

        if (Gate::denies('create', AiConversation::class)) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        if (RateLimiter::tooManyAttempts('ai-agent:'.Auth::id(), 10)) {
            Notification::make()->title('الرجاء الانتظار')->warning()->send();

            return;
        }

        if (! $this->conversation) {
            $this->startNewConversation();
        }

        $userMessageText = $this->message;

        // 1. Save User Message (This updates the UI instantly)
        $userMsg = $this->conversation->messages()->create([
            'role' => 'user',
            'content' => $userMessageText,
        ]);
        $this->messages[] = $userMsg->toArray();

        $this->message = '';
        $this->isLoading = true;

        // 2. Dispatch event to browser to trigger AI response in a second request
        $this->dispatch('message-added', text: $userMessageText);
    }

    #[On('process-ai')]
    public function generateAssistantResponse($userMessageText)
    {
        RateLimiter::hit('ai-agent:'.Auth::id(), 60);

        $cacheKey = 'ai_response_'.md5($userMessageText);
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse) {
            $this->saveAndRenderAssistantMessage($cachedResponse['answer'], $cachedResponse['sources'] ?? null);

            return;
        }

        // 2. Prepare History (using Content objects)
        $history = collect($this->messages)
            ->slice(-11) // Last 11 (includes the user message we just added)
            ->filter(fn ($m) => $m['content'] !== $userMessageText || $m['role'] !== 'user') // Filter out the current one for separate handling in orchestrator
            ->map(function ($m) {
                $role = $m['role'] === 'assistant' ? Role::MODEL : Role::USER;
                $content = is_array($m['content']) ? json_encode($m['content'], JSON_UNESCAPED_UNICODE) : (string) $m['content'];

                return Content::parse(part: $content, role: $role);
            })
            ->values()
            ->all();

        // 3. Call Agent
        try {
            $orchestrator = app(AgentOrchestrator::class);
            $result = $orchestrator->handle($userMessageText, $history);

            $this->saveAndRenderAssistantMessage(
                $result['answer'],
                $result['sources'] ?? null,
                $result['tool_used'] ?? null,
                $result['active_model'] ?? null
            );

            if (empty($result['tool_used'])) {
                Cache::put($cacheKey, $result, now()->addHours(24));
            }

        } catch (\Exception $e) {
            Notification::make()->title('خطأ في الاتصال')->danger()->send();
            $this->isLoading = false;
        }
    }

    protected function saveAndRenderAssistantMessage($content, $sources = null, $tool = null, $activeModel = null)
    {
        $aiMsg = $this->conversation->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'sources' => $sources,
            'tools_used' => $tool ? [$tool] : null,
            'metadata' => $activeModel ? ['active_model' => $activeModel] : null,
        ]);
        $this->messages[] = $aiMsg->toArray();
        $this->isLoading = false;
        $this->dispatch('scroll-to-bottom');
    }

    public function render()
    {
        return view('livewire.ai-chat-widget');
    }
}
