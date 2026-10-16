<?php

// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Modules\ActivityLog\Policies\ActivityPolicy;
use Nwidart\Modules\Facades\Module;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('ai-agent', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        if (class_exists(Module::class) && Module::find('ActivityLog')?->isEnabled() && class_exists(ActivityPolicy::class)) {
            Gate::policy(Activity::class, ActivityPolicy::class);
        }

        foreach (['PatientCore' => ['Patient', 'VisitRecord'], 'Booking' => ['Booking'], 'Clinical' => ['Prescription', 'PrintTemplate', 'MedicalHistoryItem', 'PatientMedication'], 'Whatsapp' => ['WhatsappLog', 'WhatsappTemplate']] as $module => $models) {
            if (! Module::find($module)?->isEnabled()) {
                continue;
            }
            foreach ($models as $model) {
                $policy = 'Modules\\'.$module.'\\Policies\\'.$model.'Policy';
                $class = 'Modules\\'.$module.'\\Models\\'.$model;
                if (class_exists($policy)) {
                    Gate::policy($class, $policy);
                }
            }
        }
        if (Module::find('AccessControl')?->isEnabled()) { Gate::policy(\Spatie\Permission\Models\Role::class, \Modules\AccessControl\Policies\RolePolicy::class); }
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        Blade::directive('seoMeta', function ($expression) {
            return "<?php
                \$seoPage = \App\Helpers\SeoHelper::getPage($expression);
                if (\$seoPage) {
                    echo '<title>' . e(\$seoPage->meta_title) . '</title>' . PHP_EOL;
                    echo '<meta name=\"description\" content=\"' . e(\$seoPage->meta_description) . '\">' . PHP_EOL;
                    
                    if (!empty(\$seoPage->meta_keywords)) {
                        \$keywords = is_array(\$seoPage->meta_keywords) ? implode(', ', \$seoPage->meta_keywords) : \$seoPage->meta_keywords;
                        echo '<meta name=\"keywords\" content=\"' . e(\$keywords) . '\">' . PHP_EOL;
                    }
                    
                    if (\$seoPage->canonical_url) {
                        echo '<link rel=\"canonical\" href=\"' . e(\$seoPage->canonical_url) . '\">' . PHP_EOL;
                    }
                    
                    if (\$seoPage->robots) {
                        \$robotsMap = [
                            'index_follow' => 'index, follow',
                            'noindex_follow' => 'noindex, follow',
                            'index_nofollow' => 'index, nofollow',
                            'noindex_nofollow' => 'noindex, nofollow'
                        ];
                        \$robotsValue = \$robotsMap[\$seoPage->robots] ?? 'index, follow';
                        echo '<meta name=\"robots\" content=\"' . \$robotsValue . '\">' . PHP_EOL;
                    }
                    
                    // Open Graph
                    echo '<meta property=\"og:title\" content=\"' . e(\$seoPage->og_title ?: \$seoPage->meta_title) . '\">' . PHP_EOL;
                    echo '<meta property=\"og:description\" content=\"' . e(\$seoPage->og_description ?: \$seoPage->meta_description) . '\">' . PHP_EOL;
                    if (\$seoPage->og_type) {
                        echo '<meta property=\"og:type\" content=\"' . e(\$seoPage->og_type) . '\">' . PHP_EOL;
                    }
                    if (\$ogImage = \$seoPage->getFirstMediaUrl('seo-page-og')) {
                        echo '<meta property=\"og:image\" content=\"' . e(\$ogImage) . '\">' . PHP_EOL;
                    } else if (\$defaultOg = \App\Helpers\SeoHelper::getSetting('default_og_image')) {
                        echo '<meta property=\"og:image\" content=\"' . e(asset('storage/' . \$defaultOg)) . '\">' . PHP_EOL;
                    }
                    
                    // Twitter
                    echo '<meta name=\"twitter:card\" content=\"' . e(\$seoPage->twitter_card ?: 'summary_large_image') . '\">' . PHP_EOL;
                    echo '<meta name=\"twitter:title\" content=\"' . e(\$seoPage->twitter_title ?: \$seoPage->meta_title) . '\">' . PHP_EOL;
                    echo '<meta name=\"twitter:description\" content=\"' . e(\$seoPage->twitter_description ?: \$seoPage->meta_description) . '\">' . PHP_EOL;
                    if (\$twitterImage = \$seoPage->getFirstMediaUrl('seo-page-twitter')) {
                        echo '<meta name=\"twitter:image\" content=\"' . e(\$twitterImage) . '\">' . PHP_EOL;
                    }
                }
            ?>";
        });

        Blade::directive('schema', function ($expression) {
            return "<?php
                \$seoPage = \App\Helpers\SeoHelper::getPage($expression);
                if (\$seoPage && \$seoPage->schemas) {
                    foreach (\$seoPage->schemas as \$schema) {
                        if (\$schema->is_active && !empty(\$schema->schema_data)) {
                            \$schemaData = \$schema->schema_data;
                            
                            if (\$schema->schema_type === 'Custom') {
                                // For Custom type, use raw_json field directly
                                \$rawJson = \$schemaData['raw_json'] ?? null;
                                if (\$rawJson) {
                                    \$decoded = json_decode(\$rawJson, true);
                                    if (\$decoded) {
                                        echo '<script type=\"application/ld+json\">' . json_encode(\$decoded, JSON_UNESCAPED_UNICODE) . '</script>' . PHP_EOL;
                                    }
                                }
                            } elseif (\$schema->schema_type === 'FAQPage') {
                                // For FAQPage, build proper @context/@type and mainEntity from nested structure
                                \$mainEntity = \$schemaData['mainEntity'] ?? [];
                                \$faqItems = [];
                                foreach (\$mainEntity as \$item) {
                                    if (!empty(\$item['name']) && !empty(\$item['acceptedAnswer']['text'])) {
                                        \$faqItems[] = [
                                            '@type' => 'Question',
                                            'name' => \$item['name'],
                                            'acceptedAnswer' => ['@type' => 'Answer', 'text' => \$item['acceptedAnswer']['text']],
                                        ];
                                    }
                                }
                                if (!empty(\$faqItems)) {
                                    \$output = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => \$faqItems];
                                    echo '<script type=\"application/ld+json\">' . json_encode(\$output, JSON_UNESCAPED_UNICODE) . '</script>' . PHP_EOL;
                                }
                            } else {
                                // For Physician, VideoObject, etc. add @context and @type
                                \$schemaData['@context'] = 'https://schema.org';
                                \$schemaData['@type'] = \$schema->schema_type;
                                // Remove any internal helper keys
                                unset(\$schemaData['raw_json'], \$schemaData['mainEntity']);
                                echo '<script type=\"application/ld+json\">' . json_encode(\$schemaData, JSON_UNESCAPED_UNICODE) . '</script>' . PHP_EOL;
                            }
                        }
                    }
                }
            ?>";
        });

        Blade::directive('seoGlobal', function () {
            return "<?php
                \$verifications = [
                    'google' => \App\Helpers\SeoHelper::getSetting('google_verification'),
                    'bing' => \App\Helpers\SeoHelper::getSetting('bing_verification'),
                    'yandex' => \App\Helpers\SeoHelper::getSetting('yandex_verification'),
                    'baidu' => \App\Helpers\SeoHelper::getSetting('baidu_verification'),
                ];
                
                foreach (\$verifications as \$key => \$value) {
                    if (\$value) {
                        if (str_contains(\$value, '<meta')) {
                            echo \$value . PHP_EOL;
                        } else {
                            \$name = \$key === 'google' ? 'google-site-verification' : (\$key === 'bing' ? 'msvalidate.01' : (\$key === 'yandex' ? 'yandex-verification' : 'baidu-site-verification'));
                            echo '<meta name=\"' . \$name . '\" content=\"' . e(\$value) . '\">' . PHP_EOL;
                        }
                    }
                }
                
                // Google Analytics (GA4) - Use Measurement ID (G-XXXX)
                \$gaId = \App\Helpers\SeoHelper::getSetting('google_analytics_id');
                if (\$gaId) {
                    echo \"<!-- Google Analytics (GA4) -->\" . PHP_EOL;
                    echo \"<script async src='https://www.googletagmanager.com/gtag/js?id=\" . e(\$gaId) . \"'></script>\" . PHP_EOL;
                    echo \"<script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', '\" . e(\$gaId) . \"');</script>\" . PHP_EOL;
                }

                // Google Tag Manager (GTM)
                \$gtmId = \App\Helpers\SeoHelper::getSetting('gtm_id');
                if (\$gtmId) {
                    echo \"<!-- Google Tag Manager -->\" . PHP_EOL;
                    echo \"<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                    })(window,document,'script','dataLayer','\" . e(\$gtmId) . \"');</script>\" . PHP_EOL;
                }

                // Facebook Pixel
                \$fbPixelId = \App\Helpers\SeoHelper::getSetting('facebook_pixel_id');
                if (\$fbPixelId) {
                    echo \"<!-- Facebook Pixel -->\" . PHP_EOL;
                    echo \"<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                    document,'script','https://connect.facebook.net/en_US/fbevents.js');
                    fbq('init', '\" . e(\$fbPixelId) . \"'); fbq('track', 'PageView');</script>\" . PHP_EOL;
                    echo \"<noscript><img height='1' width='1' style='display:none' src='https://www.facebook.com/tr?id=\" . e(\$fbPixelId) . \"&ev=PageView&noscript=1'/></noscript>\" . PHP_EOL;
                }
                ?>";
        });

        Blade::directive('seoHeader', function () {
            return "<?php
                \$scripts = json_decode(\App\Helpers\SeoHelper::getSetting('custom_scripts', '[]'), true);
                if (is_array(\$scripts)) {
                    foreach (\$scripts as \$script) {
                        if ((\$script['is_active'] ?? true) && (\$script['location'] ?? 'header') === 'header') {
                            echo \$script['code'] . PHP_EOL;
                        }
                    }
                }
                ?>";
        });

        Blade::directive('seoFooter', function () {
            return "<?php
                \$scripts = json_decode(\App\Helpers\SeoHelper::getSetting('custom_scripts', '[]'), true);
                if (is_array(\$scripts)) {
                    foreach (\$scripts as \$script) {
                        if ((\$script['is_active'] ?? true) && (\$script['location'] ?? 'header') === 'footer') {
                            echo \$script['code'] . PHP_EOL;
                        }
                    }
                }
                ?>";
        });
    }
}
