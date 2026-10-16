<?php

namespace App\Traits;

use App\Helpers\SeoHelper;
use App\Services\GoogleAnalyticsService;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Route;

trait HasAnalyticsActions
{
    public function connectAction(): Action
    {
        return Action::make('connect')
            ->label('إعداد الربط')
            ->modalHeading('ربط أدوات التتبع والتحليلات')
            ->modalDescription('أدخل معرفات التتبع الخاصة بك لتمكين نظام المراقبة المتقدم.')
            ->modalSubmitActionLabel('حفظ الإعدادات')
            ->modalWidth('lg')
            ->form([
                Section::make('Google Analytics (GA4)')
                    ->description('اربط موقعك بـ Google Analytics للحصول على إحصائيات حية.')
                    ->aside()
                    ->schema([
                        TextInput::make('google_analytics_id')
                            ->label('Measurement ID (G-XXXXXX)')
                            ->placeholder('G-XXXXXXXXXX')
                            ->default(fn () => SeoHelper::getSetting('google_analytics_id')),

                        TextInput::make('google_property_id')
                            ->label('Property ID')
                            ->placeholder('123456789')
                            ->helperText('مطلوب لاستخدام الـ API المتقدم والرسوم البيانية.')
                            ->default(fn () => SeoHelper::getSetting('google_property_id')),

                        Actions::make([
                            Actions\Action::make('google_oauth')
                                ->label(fn () => app(GoogleAnalyticsService::class)->isConnected() ? 'إعادة الاتصال بحساب Google' : 'الاتصال بحساب Google')
                                ->color(fn () => app(GoogleAnalyticsService::class)->isConnected() ? 'success' : 'primary')
                                ->icon('heroicon-o-link')
                                ->url(fn () => Route::has('admin.google.connect') ? route('admin.google.connect') : null)
                                ->visible(fn () => config('clinic-integrations.google') && Route::has('admin.google.connect'))
                                ->openUrlInNewTab(),
                        ]),
                    ]),

                Section::make('Facebook & Tag Manager')
                    ->description('تتبع التحويلات وإدارة العلامات بسهولة.')
                    ->aside()
                    ->schema([
                        TextInput::make('facebook_pixel_id')
                            ->label('Facebook Pixel ID')
                            ->placeholder('123456789012345')
                            ->default(fn () => SeoHelper::getSetting('facebook_pixel_id')),

                        TextInput::make('gtm_id')
                            ->label('Google Tag Manager ID')
                            ->placeholder('GTM-XXXXXXX')
                            ->default(fn () => SeoHelper::getSetting('gtm_id')),
                    ]),
            ])
            ->action(function (array $data) {
                SeoHelper::setSetting('google_analytics_id', $data['google_analytics_id'], 'verification');
                SeoHelper::setSetting('google_property_id', $data['google_property_id'], 'verification');
                SeoHelper::setSetting('facebook_pixel_id', $data['facebook_pixel_id'], 'verification');
                SeoHelper::setSetting('gtm_id', $data['gtm_id'], 'verification');

                Notification::make()
                    ->title('تم حفظ إعدادات التتبع بنجاح')
                    ->success()
                    ->send();

                // Refresh the page or trigger a refresh event
                return redirect(request()->header('Referer'));
            });
    }
}
