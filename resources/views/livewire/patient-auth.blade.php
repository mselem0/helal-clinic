<div class="min-h-screen flex items-center justify-center p-4 gradient-bg">
    <div class="w-full max-w-md bg-white rounded-[2.5rem] shadow-2xl overflow-hidden">
        <div class="p-8 sm:p-12">
            <div class="text-center mb-10">
                <a href="/">
                    <img src="{{ asset('logo.png') }}" alt="Dr. Mohamed Ragab" class="h-16 mx-auto mb-6">
                </a>
                <h2 class="text-3xl font-bold text-navy">
                    {{ $type === 'signup' ? 'إنشاء حساب جديد' : 'تسجيل الدخول' }}
                </h2>
                <p class="text-gray-500 mt-2">
                    {{ $type === 'signup' ? 'انضم إلينا لمتابعة حجوزاتك ومحادثاتك' : 'مرحباً بك مجدداً في عيادة د. محمد هلال' }}
                </p>
            </div>

            <form wire:submit.prevent="{{ $type }}">
                @if($type === 'signup')
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700 mb-2 mr-1">الاسم الكامل</label>
                        <input type="text" wire:model.blur="name" class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-transparent focus:bg-white focus:border-cyan-brand outline-none transition-all" placeholder="أدخل اسمك بالكامل">
                        @error('name') <span class="text-red-600 text-xs font-bold mt-2 block mr-1 bg-red-50 p-2 rounded-lg border border-red-100">{{ $message }}</span> @enderror
                    </div>
                @endif

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2 mr-1">رقم الواتساب</label>
                    <div class="relative">
                        <input type="text" wire:model.blur="phone" class="w-full pl-5 pr-12 py-4 rounded-2xl bg-gray-50 border border-transparent focus:bg-white focus:border-cyan-brand outline-none transition-all text-left dir-ltr" placeholder="01XXXXXXXXX">
                        <span class="absolute right-5 top-1/2 -translate-y-1/2 text-xl select-none">🇪🇬</span>
                    </div>
                    @error('phone') <span class="text-red-600 text-xs font-bold mt-2 block mr-1 bg-red-50 p-2 rounded-lg border border-red-100">{{ $message }}</span> @enderror
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2 mr-1">كلمة المرور</label>
                    <input type="password" wire:model.blur="password" class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-transparent focus:bg-white focus:border-cyan-brand outline-none transition-all" placeholder="********">
                    @error('password') <span class="text-red-600 text-xs font-bold mt-2 block mr-1 bg-red-50 p-2 rounded-lg border border-red-100">{{ $message }}</span> @enderror
                </div>

                @if($type === 'signup')
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-2 mr-1">تأكيد كلمة المرور</label>
                        <input type="password" wire:model.blur="password_confirmation" class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-transparent focus:bg-white focus:border-cyan-brand outline-none transition-all" placeholder="********">
                        @error('password_confirmation') <span class="text-red-600 text-xs font-bold mt-2 block mr-1 bg-red-50 p-2 rounded-lg border border-red-100">{{ $message }}</span> @enderror
                    </div>
                @endif

                <button type="button" wire:click="{{ $type }}" wire:loading.attr="disabled" class="w-full bg-cyan-brand hover:bg-[#0096B4] text-white py-4 rounded-2xl font-bold shadow-lg shadow-cyan-500/30 transition-all transform hover:-translate-y-1 mb-6 flex items-center justify-center gap-2">
                    <span wire:loading.remove>{{ $type === 'signup' ? 'إنشاء الحساب' : 'تسجيل الدخول' }}</span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        جاري التحميل...
                    </span>
                </button>

                <div class="text-center">
                    <button type="button" wire:click="toggleType" class="text-navy font-bold hover:text-cyan-brand transition-colors">
                        {{ $type === 'signup' ? 'لديك حساب بالفعل؟ سجل دخولك' : 'ليس لديك حساب؟ أنشئ حساباً جديداً' }}
                    </button>
                </div>
            </form>
        </div>
        
        <div class="bg-gray-50 p-6 text-center border-t border-gray-100">
            <a href="/" class="text-sm text-gray-500 hover:text-navy transition-colors">العودة للرئيسية</a>
        </div>
    </div>
</div>
