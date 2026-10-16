<?php

// app/Filament/Pages/WhatsappConversations.php

namespace Modules\Whatsapp\Filament\Pages;

use App\Helpers\SeoHelper;
use App\Helpers\SiteSettingHelper;
use App\Support\GuardsClinicModule;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Modules\Whatsapp\Filament\Resources\WhatsappTemplateResource;
use Modules\Whatsapp\Models\WhatsappLog;
use Modules\Whatsapp\Models\WhatsappTemplate;

class WhatsappConversations extends Page
{
    use GuardsClinicModule;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string $view = 'filament.pages.whatsapp-conversations';

    protected static ?string $title = 'مركز محادثات واتساب';

    protected static ?string $navigationLabel = 'المحادثات';

    protected static ?string $navigationGroup = 'مركز الواتساب';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return WhatsappLog::where('is_read', false)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public $selectedPhone = null;

    public $contacts = [];

    public $activeThread = [];

    public $newMessage = '';

    public $selectedTemplateId = null;

    public $searchQuery = '';

    public $editingMessageId = null;

    public $editingContent = '';

    public $pendingMessage = null;

    public $showConfirmation = false;

    // Template Filling State
    public $isFillingTemplate = false;

    public $currentTemplate = null;

    public $templateVariables = []; // Key-value pairs for variables

    public $manualVariables = [];   // List of variables requiring manual input

    public function mount()
    {
        if (! SeoHelper::getSetting('whatsapp_api_key')) {
            // We can keep this or remove if it's too intrusive
        }

        $this->loadContacts();
    }

    public function loadContacts()
    {
        $query = WhatsappLog::select(
            'patient_phone',
            'patient_name',
            DB::raw('MAX(created_at) as last_activity'),
            DB::raw('(SELECT message_content FROM whatsapp_logs wl WHERE wl.patient_phone = whatsapp_logs.patient_phone ORDER BY created_at DESC LIMIT 1) as last_message'),
            DB::raw('SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count')
        )
            ->groupBy('patient_phone', 'patient_name');

        if ($this->searchQuery) {
            $query->where(function ($q) {
                $q->where('patient_name', 'like', '%'.$this->searchQuery.'%')
                    ->orWhere('patient_phone', 'like', '%'.$this->searchQuery.'%');
            });
        }

        $this->contacts = $query->orderBy('last_activity', 'desc')
            ->get()
            ->toArray();
    }

    public function updatedSearchQuery()
    {
        $this->loadContacts();
    }

    public function selectContact($phone)
    {
        $this->selectedPhone = $phone;
        $this->loadThread();

        // Mark as read
        WhatsappLog::where('patient_phone', $phone)->update(['is_read' => true]);
        $this->loadContacts();

        $this->dispatch('contactSelected');
    }

    public function loadThread()
    {
        if (! $this->selectedPhone) {
            return;
        }

        $this->activeThread = WhatsappLog::where('patient_phone', $this->selectedPhone)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function useTemplate($templateId)
    {
        $template = WhatsappTemplate::find($templateId);
        if (! $template) {
            return;
        }

        $this->currentTemplate = $template->toArray();
        $this->templateVariables = [];
        $this->manualVariables = [];

        $contact = collect($this->contacts)->firstWhere('patient_phone', $this->selectedPhone);
        $patientName = $contact['patient_name'] ?? 'مريض';
        $clinicPhone = SiteSettingHelper::get('wa_number', '201017900354');

        $systemValues = [
            'patient_name' => $patientName,
            'patient_phone' => $this->selectedPhone,
            'clinic_phone' => $clinicPhone,
            'doctor_name' => 'د. محمد هلال',
            'date' => now()->format('Y-m-d'),
            'time' => now()->format('h:i A'),
        ];

        $vars = $template->variables_used ?? [];

        foreach ($vars as $var) {
            if (isset($systemValues[$var])) {
                $this->templateVariables[$var] = $systemValues[$var];
            } else {
                $this->templateVariables[$var] = '';
                $this->manualVariables[] = $var;
            }
        }

        if (empty($this->manualVariables)) {
            $this->applyTemplate();
        } else {
            $this->isFillingTemplate = true;
        }
    }

    public function applyTemplate()
    {
        if (! $this->currentTemplate) {
            return;
        }

        $content = $this->currentTemplate['content'];

        foreach ($this->templateVariables as $var => $value) {
            $content = str_replace('{'.$var.'}', $value, $content);
        }

        $this->newMessage = $content;
        $this->cancelFilling();
        $this->dispatch('templateApplied');
    }

    public function cancelFilling()
    {
        $this->isFillingTemplate = false;
        $this->currentTemplate = null;
        $this->templateVariables = [];
        $this->manualVariables = [];
    }

    public function deleteMessage($id)
    {
        WhatsappLog::find($id)?->delete();
        $this->loadThread();
        $this->loadContacts();
        Notification::make()->success()->title('تم حذف الرسالة من السجل')->send();
    }

    public function startEditing($id, $content)
    {
        $this->editingMessageId = $id;
        $this->editingContent = $content;
    }

    public function cancelEditing()
    {
        $this->editingMessageId = null;
        $this->editingContent = '';
    }

    public function updateMessage()
    {
        if (! $this->editingMessageId) {
            return;
        }

        $log = WhatsappLog::find($this->editingMessageId);
        if ($log) {
            $log->update(['message_content' => $this->editingContent]);
        }

        $this->editingMessageId = null;
        $this->editingContent = '';
        $this->loadThread();
        Notification::make()->success()->title('تم تحديث الرسالة')->send();
    }

    public function sendMessage()
    {
        if (empty($this->newMessage) || ! $this->selectedPhone) {
            return;
        }

        $this->pendingMessage = [
            'content' => $this->newMessage,
            'phone' => $this->selectedPhone,
            'name' => collect($this->contacts)->firstWhere('patient_phone', $this->selectedPhone)['patient_name'] ?? 'مريض',
        ];

        $cleanPhone = SiteSettingHelper::formatWhatsappNumber($this->selectedPhone);
        $url = 'https://wa.me/'.$cleanPhone.'?text='.urlencode($this->newMessage);

        $this->newMessage = '';
        $this->showConfirmation = true;

        $this->dispatch('messageSent', url: $url);
    }

    public function confirmSend()
    {
        if (! $this->pendingMessage) {
            return;
        }

        WhatsappLog::create([
            'patient_name' => $this->pendingMessage['name'],
            'patient_phone' => $this->pendingMessage['phone'],
            'message_content' => $this->pendingMessage['content'],
            'source' => 'manual',
            'status' => 'contacted',
            'is_read' => true,
        ]);

        $this->pendingMessage = null;
        $this->showConfirmation = false;
        $this->loadThread();
        $this->loadContacts();

        Notification::make()->success()->title('تم تسجيل الرسالة في السجل')->send();
    }

    public function cancelSend()
    {
        $this->pendingMessage = null;
        $this->showConfirmation = false;
        Notification::make()->info()->title('لم يتم تسجيل الرسالة')->send();
    }

    protected function getViewData(): array
    {
        return [
            'templates' => WhatsappTemplate::where('is_active', true)->get(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manageTemplates')
                ->label('إدارة القوالب')
                ->url(WhatsappTemplateResource::getUrl())
                ->color('gray')
                ->icon('heroicon-o-document-duplicate'),
        ];
    }
}
