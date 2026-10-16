<div
    x-data="{ open: @if($isInline) true @else @entangle('isOpen').live @endif }"
    @class([
        'fixed bottom-5 right-5 z-[60] flex flex-col items-end rtl:left-5 rtl:right-auto' => !$isInline,
        'w-full h-full flex flex-col' => $isInline,
    ])
    dir="rtl"
>

    {{-- ========================================================= --}}
    {{-- GLASSMORPHIC CHAT BOX                                     --}}
    {{-- ========================================================= --}}
    <div
        x-show="open"
        x-init="$nextTick(() => { const m = document.getElementById('chat-msgs'); if(m) m.scrollTop = m.scrollHeight; })"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 scale-95"
        @class([
            'flex flex-col overflow-hidden border border-slate-200 bg-white',
            'w-full h-full' => $isInline,
        ])
        style="{{ !$isInline ? 'width:380px; max-height:600px; border-radius:1.5rem;
                   box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08), 0 10px 10px -5px rgba(0,0,0,0.04);'
                : 'border-radius: 1.5rem;
                   box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.02);' }}"
        id="chat-box-element"
    >

            {{-- ─── HEADER ─────────────────────────────────────────────── --}}
            <div class="relative px-5 py-4 flex-shrink-0 border-b border-slate-100 flex items-center justify-between"
                style="background: #ffffff;
                       border-radius: 1.5rem 1.5rem 0 0;">

                <div class="relative z-10 flex items-center gap-3">
                    {{-- Doctor mini avatar --}}
                    <div class="relative flex-shrink-0">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center overflow-hidden bg-slate-50 border border-slate-200">
                            {{-- Mini doctor SVG --}}
                            <svg viewBox="0 0 40 44" width="28" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                {{-- White coat body --}}
                                <rect x="8" y="24" width="24" height="18" rx="4" fill="white" opacity="0.95"/>
                                {{-- Coat lapels --}}
                                <path d="M20 24 L14 28 L14 36" stroke="#e2e8f0" stroke-width="1.5" fill="none"/>
                                <path d="M20 24 L26 28 L26 36" stroke="#e2e8f0" stroke-width="1.5" fill="none"/>
                                {{-- Scrubs/shirt showing --}}
                                <rect x="17" y="26" width="6" height="12" rx="1" fill="#0d9488" opacity="0.6"/>
                                {{-- Medical cross badge --}}
                                <rect x="22" y="30" width="6" height="6" rx="1.5" fill="#ef4444" opacity="0.85"/>
                                <rect x="24" y="28.5" width="2" height="9" rx="1" fill="white"/>
                                <rect x="21.5" y="32" width="7" height="2" rx="1" fill="white"/>
                                {{-- Head --}}
                                <ellipse cx="20" cy="15" rx="8" ry="9" fill="#fde68a" opacity="0.95"/>
                                {{-- Hair --}}
                                <path d="M12 13 Q12 6 20 6 Q28 6 28 13" fill="#1e293b" opacity="0.9"/>
                                {{-- Doctor mirror on head --}}
                                <circle cx="25" cy="10" r="3" fill="#cbd5e1" stroke="#94a3b8" stroke-width="0.5"/>
                                <circle cx="25" cy="10" r="1" fill="none" stroke="#64748b" stroke-width="0.5"/>
                                {{-- Eyes --}}
                                <ellipse cx="16.5" cy="15.5" rx="1.8" ry="2" fill="#1e293b" class="{{ $isLoading ? 'thinking-eye-anim' : '' }}"/>
                                <ellipse cx="23.5" cy="15.5" rx="1.8" ry="2" fill="#1e293b" class="{{ $isLoading ? 'thinking-eye-anim' : '' }}"/>
                                <circle cx="16" cy="14.8" r="0.6" fill="white"/>
                                <circle cx="23" cy="14.8" r="0.6" fill="white"/>
                                {{-- Smile --}}
                                <path d="M15.5 19.5 Q20 22 24.5 19.5" stroke="#92400e" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                                {{-- Stethoscope --}}
                                <path d="M14 26 Q11 22 12 18 Q13 16 14 18" stroke="#64748b" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                                <circle cx="12" cy="19" r="1.5" fill="#475569" stroke="#334155" stroke-width="0.5"/>
                            </svg>
                        </div>
                        {{-- Online dot --}}
                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white bg-emerald-400"></div>
                    </div>

                    {{-- Text info --}}
                    <div class="flex-1 min-w-0">
                        <div class="text-slate-800 font-extrabold text-sm leading-tight tracking-tight">المساعد الطبي الذكي</div>
                        <div class="mt-0.5 flex items-center gap-1 text-slate-400 text-[10px] font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block animate-pulse"></span>
                            @if($isLoading)
                                <span class="text-indigo-600 font-bold">جارٍ المعالجة...</span>
                            @else
                                نشط ومتاح
                            @endif
                        </div>
                    </div>
                </div>

                {{-- New conversation button --}}
                <button wire:click="startNewConversation" title="محادثة جديدة"
                    class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center transition-all duration-200 active:scale-90 bg-slate-50 border border-slate-200 hover:bg-slate-100 hover:border-slate-300">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#475569"
                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </button>
            </div>

            {{-- ─── MESSAGES ────────────────────────────────────────────── --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-4 chat-scroll bg-white" id="chat-msgs"
                style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">

                @forelse($messages as $msg)
                    <div @class([
                        'flex flex-col gap-1',
                        'items-end' => $msg['role'] === 'user',
                        'items-start' => $msg['role'] === 'assistant',
                    ])>
                        {{-- Avatar for assistant --}}
                        @if($msg['role'] === 'assistant')
                            <div class="flex items-end gap-2">
                                <div class="w-6 h-6 rounded-lg flex-shrink-0 flex items-center justify-center mb-0.5"
                                    style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                                    <svg viewBox="0 0 16 18" width="12" height="14" fill="none">
                                        <rect x="3" y="8" width="10" height="9" rx="2" fill="white" opacity="0.9"/>
                                        <ellipse cx="8" cy="5.5" rx="4" ry="4.5" fill="#fde68a"/>
                                        <path d="M4 5 Q4 1 8 1 Q12 1 12 5" fill="#1e293b" opacity="0.9"/>
                                        <ellipse cx="6.2" cy="5.8" rx="0.9" ry="1" fill="#1e293b"/>
                                        <ellipse cx="9.8" cy="5.8" rx="0.9" ry="1" fill="#1e293b"/>
                                    </svg>
                                </div>
                        @endif

                        <div @class([
                            'max-w-[82%] text-sm leading-relaxed',
                            'chat-bubble-user' => $msg['role'] === 'user',
                            'chat-bubble-ai' => $msg['role'] === 'assistant',
                        ])>
                            <div x-data="{
                                text: '',
                                fullText: {{ json_encode($msg['content'], JSON_HEX_APOS | JSON_HEX_QUOT) }},
                                index: 0,
                                speed: 10,
                                init() {
                                    @if($loop->last && $msg['role'] === 'assistant')
                                        this.typeWriter();
                                    @else
                                        this.text = this.formatText(this.fullText);
                                    @endif
                                },
                                formatText(str) {
                                    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
                                },
                                typeWriter() {
                                    if (this.index < this.fullText.length) {
                                        let chunkSize = Math.min(3, this.fullText.length - this.index);
                                        this.index += chunkSize;
                                        this.text = this.formatText(this.fullText.substring(0, this.index));
                                        
                                        setTimeout(() => this.typeWriter(), this.speed);
                                        
                                        const el = document.getElementById('chat-msgs');
                                        if (el) el.scrollTop = el.scrollHeight;
                                    }
                                }
                            }" x-html="text" class="prose prose-sm prose-p:my-1 {{ $msg['role'] === 'user' ? 'prose-invert' : 'dark:prose-invert' }}">
                            </div>

                            @if($msg['role'] === 'assistant' && !empty($msg['sources']))
                                <div class="mt-2.5 pt-2.5 flex flex-wrap gap-1.5"
                                    style="border-top: 1px solid #f1f5f9;">
                                    @foreach($msg['sources'] as $source)
                                        <span class="source-chip">🔍 {{ $source }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if($msg['role'] === 'assistant' && isset($msg['metadata']['active_model']))
                                <div class="mt-1 text-[8px] text-slate-400 font-bold text-left opacity-60">
                                    via {{ $msg['metadata']['active_model'] }}
                                </div>
                            @endif
                        </div>

                        @if($msg['role'] === 'assistant')
                            </div>
                        @endif

                        <span class="text-[9px] font-semibold px-2"
                            style="color: #94a3b8;">{{ \Carbon\Carbon::parse($msg['created_at'])->diffForHumans() }}</span>
                    </div>
                @empty
                    {{-- Welcome state --}}
                    <div class="flex flex-col items-center justify-center text-center py-10 welcome-anim">
                        <div class="w-16 h-16 rounded-3xl mb-4 flex items-center justify-center relative bg-slate-50 border border-slate-200">
                            {{-- Welcome doctor SVG --}}
                            <svg viewBox="0 0 56 62" width="40" height="44" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="10" y="34" width="36" height="26" rx="6" fill="#0d9488" opacity="0.9"/>
                                <path d="M28 34 L19 40 L19 52" stroke="#0f766e" stroke-width="2" fill="none"/>
                                <path d="M28 34 L37 40 L37 52" stroke="#0f766e" stroke-width="2" fill="none"/>
                                <rect x="24" y="36" width="8" height="16" rx="2" fill="#0d9488" opacity="0.6"/>
                                <rect x="33" y="42" width="9" height="9" rx="2" fill="#ef4444" opacity="0.85"/>
                                <rect x="36" y="40" width="3" height="13" rx="1.5" fill="white"/>
                                <rect x="32.5" y="46" width="10" height="3" rx="1.5" fill="white"/>
                                <ellipse cx="28" cy="21" rx="12" ry="13" fill="#fde68a"/>
                                <path d="M16 18 Q16 8 28 8 Q40 8 40 18" fill="#1e293b" opacity="0.9"/>
                                <circle cx="37" cy="13" r="4.5" fill="#cbd5e1" stroke="#94a3b8" stroke-width="0.8"/>
                                <circle cx="37" cy="13" r="1.5" fill="none" stroke="#64748b" stroke-width="0.8"/>
                                <ellipse cx="22" cy="21.5" rx="2.5" ry="2.8" fill="#1e293b"/>
                                <ellipse cx="34" cy="21.5" rx="2.5" ry="2.8" fill="#1e293b"/>
                                <circle cx="21" cy="20.5" r="0.9" fill="white"/>
                                <circle cx="33" cy="20.5" r="0.9" fill="white"/>
                                <path d="M22 27 Q28 30.5 34 27" stroke="#92400e" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                                <path d="M18 36 Q14 30 15 23 Q16 20 18 24" stroke="#64748b" stroke-width="2" fill="none" stroke-linecap="round"/>
                                <circle cx="14" cy="25" r="2.5" fill="#475569"/>
                            </svg>
                            <div class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full flex items-center justify-center animate-bounce bg-indigo-600">
                                <span class="text-white text-[8px]">✦</span>
                            </div>
                        </div>
                        <h4 class="font-extrabold text-sm mb-1 text-slate-800">أهلاً دكتور! 👋</h4>
                        <p class="text-xs leading-relaxed max-w-[260px] text-slate-500">
                            أنا مساعدك الطبي الذكي. يمكنني مساعدتك في الاستفسار عن المرضى، الحجوزات، والبروتوكولات العلاجية فوراً.
                        </p>
                    </div>
                @endforelse

                {{-- Typing indicator --}}
                @if($isLoading)
                    <div class="flex items-end gap-2">
                        <div class="w-6 h-6 rounded-lg flex-shrink-0 flex items-center justify-center"
                            style="background: linear-gradient(135deg, #4f46e5, #818cf8);">
                            <svg viewBox="0 0 16 18" width="12" height="14" fill="none">
                                <rect x="3" y="8" width="10" height="9" rx="2" fill="white" opacity="0.9"/>
                                <ellipse cx="8" cy="5.5" rx="4" ry="4.5" fill="#fde68a"/>
                                <path d="M4 5 Q4 1 8 1 Q12 1 12 5" fill="#1e293b" opacity="0.9"/>
                            </svg>
                        </div>
                        <div class="chat-bubble-ai py-3 px-5">
                            <div class="flex items-center gap-1.5">
                                <span class="typing-dot" style="animation-delay:0s"></span>
                                <span class="typing-dot" style="animation-delay:0.18s"></span>
                                <span class="typing-dot" style="animation-delay:0.36s"></span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ─── SUGGESTION CHIPS ────────────────────────────────────── --}}
            <div class="px-4 pt-2 pb-1 flex-shrink-0 border-t border-slate-100 bg-white">
                <div class="flex gap-2 overflow-x-auto pb-1 chips-scroll" style="scrollbar-width: none;">
                    <button wire:click="$set('message', '📋 أعطني ملخص حالة المريض: ')" class="chip">📋 ملخص مريض</button>
                    <button wire:click="$set('message', '📅 ما حجوزات العيادة اليوم؟')" class="chip">📅 حجوزات اليوم</button>
                    <button wire:click="$set('message', '🏥 بروتوكول علاج دوالي الخصية؟')" class="chip">🏥 بروتوكول علاج</button>
                    <button wire:click="$set('message', '💊 أدوية المريض النشطة: ')" class="chip">💊 الأدوية</button>
                    <button wire:click="$set('message', '📊 تقرير موجز لهذا الأسبوع')" class="chip">📊 تقرير أسبوعي</button>
                </div>
            </div>

            {{-- ─── INPUT BAR ───────────────────────────────────────────── --}}
            <div class="px-4 pb-5 pt-2 flex-shrink-0 bg-white">
                <form wire:submit.prevent="sendMessage" class="relative">
                    <input
                        type="text"
                        wire:model.live="message"
                        placeholder="اكتب سؤالك هنا..."
                        @disabled($isLoading)
                        class="w-full text-sm font-medium outline-none transition-all duration-200 bg-slate-50 border border-slate-200 text-slate-900 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        style="padding: 12px 52px 12px 16px;
                               box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);"
                        onfocus="this.style.borderColor='#6366f1'; this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.12), inset 0 1px 2px rgba(0,0,0,0.02)';"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='inset 0 1px 2px rgba(0,0,0,0.02)';">

                    {{-- Send button --}}
                    <button type="submit"
                        @disabled($isLoading || empty(trim($message)))
                        class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 active:scale-90 {{ (!empty(trim($message)) && !$isLoading) ? 'send-btn-active' : 'send-btn-inactive' }}">
                        @if($isLoading)
                            <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-dashoffset="20" stroke-linecap="round"/>
                            </svg>
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 rtl:rotate-180">
                                <path d="M3.478 2.404a.75.75 0 0 0-.926.941l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.404Z"/>
                            </svg>
                        @endif
                    </button>
                </form>

                {{-- Powered by --}}
                <div class="mt-2 flex items-center justify-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse inline-block"></span>
                    <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">مدعوم بتقنية RAG الطبية المتقدمة</p>
                </div>
            </div>
        </div>

    {{-- ========================================================= --}}
    {{-- FLOATING DOCTOR CHARACTER BUTTON                          --}}
    {{-- ========================================================= --}}
    @if(!$isInline)
        <button
            @click="open = !open"
            :class="open ? 'w-12 h-12' : 'w-[72px] h-[72px]'"
            class="group relative flex items-center justify-center rounded-full focus:outline-none transition-all duration-300 ease-in-out"
            style="background: none; border: none; margin-top: 12px;"
        >
            {{-- Outer glow ring (only if closed) --}}
            <span x-show="!open" class="absolute inset-0 rounded-full animate-sonar-aura pointer-events-none"
                style="background: radial-gradient(circle, rgba(99,102,241,0.06) 0%, transparent 70%);"></span>
            <span x-show="!open" class="absolute inset-0 rounded-full animate-sonar-aura-delayed pointer-events-none"
                style="background: radial-gradient(circle, rgba(71,85,105,0.04) 0%, transparent 70%);"></span>

            {{-- The circular platform / button base (Beautiful Premium White) --}}
            <span
                :class="open ? 'scale-100 shadow-md' : 'group-hover:scale-110 shadow-lg'"
                class="absolute inset-0 rounded-full bg-white border border-slate-200 transition-all duration-300"
                style="box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), inset 0 -4px 8px rgba(0,0,0,0.02), inset 0 2px 4px rgba(255,255,255,0.95);"
            ></span>

            {{-- Spotlight container behind the character for pristine contrast (only if closed) --}}
            <span x-show="!open" class="absolute w-13 h-13 rounded-full top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-0"
                style="background: radial-gradient(circle, #ffffff 60%, #f8fafc 100%); border: 1.5px solid #f1f5f9; box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);"></span>

            {{-- CLOSE ICON (shown when open) --}}
            <span x-show="open" class="relative z-10 flex items-center justify-center transition-all duration-300 group-hover:rotate-90">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#64748b"
                    stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                    class="w-5 h-5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </span>

            {{-- DOCTOR CHARACTER (shown when closed) --}}
            <span x-show="!open" class="dr-ai-wrap {{ $isLoading ? 'is-thinking' : 'is-idle' }} relative z-10">

                {{-- Stethoscope arc (behind head) --}}
                <span class="steth-back"></span>

                {{-- Doctor Head --}}
                <span class="dr-head">
                    {{-- Doctor Hair (Modern clean haircut) --}}
                    <span class="dr-hair"></span>

                    {{-- Doctor mirror on forehead --}}
                    <span class="dr-mirror">
                        <span class="mirror-hole"></span>
                    </span>

                    {{-- Face features --}}
                    <span class="dr-face-human">
                        {{-- Eyes --}}
                        <span class="dr-eyes-human">
                            <span class="dr-eye-human left-eye">
                                <span class="dr-shine-human"></span>
                            </span>
                            <span class="dr-eye-human right-eye">
                                <span class="dr-shine-human"></span>
                            </span>
                        </span>

                        {{-- Rosy cheeks --}}
                        <span class="dr-cheeks">
                            <span class="cheek left-cheek"></span>
                            <span class="cheek right-cheek"></span>
                        </span>

                        {{-- Human warm smile --}}
                        <span class="dr-mouth-human"></span>
                    </span>
                </span>

                {{-- White Coat Body --}}
                <span class="dr-body">
                    {{-- White coat lapels --}}
                    <span class="coat-lapel left-lapel"></span>
                    <span class="coat-lapel right-lapel"></span>
                    {{-- Tie / scrubs line --}}
                    <span class="dr-tie"></span>
                    {{-- Medical cross badge --}}
                    <span class="dr-badge">
                        <span class="badge-cross"></span>
                    </span>
                    {{-- Stethoscope chest piece --}}
                    <span class="steth-chest"></span>
                </span>

                {{-- Arms --}}
                <span class="dr-arms">
                    <span class="dr-arm left-arm">
                        <span class="arm-hand"></span>
                    </span>
                    <span class="dr-arm right-arm">
                        <span class="arm-hand"></span>
                    </span>
                </span>

                {{-- Floating sparkles --}}
                <span class="dr-sparkles">
                    <span class="spark sp1">✦</span>
                    <span class="spark sp2">·</span>
                    <span class="spark sp3">✦</span>
                </span>
            </span>

            {{-- Tooltip (only if closed) --}}
            <span x-show="!open" class="absolute bottom-full mb-3 left-0 px-3.5 py-2 text-white text-[11px] font-bold rounded-2xl
                         opacity-0 group-hover:opacity-100 transition-all duration-300
                         translate-y-2 group-hover:translate-y-0 whitespace-nowrap pointer-events-none"
                style="background: rgba(15,23,42,0.92); backdrop-filter: blur(12px);
                       border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 8px 24px rgba(0,0,0,0.15);">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping inline-block"></span>
                    المساعد الطبي الذكي · نشط
                </span>
                {{-- Arrow --}}
                <span class="absolute top-full left-6 border-4 border-transparent"
                    style="border-top-color: rgba(15,23,42,0.92);"></span>
            </span>
        </button>
    @endif

    {{-- ========================================================= --}}
    {{-- STYLES                                                    --}}
    {{-- ========================================================= --}}
    <style>
        /* ── DOCTOR CHARACTER ──────────────────────────────────── */
        .dr-ai-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 46px;
            height: 56px;
            animation: dr-bobbing 3.8s ease-in-out infinite;
            transform-origin: center bottom;
            position: relative;
        }

        /* === Head === */
        .dr-head {
            width: 30px;
            height: 32px;
            border-radius: 50% 50% 44% 44% / 55% 55% 45% 45%;
            position: relative;
            z-index: 6;
            box-shadow: 0 4px 12px rgba(71,85,105,0.15), inset 0 -2px 4px rgba(0,0,0,0.08);
            background: linear-gradient(175deg, #fef3c7 0%, #fde68a 60%, #fbbf24 100%);
            border: 1.5px solid rgba(180,120,0,0.15);
        }

        /* Doctor mirror reflector on forehead */
        .dr-mirror {
            position: absolute;
            top: 3px;
            right: -1px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 35%, #f1f5f9, #94a3b8 60%, #475569);
            border: 1.5px solid #94a3b8;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15), inset 0 1px 2px rgba(255,255,255,0.8);
            animation: mirror-glint 4s ease-in-out infinite;
            z-index: 8;
        }
        .mirror-hole {
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 3px; height: 3px;
            border-radius: 50%;
            background: #334155;
            border: 1px solid #64748b;
        }

        /* Hair styling */
        .dr-hair {
            position: absolute;
            top: -3px; left: -2px;
            width: 34px; height: 16px;
            background: #1e293b;
            border-radius: 16px 16px 4px 4px;
            z-index: 7;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        /* Sideburns */
        .dr-hair::before {
            content: '';
            position: absolute;
            bottom: -5px; left: 2px;
            width: 3px; height: 6px;
            background: #1e293b;
            border-radius: 0 0 1px 1px;
            box-shadow: 26px 0 0 #1e293b;
        }

        /* Human Face features container */
        .dr-face-human {
            position: absolute;
            top: 11px; left: 0;
            width: 100%; height: 22px;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 8;
        }

        /* Eyes styling */
        .dr-eyes-human {
            display: flex;
            gap: 7px;
            margin-top: 3px;
        }
        .dr-eye-human {
            width: 5px; height: 5px;
            background: #1e293b;
            border-radius: 50%;
            position: relative;
            animation: dr-blink 4.5s ease-in-out infinite;
            transition: all 0.25s ease;
        }
        .dr-shine-human {
            position: absolute;
            width: 1.5px; height: 1.5px;
            background: white;
            border-radius: 50%;
            top: 0.8px; right: 0.8px;
        }

        /* Thinking eyes - scanning left-right gently */
        .is-thinking .dr-eye-human {
            animation: human-thinking-eyes 1.5s ease-in-out infinite alternate;
        }
        @keyframes human-thinking-eyes {
            0% { transform: translateX(-1px); }
            100% { transform: translateX(1px); }
        }

        /* Hover eyes - happy upward curves */
        button:hover .dr-eye-human, .group:hover .dr-eye-human {
            height: 3px;
            border-radius: 3px 3px 0 0;
            background: #0f766e;
            animation: none;
            transform: translateY(-0.5px);
        }

        /* Rosy cheeks */
        .dr-cheeks {
            position: absolute;
            top: 8px;
            width: 24px;
            display: flex;
            justify-content: space-between;
            pointer-events: none;
        }
        .cheek {
            width: 4px; height: 2px;
            background: #f43f5e;
            opacity: 0.35;
            border-radius: 50%;
            filter: blur(0.5px);
            transition: all 0.3s ease;
        }
        button:hover .cheek, .group:hover .cheek {
            opacity: 0.6;
            transform: scale(1.3);
        }

        /* Human mouth / smile */
        .dr-mouth-human {
            width: 8px; height: 4px;
            border-bottom: 2px solid #92400e;
            border-radius: 0 0 8px 8px;
            margin-top: 3px;
            transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        
        /* Happy hover smile */
        button:hover .dr-mouth-human, .group:hover .dr-mouth-human {
            width: 10px; height: 5px;
            border-bottom: 2.5px solid #92400e;
            border-radius: 0 0 10px 10px;
            transform: translateY(0.5px);
        }

        /* Thinking mouth - soft whistle/speaking circle */
        .is-thinking .dr-mouth-human {
            width: 4px; height: 4px;
            border: 2px solid #92400e;
            border-radius: 50%;
            margin-top: 3px;
            animation: human-thinking-mouth 0.4s ease-in-out infinite alternate;
        }
        @keyframes human-thinking-mouth {
            0% { transform: scale(0.9); }
            100% { transform: scale(1.1); }
        }

        /* === Stethoscope behind head === */
        .steth-back {
            position: absolute;
            top: 12px; left: 50%;
            transform: translateX(-50%);
            width: 34px; height: 18px;
            border: 2px solid #64748b;
            border-bottom: none;
            border-radius: 16px 16px 0 0;
            z-index: 5;
        }
        .steth-back::before {
            content: '';
            position: absolute;
            top: -3px; left: 2px;
            width: 4px; height: 4px;
            border-radius: 50%;
            background: #94a3b8;
            box-shadow: 22px 0 0 #94a3b8;
        }

        /* === Body (White Coat) === */
        .dr-body {
            width: 26px;
            height: 20px;
            background: linear-gradient(175deg, #ffffff 0%, #f1f5f9 100%);
            border-radius: 4px 4px 8px 8px;
            border: 1.5px solid rgba(100,116,139,0.25);
            border-top: none;
            position: relative;
            z-index: 4;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            box-shadow: 0 4px 10px rgba(0,0,0,0.06), inset 0 1px 2px rgba(255,255,255,0.8);
        }

        /* Lapels */
        .coat-lapel {
            position: absolute;
            top: 0; width: 10px; height: 13px;
            background: white;
            border: 1px solid rgba(100,116,139,0.15);
        }
        .left-lapel { left: 0; border-radius: 0 0 4px 0; clip-path: polygon(0 0, 100% 0, 40% 100%, 0 100%); }
        .right-lapel { right: 0; border-radius: 0 0 0 4px; clip-path: polygon(0 0, 100% 0, 100% 100%, 60% 100%); }

        /* Scrubs / shirt between lapels */
        .dr-tie {
            position: absolute;
            top: 0; left: 50%; transform: translateX(-50%);
            width: 6px; height: 14px;
            background: linear-gradient(180deg, #0d9488, #0f766e);
            border-radius: 0 0 3px 3px;
            box-shadow: 0 0 4px rgba(13,148,136,0.3);
        }

        /* Medical badge */
        .dr-badge {
            position: absolute;
            top: 3px; right: 2px;
            width: 7px; height: 7px;
            border-radius: 2px;
            background: #ef4444;
            box-shadow: 0 0 4px rgba(239,68,68,0.3);
            display: flex; align-items: center; justify-content: center;
        }
        .badge-cross::before, .badge-cross::after {
            content: '';
            position: absolute;
            background: white;
            border-radius: 0.5px;
        }
        .badge-cross::before { width: 1px; height: 5px; top: 1px; left: 3px; }
        .badge-cross::after  { width: 5px; height: 1px; top: 3px; left: 1px; }

        /* Stethoscope chest piece */
        .steth-chest {
            position: absolute;
            bottom: 3px; left: 3px;
            width: 5px; height: 5px;
            border-radius: 50%;
            background: radial-gradient(circle, #94a3b8, #475569);
            border: 1px solid #334155;
            box-shadow: 0 0 4px rgba(0,0,0,0.15);
        }

        /* === Arms === */
        .dr-arms {
            position: absolute;
            width: 38px;
            display: flex;
            justify-content: space-between;
            top: 38px; z-index: 3;
        }
        .dr-arm {
            width: 7px; height: 14px;
            background: linear-gradient(180deg, #f1f5f9, #e2e8f0);
            border: 1.5px solid rgba(100,116,139,0.2);
            border-radius: 3px;
            position: relative;
            animation: arm-sway 3s ease-in-out infinite alternate;
        }
        .right-arm { animation-delay: 0.8s; }
        .arm-hand {
            position: absolute;
            bottom: -4px; left: 50%;
            transform: translateX(-50%);
            width: 8px; height: 5px;
            background: #fde68a;
            border-radius: 2px;
            border: 1px solid rgba(180,120,0,0.1);
        }

        /* === Sparkles === */
        .dr-sparkles .spark {
            position: absolute;
            font-size: 7px;
            color: #6366f1;
            opacity: 0;
        }
        .sp1 { top: -4px; right: -6px; animation: spark-float 3s infinite 0.3s; }
        .sp2 { top: 20px; left: -9px; animation: spark-float 3s infinite 1.1s; font-size: 5px; color: #10b981; }
        .sp3 { bottom: 6px; right: -7px; animation: spark-float 3s infinite 2s; }

        /* Hover full tilt effect */
        button:hover .dr-ai-wrap, .group:hover .dr-ai-wrap {
            animation: dr-bobbing-hover 1.4s ease-in-out infinite;
        }
        button:hover .dr-head, .group:hover .dr-head {
            transform: rotate(-6deg) scale(1.05);
            transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        button:hover .dr-mirror, .group:hover .dr-mirror {
            box-shadow: 0 0 10px rgba(255,255,255,0.8), 0 2px 6px rgba(0,0,0,0.15);
        }

        /* ── KEYFRAMES ─────────────────────────────────────────── */
        @keyframes dr-bobbing {
            0%,100% { transform: translateY(0) rotate(0deg); }
            30% { transform: translateY(-6px) rotate(1deg); }
            70% { transform: translateY(-3px) rotate(-1deg); }
        }
        @keyframes dr-bobbing-hover {
            0%,100% { transform: translateY(0) rotate(-2deg); }
            50% { transform: translateY(-9px) rotate(2deg); }
        }
        @keyframes dr-blink {
            0%,88%,100% { transform: scaleY(1); }
            92% { transform: scaleY(0.08); }
        }
        @keyframes thinking-scan {
            0% { transform: translateX(-1px); background: #6366f1; }
            100% { transform: translateX(1px); background: #818cf8; box-shadow: 0 0 8px #818cf8; }
        }
        @keyframes wave-pulse {
            0% { height: 1px; }
            100% { height: 5px; }
        }
        @keyframes arm-sway {
            0% { transform: rotate(-6deg) translateY(0); }
            100% { transform: rotate(6deg) translateY(2px); }
        }
        @keyframes mirror-glint {
            0%,100% { box-shadow: 0 2px 6px rgba(0,0,0,0.15), inset 0 1px 2px rgba(255,255,255,0.8); }
            50% { box-shadow: 0 2px 10px rgba(0,0,0,0.15), inset 0 1px 4px rgba(255,255,255,1), 0 0 8px rgba(255,255,255,0.5); }
        }
        @keyframes spark-float {
            0% { transform: scale(0) translateY(0); opacity: 0; }
            40% { opacity: 0.9; }
            100% { transform: scale(1.2) translateY(-8px); opacity: 0; }
        }

        /* ── SONAR RING ─────────────────────────────────────────── */
        @keyframes sonar-aura {
            0% { transform: scale(0.9); opacity: 0.7; }
            100% { transform: scale(1.65); opacity: 0; }
        }
        @keyframes sonar-aura-delayed {
            0% { transform: scale(0.9); opacity: 0.5; }
            100% { transform: scale(2); opacity: 0; }
        }
        .animate-sonar-aura { animation: sonar-aura 2.6s cubic-bezier(0.1,0.8,0.3,1) infinite; }
        .animate-sonar-aura-delayed { animation: sonar-aura-delayed 2.6s cubic-bezier(0.1,0.8,0.3,1) infinite 1.3s; }

        /* ── CHAT BOX ENTRY ─────────────────────────────────────── */
        @keyframes chat-in {
            0% { transform: translateY(18px) scale(0.96); opacity: 0; }
            100% { transform: translateY(0) scale(1); opacity: 1; }
        }
        .animate-chat-in { animation: chat-in 0.38s cubic-bezier(0.16,1,0.3,1) forwards; }

        /* ── CHAT BUBBLES ───────────────────────────────────────── */
        .chat-bubble-user {
            padding: 10px 16px;
            border-radius: 1.25rem 1.25rem 0.25rem 1.25rem;
            background: #f1f5f9;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            line-height: 1.55;
            font-weight: 500;
        }
        .chat-bubble-ai {
            padding: 10px 16px;
            border-radius: 1.25rem 1.25rem 1.25rem 0.25rem;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            font-size: 13px;
            line-height: 1.55;
        }

        /* ── SOURCE CHIPS ───────────────────────────────────────── */
        .source-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            padding: 2px 9px;
            border-radius: 9999px;
            font-size: 9px;
            font-weight: 700;
            color: #4f46e5;
            background: rgba(79, 70, 229, 0.06);
            border: 1px solid rgba(79, 70, 229, 0.15);
            transition: all 0.2s;
        }
        .source-chip:hover { background: rgba(79, 70, 229, 0.12); }

        /* ── SUGGESTION CHIPS ───────────────────────────────────── */
        .chip {
            flex-shrink: 0;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            white-space: nowrap;
            cursor: pointer;
        }
        .chip:hover {
            background: #e2e8f0;
            color: #0f172a;
            border-color: #cbd5e1;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .chip:active { transform: scale(0.97); }
        .chips-scroll::-webkit-scrollbar { display: none; }

        /* ── TYPING DOTS ────────────────────────────────────────── */
        .typing-dot {
            display: inline-block;
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #cbd5e1;
            animation: typing-bounce 1.1s ease-in-out infinite;
        }
        @keyframes typing-bounce {
            0%,60%,100% { transform: translateY(0); background: #e2e8f0; }
            30% { transform: translateY(-6px); background: #6366f1; }
        }

        /* ── SEND BUTTON STATES ─────────────────────────────────── */
        .send-btn-active {
            background: #4f46e5;
            color: white;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.2);
        }
        .send-btn-active:hover {
            background: #4338ca;
            box-shadow: 0 4px 8px rgba(79, 70, 229, 0.3);
            transform: translateY(-50%) scale(1.05);
        }
        .send-btn-inactive {
            background: #f1f5f9;
            color: #94a3b8;
            cursor: not-allowed;
        }

        /* ── SCROLLBAR ──────────────────────────────────────────── */
        .chat-scroll::-webkit-scrollbar { width: 4px; }
        .chat-scroll::-webkit-scrollbar-track { background: transparent; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        /* ── WELCOME ANIMATION ──────────────────────────────────── */
        .welcome-anim { animation: fade-up 0.5s ease-out; }
        @keyframes fade-up {
            0% { opacity: 0; transform: translateY(12px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* ── REDUCED MOTION ─────────────────────────────────────── */
        @media (prefers-reduced-motion: reduce) {
            .dr-ai-wrap, .dr-eye, .dr-mirror, .typing-dot,
            .animate-sonar-aura, .animate-sonar-aura-delayed { animation-duration: 0.01ms !important; }
        }
    </style>

    <script>
        document.addEventListener('livewire:initialized', () => {
            // Auto scroll to bottom
            const scrollToBottom = () => {
                setTimeout(() => {
                    const el = document.getElementById('chat-msgs');
                    if (el) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                }, 50);
            };

            // Listen for user message added to UI
            @this.on('message-added', (event) => {
                scrollToBottom();

                // Trigger the actual AI processing in a separate request
                @this.dispatch('process-ai', { userMessageText: event.text });
            });

            // Listen for assistant response finished
            @this.on('scroll-to-bottom', () => {
                scrollToBottom();
            });
        });
    </script>

</div>
