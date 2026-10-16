<?php

// app/Filament/Pages/RobotsTxtEditor.php

namespace App\Filament\Pages;

use App\Support\AuthorizesSitePage;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

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
        $path = base_path('public/robots.txt');
        $content = File::exists($path) ? File::get($path) : "User-agent: *\nAllow: /\n\nSitemap: ".url('/sitemap.xml');
        $this->form->fill(['content' => $content]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('content')
                    ->label('محتوى ملف robots.txt')
                    ->rows(15)
                    ->required()
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
        File::put(base_path('public/robots.txt'), $data['content']);
        Notification::make()->title('تم حفظ ملف robots.txt بنجاح')->success()->send();
    }
}
