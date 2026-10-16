<?php

// app/Filament/Pages/SiteSettings.php

namespace App\Filament\Pages;

use App\Helpers\SiteSettingHelper;
use App\Models\SiteSetting;
use App\Support\AuthorizesSitePage;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use AuthorizesSitePage;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.site-settings';

    protected static ?string $title = 'إعدادات الموقع';

    protected static ?string $navigationLabel = 'إعدادات الموقع';

    protected static ?string $navigationGroup = 'الإعدادات';

    protected static bool $shouldRegisterNavigation = true;

    public ?array $data = [];

    public function mount(): void
    {
        $settings = SiteSetting::all()->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->tabs([
                        Tabs\Tab::make('المظهر')
                            ->schema([
                                ColorPicker::make('primary_color')->label('اللون الأساسي')->default('#00B4D8'),
                                ColorPicker::make('secondary_color')->label('اللون الثانوي')->default('#002855'),
                                Select::make('font_family')
                                    ->label('نوع الخط')
                                    ->options([
                                        'Cairo' => 'Cairo',
                                        'Almarai' => 'Almarai',
                                        'Tajawal' => 'Tajawal',
                                    ])->default('Cairo'),
                            ]),
                        Tabs\Tab::make('التواصل')
                            ->schema([
                                TextInput::make('email')->label('البريد الإلكتروني')->email(),
                                TextInput::make('footer_copyright')->label('نص حقوق النشر (الفوتر)'),
                            ]),
                        Tabs\Tab::make('واتساب')
                            ->schema([
                                TextInput::make('wa_number')->label('رقم واتساب')->required()->regex('/^[0-9]{10,15}$/')->helperText('بالصيغة الدولية دون +، مثال 201017900354'),
                                TextInput::make('wa_team_name')->label('اسم فريق خدمة العملاء'),
                                Textarea::make('wa_welcome_message')->label('رسالة الترحيب'),
                            ]),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('حفظ الإعدادات')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            $group = $this->getGroupForKey($key);
            SiteSettingHelper::set($key, $value, $group);
        }

        Notification::make()
            ->title('تم حفظ الإعدادات بنجاح')
            ->success()
            ->send();
    }

    private function getGroupForKey(string $key): string
    {
        if (in_array($key, ['primary_color', 'secondary_color', 'font_family'])) {
            return 'appearance';
        }
        if (in_array($key, ['email', 'footer_copyright'])) {
            return 'contact';
        }
        if (in_array($key, ['wa_number', 'wa_team_name', 'wa_welcome_message'])) {
            return 'whatsapp';
        }

        return 'appearance';
    }
}
