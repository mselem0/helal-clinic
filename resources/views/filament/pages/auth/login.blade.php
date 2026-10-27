{{-- resources/views/filament/pages/auth/login.blade.php --}}
<div class="clinic-login flex min-h-screen w-full bg-white" dir="rtl">
    <!-- Branding Panel (Appears on the Right in RTL) -->
    <div class="clinic-login__brand hidden lg:flex lg:w-1/2 relative items-center justify-center overflow-hidden bg-[#002855]">
        <!-- Decorative Background -->
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 bg-gradient-to-br from-[#002855]/95 via-[#002855]/90 to-[#001a35]/95 z-10"></div>
            <img src="{{ asset('assets/doctor.jpg') }}" class="w-full h-full object-cover opacity-60" alt="Background">
            
            <!-- Animated decorative circles -->
            <div class="absolute top-0 left-0 w-96 h-96 bg-[#5B969B]/10 rounded-full filter blur-3xl -ml-48 -mt-48"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-[#5B969B]/5 rounded-full filter blur-3xl -mr-48 -mb-48"></div>
        </div>

        <div class="relative z-20 text-center px-12 max-w-2xl">
            <div class="clinic-login__logo inline-flex items-center justify-center rounded-[2rem] shadow-2xl">
                <img src="{{ asset('assets/logo.png') }}" alt="عيادات هلال">
            </div>
            
            <h2 class="mt-8 text-3xl font-extrabold text-white tracking-tight leading-tight">
                لوحة إدارة عيادات هلال
            </h2>
            
            <!-- <p class="text-slate-300 text-lg leading-relaxed mb-12">
                نظام الإدارة المتطور المصمم خصيصاً لتعزيز تجربة المرضى وإدارة العيادة بكفاءة متناهية وأمان عالٍ.
            </p>
 
            <div class="pt-12 border-t border-white/10">
                <p class="text-white/40 text-[10px] font-bold uppercase tracking-[0.2em] mb-8">شركاء النجاح والثقة</p>
                <div class="flex items-center justify-center gap-12">
                    <div class="group flex flex-col items-center gap-3">
                        <div class="h-20 w-20 flex items-center justify-center p-3 bg-white rounded-2xl border border-white/10 group-hover:bg-gray-50 transition-all duration-500">
                            <img src="{{ asset('partner_logo_1.png') }}" alt="Egyptian Society of Andrology" class="max-h-full max-w-full object-contain opacity-80 group-hover:opacity-100 transition-opacity">
                        </div>
                        <span class="text-[10px] text-white/40 font-medium text-center">الجمعية المصرية للذكورة</span>
                    </div>
                    
                    <div class="group flex flex-col items-center gap-3">
                        <div class="h-20 w-20 flex items-center justify-center p-3 bg-white rounded-2xl border border-white/10 group-hover:bg-gray-50 transition-all duration-500">
                            <img src="{{ asset('partner_logo_2.png') }}" alt="International Fertility Hospital" class="max-h-full max-w-full object-contain opacity-80 group-hover:opacity-100 transition-opacity">
                        </div>
                        <span class="text-[10px] text-white/40 font-medium text-center">مستشفى الخصوبة الدولي</span>
                    </div>
                </div>
            </div> -->
        </div>
    </div>

    <!-- Login Form Panel (Appears on the Left in RTL) -->
    <div class="clinic-login__form-panel w-full lg:w-1/2 flex items-center justify-center p-6 md:p-12 bg-white">
        <!-- Mobile Logo (shows only when branding panel is hidden) -->
        <div class="clinic-login__mobile-logo lg:hidden" aria-hidden="true">
            <img src="{{ asset('assets/logo.png') }}" alt="عيادات هلال">
        </div>

        <div class="w-full max-w-[440px] animate-fade-in">
            <!-- Form Header -->
            <div class="flex flex-col items-center mb-10 text-center">
                <h1 class="text-3xl font-black text-[#002855] tracking-tight mb-2">
                    تسجيل الدخول
                </h1>
                <div class="flex items-center gap-2">
                    <span class="h-px w-4 bg-[#5B969B]/40"></span>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest text-center">لوحة تحكم الإدارة الذكية</p>
                    <span class="h-px w-4 bg-[#5B969B]/40"></span>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white/80 backdrop-blur-sm p-8 md:p-10 rounded-[2.5rem] shadow-premium border border-white">
                @if (filament()->hasRegistration())
                    <div class="mb-8 p-3 bg-primary-50 rounded-2xl text-center">
                        <span class="text-sm text-primary-700 font-medium">
                            {{ __('filament-panels::pages/auth/login.actions.register.before') }}
                        </span>
                        {{ $this->registerAction }}
                    </div>
                @endif

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

                <x-filament-panels::form id="form" wire:submit="authenticate">
                    {{ $this->form }}

                    @if (filled(config('recaptcha.api_site_key')))
                        <div class="mt-6 flex justify-center">
                            <div class="overflow-hidden rounded-2xl shadow-sm border border-gray-100 p-1 bg-gray-50/50 scale-[0.9] origin-center">
                                {!! htmlFormSnippet() !!}
                            </div>
                        </div>
                    @endif

                    <div class="mt-8">
                        <button
                            type="submit"
                            class="relative z-50 w-full flex justify-center items-center px-6 py-3.5 border border-transparent text-base font-bold rounded-2xl text-white bg-[#002855] hover:bg-[#5B969B] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#5B969B] transition-all duration-300 shadow-xl"
                            style="display: flex !important; visibility: visible !important; opacity: 1 !important;"
                        >
                            <span>تسجيل الدخول</span>
                        </button>
                    </div>
                </x-filament-panels::form>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
            </div>

            <!-- Footer Links -->
            <div class="mt-10 flex flex-col items-center gap-6">
                <div class="flex items-center gap-6 text-[11px] font-bold text-gray-400">
                    <a href="https://wa.me/201065082026" target="_blank" class="hover:text-[#5B969B] transition-colors uppercase tracking-wider">المساعدة والدعم الفني</a>
                </div>
                
                <p class="text-[10px] text-gray-400">
                    &copy; {{ date('Y') }} جميع الحقوق محفوظة لعيادة د. محمد هلال
                </p>

                <div class="mt-4 flex items-center justify-center gap-1 text-[10px] text-gray-400">
                    <span>تم عمل السيستم بكل ❤️ بواسطة تيم</span>
                    <a href="https://lighthearted-alfajores-8da3c9.netlify.app" target="_blank" class="text-[#5B969B] hover:underline font-bold">CodeAi</a>
                </div>
            </div>
        </div>
    </div>

    @if (filled(config('recaptcha.api_site_key')))
        {!! htmlScriptTagJsApi() !!}
    @endif

    <style>
        .clinic-login { min-height: 100vh; }
        .clinic-login__brand { background-color: #123c64; }
        .clinic-login__logo {
            width: 176px;
            height: 176px;
            padding: 14px;
            background: #fff;
            border: 1px solid rgba(255, 255, 255, .55);
        }
        .clinic-login__logo img { display: block; width: 100%; height: 100%; object-fit: contain; }
        .clinic-login__form-panel { flex-direction: column; }
        .clinic-login__mobile-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin: 0 0 1.5rem;
        }
        .clinic-login__mobile-logo img {
            display: block;
            width: 84px;
            height: 84px;
            padding: 7px;
            object-fit: contain;
            background: #fff;
            border: 1px solid #e5edf3;
            border-radius: 1.25rem;
            box-shadow: 0 8px 24px rgba(18, 60, 100, .08);
        }
        @media (min-width: 1024px) {
            .clinic-login__mobile-logo { display: none; }
        }
        @media (max-width: 1023px) {
            .clinic-login { min-height: 100vh; overflow-y: auto; }
            .clinic-login__form-panel { min-height: 100vh; padding: 2rem 1.25rem; }
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fade-in 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        /* Custom scrollbar for form if needed */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</div>
