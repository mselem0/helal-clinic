<?php

// app/Filament/Pages/RobotsTxtEditor.php

namespace App\Filament\Pages;

use App\Helpers\SeoHelper;
use App\Support\AuthorizesSitePage;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RobotsTxtEditor extends Page implements HasForms
{
    use AuthorizesSitePage;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string $view = 'filament.pages.robots-txt-editor';

    protected static ?string $title = 'محرر Robots.txt';

    protected static ?string $navigationLabel = 'محرر Robots.txt';

    protected static ?string $navigationGroup = 'SEO & التسويق';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $content = SeoHelper::getSetting('robots_extra', '');
        $this->form->fill(['content' => $content]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('content')
                    ->label('تعليمات إضافية — حماية الإدارة والمعاينات وخريطة الموقع تلقائية')
                    ->rows(15)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الملف')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        SeoHelper::setSetting('robots_extra', $data['content'] ?? '');
        Notification::make()->title('تم حفظ ملف robots.txt بنجاح')->success()->send();
    }
}
