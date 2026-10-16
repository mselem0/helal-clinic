<x-filament-panels::page>
    <div class="space-y-6 text-right" dir="rtl">
        <!-- Top Actions Bar -->
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 pb-4">
            <div>
                <a href="{{ \Modules\PatientCore\Filament\Resources\PatientResource::getUrl('index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-[#00B4D8] transition-colors">
                    <span class="ml-1">&larr;</span> العودة لقائمة المرضى
                </a>
            </div>
            <div class="flex items-center gap-3">
                <button wire:click="openNewVisitModal" class="inline-flex items-center gap-2 px-4 py-2 bg-[#002855] text-white rounded-lg shadow hover:bg-[#003f8a] transition-all font-semibold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    تسجيل زيارة جديدة
                </button>
                <a href="https://wa.me/{{ \App\Helpers\SiteSettingHelper::formatWhatsappNumber($record->phone) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg shadow hover:bg-emerald-700 transition-all font-semibold text-sm">
                    💬 واتساب المريض
                </a>
            </div>
        </div>

        <!-- Quick Info Bar (بطاقة المريض السريعة) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-150 overflow-hidden">
            <div class="p-6 bg-gradient-to-r from-[#002855] to-[#003f8a] text-white">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                    <div class="col-span-1 flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-white/10 flex items-center justify-center text-2xl font-bold text-[#00B4D8] border-2 border-white/20">
                            {{ mb_substr($record->name, 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">{{ $record->name }}</h2>
                            <p class="text-sm text-gray-200 mt-1" dir="ltr">{{ $record->phone }}</p>
                        </div>
                    </div>
                    
                    <div class="col-span-3 grid grid-cols-2 sm:grid-cols-4 gap-4 text-center md:text-right border-t md:border-t-0 md:border-r border-white/10 pt-4 md:pt-0 md:pr-6">
                        <div>
                            <span class="block text-xs text-gray-300">العمر</span>
                            <span class="text-lg font-bold text-[#00B4D8]">{{ $record->age ? "{$record->age} سنة" : 'غير محدد' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-300">الجنس</span>
                            <span class="text-lg font-bold">{{ $record->gender == 'male' ? 'ذكر' : ($record->gender == 'female' ? 'أنثى' : 'غير محدد') }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-300">فصيلة الدم</span>
                            <span class="text-lg font-bold text-red-400">{{ $record->blood_type ?? 'غير محدد' }}</span>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-300">إجمالي الزيارات</span>
                            <span class="text-lg font-bold text-emerald-400">{{ $record->visitRecords->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Red Allergy Alert Bar (شريط التنبيه الأحمر) -->
            @php
                $allergies = $record->medicalHistoryItems->where('type', 'allergy')->where('is_active', true);
            @endphp
            @if($allergies->isNotEmpty())
                <div class="px-6 py-3 bg-rose-50 border-t border-rose-100 flex items-center gap-3 text-rose-800">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <span class="font-bold text-sm">تنبيه حساسية نشط:</span>
                        <span class="text-sm">
                            @foreach($allergies as $allergy)
                                <span class="inline-block bg-rose-100 px-2 py-0.5 rounded text-xs font-semibold text-rose-900 ml-1">{{ $allergy->title }} ({{ $allergy->severity == 'severe' ? 'شديدة' : ($allergy->severity == 'moderate' ? 'متوسطة' : 'خفيفة') }})</span>
                            @endforeach
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Custom Page Tabs -->
        <div class="flex border-b border-gray-200 gap-1 bg-white p-1 rounded-xl shadow-sm border border-gray-150">
            <button wire:click="setActiveTab('summary')" class="flex-1 py-3 text-sm font-semibold rounded-lg transition-all {{ $activeTab == 'summary' ? 'bg-[#002855] text-white' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                📋 ملخص الحالة
            </button>
            <button wire:click="setActiveTab('timeline')" class="flex-1 py-3 text-sm font-semibold rounded-lg transition-all {{ $activeTab == 'timeline' ? 'bg-[#002855] text-white' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                🕐 سجل الزيارات
            </button>
            <button wire:click="setActiveTab('history')" class="flex-1 py-3 text-sm font-semibold rounded-lg transition-all {{ $activeTab == 'history' ? 'bg-[#002855] text-white' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                🏥 التاريخ المرضي
            </button>
            <button wire:click="setActiveTab('medications')" class="flex-1 py-3 text-sm font-semibold rounded-lg transition-all {{ $activeTab == 'medications' ? 'bg-[#002855] text-white' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                💊 الأدوية والوصفات
            </button>
        </div>

        <!-- TAB CONTENT PANELS -->

        <!-- TAB 1: SUMMARY -->
        @if($activeTab == 'summary')
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left (60%): Visits & Timeline Summary -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Last Visit Box -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-150">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                            <h3 class="text-base font-bold text-[#002855]">آخر كشف وزيارة للمريض</h3>
                            @if($record->last_visit)
                                <span class="text-xs bg-cyan-50 text-[#00B4D8] px-3 py-1 rounded-full font-bold">
                                    {{ $record->last_visit->visit_date->format('Y-m-d') }} ({{ $record->last_visit->visit_date->diffForHumans() }})
                                </span>
                            @endif
                        </div>
                        @if($record->last_visit)
                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-400">الشكوى الرئيسية</h4>
                                    <p class="text-sm font-medium text-gray-800 mt-1 whitespace-pre-line">{{ $record->last_visit->chief_complaint }}</p>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="text-xs font-semibold text-gray-400">التشخيص</h4>
                                        <p class="text-sm font-medium text-gray-800 mt-1 whitespace-pre-line">{{ $record->last_visit->diagnosis ?: 'غير محدد' }}</p>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-semibold text-gray-400">العلاج والخطة العلاجية</h4>
                                        <p class="text-sm font-medium text-gray-800 mt-1 whitespace-pre-line">{{ $record->last_visit->treatment_plan ?: 'غير محدد' }}</p>
                                    </div>
                                </div>
                                @if($record->last_visit->follow_up_date)
                                    <div class="flex items-center gap-2 text-xs bg-amber-50 text-amber-800 p-2.5 rounded-lg border border-amber-100 font-semibold w-fit">
                                        📅 موعد المتابعة القادم: {{ $record->last_visit->follow_up_date->format('Y-m-d') }}
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-6 text-gray-400 text-sm">
                                لم يتم تسجيل أي كشوفات أو زيارات بعد لهذا المريض.
                            </div>
                        @endif
                    </div>

                    <!-- Timeline Overview -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-150">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                            <h3 class="text-base font-bold text-[#002855]">الخط الزمني المختصر للزيارات</h3>
                            <button wire:click="setActiveTab('timeline')" class="text-xs font-bold text-[#00B4D8] hover:underline">عرض التفاصيل الكاملة &rarr;</button>
                        </div>
                        @if($record->visitRecords->isNotEmpty())
                            <div class="space-y-4 relative border-r-2 border-gray-100 pr-4">
                                @foreach($record->visitRecords()->latest('visit_date')->take(4)->get() as $visit)
                                    <div class="relative">
                                        <div class="absolute -right-[21px] top-1 w-2.5 h-2.5 rounded-full bg-[#00B4D8]"></div>
                                        <div class="text-xs font-semibold text-gray-400">{{ $visit->visit_date->format('Y-m-d') }}</div>
                                        <div class="text-sm font-bold text-gray-800 mt-0.5">{{ $visit->diagnosis ?: 'زيارة / استشارة' }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ $visit->chief_complaint }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-400 text-sm">لا يوجد سجل زيارات بعد.</div>
                        @endif
                    </div>
                </div>

                <!-- Right (40%): Chronic Conditions & Quick Metrics -->
                <div class="space-y-6">
                    <!-- Chronic Diseases Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-150">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-3">
                            <h3 class="text-base font-bold text-[#002855]">الأمراض المزمنة</h3>
                            <button wire:click="openNewHistoryModal('chronic_disease')" class="text-xs font-bold text-[#00B4D8] hover:underline">+ إضافة</button>
                        </div>
                        @php
                            $chronics = $record->medicalHistoryItems->where('type', 'chronic_disease')->where('is_active', true);
                        @endphp
                        @if($chronics->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($chronics as $item)
                                    <div class="flex items-center justify-between p-2.5 bg-rose-50/50 rounded-lg border border-rose-100">
                                        <div>
                                            <span class="text-sm font-bold text-gray-800">{{ $item->title }}</span>
                                            @if($item->diagnosed_at)
                                                <span class="text-[10px] text-gray-400 block mt-0.5">منذ: {{ $item->diagnosed_at->format('Y') }}</span>
                                            @endif
                                        </div>
                                        <span class="text-xs bg-rose-100 text-rose-800 px-2 py-0.5 rounded font-bold">
                                            {{ $item->severity == 'severe' ? 'شديدة' : ($item->severity == 'moderate' ? 'متوسطة' : 'خفيفة') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-400 text-sm">خالٍ من الأمراض المزمنة المسجلة.</div>
                        @endif
                    </div>

                    <!-- Current Active Medications -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-150">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-3">
                            <h3 class="text-base font-bold text-[#002855]">الأدوية اليومية الحالية</h3>
                            <button wire:click="setActiveTab('medications')" class="text-xs font-bold text-[#00B4D8] hover:underline">إدارة الأدوية &rarr;</button>
                        </div>
                        @php
                            $activeMeds = $record->patientMedications->where('is_active', true);
                        @endphp
                        @if($activeMeds->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($activeMeds as $med)
                                    <div class="p-2.5 bg-emerald-50/30 rounded-lg border border-emerald-100 flex items-center justify-between">
                                        <div>
                                            <span class="text-sm font-bold text-gray-800">{{ $med->medication_name }}</span>
                                            <span class="text-xs text-gray-500 block mt-0.5">{{ $med->dosage }} - {{ $med->frequency }}</span>
                                        </div>
                                        <span class="text-xs text-emerald-800 font-semibold bg-emerald-100 px-2 py-0.5 rounded">مستمر</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-400 text-sm">لا يتناول أدوية مستمرة مسجلة.</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 2: TIMELINE (DETAILED VISIT LOG) -->
        @if($activeTab == 'timeline')
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-150">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-6">
                    <h3 class="text-base font-bold text-[#002855]">سجل الزيارات والتشخيصات المفصل</h3>
                    <button wire:click="openNewVisitModal" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-[#002855] text-white rounded-lg shadow hover:bg-[#003f8a] transition-all font-semibold text-xs">
                        + كشف جديد للمريض
                    </button>
                </div>

                @if($record->visitRecords->isNotEmpty())
                    <div class="relative border-r-2 border-gray-200 pr-6 space-y-8">
                        @foreach($record->visitRecords()->latest('visit_date')->get() as $visit)
                            <div class="relative bg-gray-50/50 p-5 rounded-2xl border border-gray-100">
                                <!-- Marker -->
                                <div class="absolute -right-[32px] top-6 w-3.5 h-3.5 rounded-full bg-[#00B4D8] border-2 border-white shadow"></div>
                                
                                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-3 mb-3">
                                    <div>
                                        <span class="text-xs bg-cyan-100 text-[#002855] px-2.5 py-0.5 rounded-full font-bold">{{ $visit->visit_date->format('Y-m-d') }}</span>
                                        <span class="text-xs text-gray-400 mr-2">{{ $visit->visit_date->diffForHumans() }}</span>
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="openEditVisitModal({{ $visit->id }})" class="text-xs font-semibold text-cyan-600 hover:text-cyan-800 bg-cyan-50 px-2.5 py-1 rounded">✏ تعديل</button>
                                        <button onclick="confirm('هل أنت متأكد من حذف هذه الزيارة بالكامل؟') || event.stopImmediatePropagation()" wire:click="deleteVisit({{ $visit->id }})" class="text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 px-2.5 py-1 rounded">🗑 حذف</button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <div>
                                            <span class="text-xs text-gray-400 font-bold block">الشكوى الرئيسية</span>
                                            <p class="text-sm text-gray-800 whitespace-pre-line">{{ $visit->chief_complaint }}</p>
                                        </div>
                                        @if($visit->examination_notes)
                                            <div>
                                                <span class="text-xs text-gray-400 font-bold block">ملاحظات الفحص السريري</span>
                                                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $visit->examination_notes }}</p>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="space-y-2 border-t md:border-t-0 md:border-r border-gray-100 pt-3 md:pt-0 md:pr-4">
                                        <div>
                                            <span class="text-xs text-gray-400 font-bold block">التشخيص الطبي</span>
                                            <p class="text-sm font-bold text-gray-800">{{ $visit->diagnosis ?: 'غير محدد' }}</p>
                                        </div>
                                        @if($visit->treatment_plan)
                                            <div>
                                                <span class="text-xs text-gray-400 font-bold block">خطة العلاج والمتابعة</span>
                                                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $visit->treatment_plan }}</p>
                                            </div>
                                        @endif
                                        @if($visit->follow_up_date)
                                            <span class="inline-block mt-2 text-xs bg-amber-50 text-amber-800 border border-amber-100 px-2 py-0.5 rounded font-bold">🔔 موعد المتابعة: {{ $visit->follow_up_date->format('Y-m-d') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Visit Prescriptions -->
                                @php
                                    $prescription = $visit->prescriptions->first();
                                @endphp
                                @if($prescription && $prescription->items->isNotEmpty())
                                    <div class="mt-4 border-t border-gray-150 pt-3">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-bold text-gray-400">💊 الروشتة الطبية الموصوفة</span>
                                            <button onclick="printPrescription({{ $prescription->id }})" class="text-xs text-[#00B4D8] hover:underline font-bold">🖨 طباعة الروشتة</button>
                                        </div>
                                        <div class="bg-white p-3 rounded-xl border border-gray-100 text-xs">
                                            <table class="w-full text-right border-collapse">
                                                <thead>
                                                    <tr class="text-gray-400 border-b border-gray-100">
                                                        <th class="pb-1.5 font-bold">اسم الدواء</th>
                                                        <th class="pb-1.5 font-bold">الجرعة</th>
                                                        <th class="pb-1.5 font-bold">التكرار</th>
                                                        <th class="pb-1.5 font-bold">المدة</th>
                                                        <th class="pb-1.5 font-bold">تعليمات</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($prescription->items as $item)
                                                        <tr class="border-b border-gray-50 last:border-0 text-gray-700">
                                                            <td class="py-2 font-bold">{{ $item->medication_name }}</td>
                                                            <td class="py-2">{{ $item->dosage ?: '-' }}</td>
                                                            <td class="py-2">{{ $item->frequency ?: '-' }}</td>
                                                            <td class="py-2">{{ $item->duration ?: '-' }}</td>
                                                            <td class="py-2 text-gray-400">{{ $item->instructions ?: '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                            @if($prescription->notes)
                                                <div class="mt-2 text-xs text-gray-400 border-t border-gray-50 pt-2">
                                                    <strong>ملاحظات الروشتة:</strong> {{ $prescription->notes }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12 text-gray-400 text-sm">
                        لم يتم تسجيل زيارات أو كشوفات للمريض بعد. ابدأ بتسجيل أول زيارة الآن!
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 3: MEDICAL HISTORY -->
        @if($activeTab == 'history')
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-150 space-y-6">
                @php
                    $historyGroups = [
                        'chronic_disease' => ['title' => 'الأمراض المزمنة والمستمرة', 'icon' => '🔴', 'color' => 'rose'],
                        'allergy' => ['title' => 'الحساسيات والتحذيرات', 'icon' => '⚠️', 'color' => 'amber'],
                        'surgery' => ['title' => 'العمليات الجراحية السابقة', 'icon' => '🔵', 'color' => 'cyan'],
                        'family_history' => ['title' => 'التاريخ العائلي الوراثي', 'icon' => '🟠', 'color' => 'orange'],
                        'other' => ['title' => 'ملاحظات وتاريخ مرضي آخر', 'icon' => '⬛', 'color' => 'gray'],
                    ];
                @endphp

                @foreach($historyGroups as $type => $meta)
                    <div class="border border-gray-150 rounded-xl overflow-hidden shadow-sm">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                            <h4 class="text-sm font-bold text-gray-700 flex items-center gap-2">
                                <span>{{ $meta['icon'] }}</span>
                                <span>{{ $meta['title'] }}</span>
                            </h4>
                            <button wire:click="openNewHistoryModal('{{ $type }}')" class="text-xs bg-[#002855] text-white px-2.5 py-1 rounded hover:bg-[#003f8a] transition-all font-semibold shadow-sm">
                                + إضافة
                            </button>
                        </div>
                        <div class="p-4 bg-white">
                            @php
                                $items = $record->medicalHistoryItems->where('type', $type);
                            @endphp
                            @if($items->isNotEmpty())
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($items as $item)
                                        <div class="p-3 bg-gray-50/50 border border-gray-100 rounded-lg flex items-center justify-between">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-gray-800">{{ $item->title }}</span>
                                                    @if($type == 'chronic_disease' || $type == 'allergy')
                                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded {{ $item->severity == 'severe' ? 'bg-red-100 text-red-800' : ($item->severity == 'moderate' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800') }}">
                                                            {{ $item->severity == 'severe' ? 'شديدة' : ($item->severity == 'moderate' ? 'متوسطة' : 'خفيفة') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($item->description)
                                                    <p class="text-xs text-gray-500 mt-1">{{ $item->description }}</p>
                                                @endif
                                                @if($item->diagnosed_at)
                                                    <span class="text-[10px] text-gray-400 block mt-1">تاريخ التشخيص: {{ $item->diagnosed_at->format('Y-m-d') }}</span>
                                                @endif
                                            </div>
                                            <div class="flex gap-2">
                                                <button wire:click="openEditHistoryModal({{ $item->id }})" class="text-xs font-semibold text-cyan-600 hover:text-cyan-800 bg-cyan-50 px-2 py-1 rounded">✏</button>
                                                <button onclick="confirm('هل تريد الحذف؟') || event.stopImmediatePropagation()" wire:click="deleteHistory({{ $item->id }})" class="text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 px-2 py-1 rounded">🗑</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-gray-400 text-xs">
                                    لا توجد سجلات مسجلة ضمن هذا القسم بعد.
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- TAB 4: MEDICATIONS -->
        @if($activeTab == 'medications')
            <div class="space-y-6">
                <!-- Chronic / Current Medications -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-150">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                        <h3 class="text-base font-bold text-[#002855]">الأدوية اليومية المستمرة (المزمنة)</h3>
                        <button wire:click="openNewMedicationModal" class="inline-flex items-center gap-1 px-3 py-1.5 bg-[#002855] text-white rounded-lg hover:bg-[#003f8a] transition-all font-semibold text-xs">
                            + إضافة دواء مستمر
                        </button>
                    </div>

                    @php
                        $meds = $record->patientMedications;
                    @endphp
                    @if($meds->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($meds as $med)
                                <div class="p-4 border rounded-xl flex items-center justify-between shadow-sm {{ $med->is_active ? 'bg-emerald-50/20 border-emerald-100' : 'bg-gray-50/50 border-gray-150 opacity-70' }}">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-gray-800">{{ $med->medication_name }}</span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded font-bold {{ $med->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600' }}">
                                                {{ $med->is_active ? 'نشط ومستمر' : 'موقوف' }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">{{ $med->dosage }} - {{ $med->frequency }}</p>
                                        @if($med->start_date)
                                            <span class="text-[10px] text-gray-400 block mt-1">منذ: {{ $med->start_date->format('Y-m-d') }}</span>
                                        @endif
                                        @if($med->prescribed_by)
                                            <span class="text-[10px] text-gray-400 block mt-0.5">بواسطة: {{ $med->prescribed_by }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button wire:click="toggleMedicationStatus({{ $med->id }})" class="text-xs px-2.5 py-1 rounded text-white font-semibold {{ $med->is_active ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                            {{ $med->is_active ? 'إيقاف مؤقت' : 'تفعيل' }}
                                        </button>
                                        <button wire:click="openEditMedicationModal({{ $med->id }})" class="text-xs bg-cyan-50 text-cyan-600 px-2 py-1 rounded">✏</button>
                                        <button onclick="confirm('هل تريد حذف الدواء؟') || event.stopImmediatePropagation()" wire:click="deleteMedication({{ $med->id }})" class="text-xs bg-rose-50 text-rose-600 px-2 py-1 rounded">🗑</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-400 text-sm">لم يتم تسجيل أي أدوية مستمرة بعد لهذا المريض.</div>
                    @endif
                </div>

                <!-- Past Prescriptions History -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-150">
                    <div class="border-b border-gray-100 pb-3 mb-4">
                        <h3 class="text-base font-bold text-[#002855]">سجل الروشتات الطبية السابقة</h3>
                    </div>
                    @php
                        $prescriptions = $record->prescriptions()->latest('prescribed_at')->get();
                    @endphp
                    @if($prescriptions->isNotEmpty())
                        <div class="space-y-4">
                            @foreach($prescriptions as $presc)
                                <div class="border border-gray-150 rounded-xl overflow-hidden shadow-sm">
                                    <div class="bg-gray-50 px-4 py-3 flex items-center justify-between border-b border-gray-100">
                                        <div>
                                            <span class="text-xs font-bold text-gray-700">📋 روشتة زيارة: {{ $presc->visitRecord ? ($presc->visitRecord->diagnosis ?: 'كشف طبي') : 'زيارة عامة' }}</span>
                                            <span class="text-[10px] text-gray-400 mr-3">تاريخ الروشتة: {{ $presc->prescribed_at->format('Y-m-d') }}</span>
                                        </div>
                                        <button onclick="printPrescription({{ $presc->id }})" class="text-xs bg-[#00B4D8] text-white px-3 py-1 rounded shadow-sm hover:bg-[#009bba] transition-all font-semibold">
                                            🖨 طباعة الروشتة
                                        </button>
                                    </div>
                                    <div class="p-4 bg-white">
                                        <ul class="space-y-1.5 text-xs text-gray-700">
                                            @foreach($presc->items as $it)
                                                <li class="flex items-center justify-between border-b border-gray-50 pb-1.5 last:border-0 last:pb-0">
                                                    <span class="font-bold text-sm text-[#002855]">{{ $it->medication_name }}</span>
                                                    <span class="text-gray-500">{{ $it->dosage }} | {{ $it->frequency }} | {{ $it->duration }}</span>
                                                    @if($it->instructions)
                                                        <span class="text-gray-400">({{ $it->instructions }})</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6 text-gray-400 text-sm">لا توجد روشتات طبية سابقة.</div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- MODAL 1: ADD/EDIT VISIT MODAL -->
    @if($isVisitModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" dir="rtl">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 backdrop-blur-sm" aria-hidden="true" wire:click="$set('isVisitModalOpen', false)"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-gray-150">
                    <div class="bg-gradient-to-r from-[#002855] to-[#003f8a] text-white px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-bold">{{ $editingVisitId ? 'تعديل بيانات الكشف' : 'تسجيل كشف وزيارة جديدة' }}</h3>
                        <button wire:click="$set('isVisitModalOpen', false)" class="text-white hover:text-[#00B4D8] font-bold text-xl">&times;</button>
                    </div>

                    <form wire:submit.prevent="saveVisit" class="p-6 space-y-6">
                        <!-- Visit Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">تاريخ الزيارة/الكشف *</label>
                                <input type="date" wire:model="visitDate" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                                @error('visitDate') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">موعد المتابعة (اختياري)</label>
                                <input type="date" wire:model="followUpDate" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">الشكوى الرئيسية (Chief Complaint) *</label>
                            <textarea wire:model="chiefComplaint" required rows="2" placeholder="الشكوى والسبب الرئيسي للزيارة..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right"></textarea>
                            @error('chiefComplaint') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">التشخيص الطبي (Diagnosis)</label>
                                <textarea wire:model="diagnosis" rows="2" placeholder="اكتب تشخيص الحالة الطبية..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">خطة العلاج والملاحظات العلاجية (Treatment Plan)</label>
                                <textarea wire:model="treatmentPlan" rows="2" placeholder="الخطة العلاجية والجرعات..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right"></textarea>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">ملاحظات الفحص السريري (Examination Notes)</label>
                            <textarea wire:model="examinationNotes" rows="2" placeholder="نتائج الفحص والضغط والنبض..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right"></textarea>
                        </div>

                        <!-- Prescription Editor -->
                        <div class="border-t border-gray-150 pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-bold text-[#002855]">أدوية الروشتة (Prescription)</h4>
                                <button type="button" wire:click="addPrescriptionItem" class="text-xs bg-[#00B4D8] text-white px-2.5 py-1 rounded hover:bg-[#009bba]">
                                    + إضافة دواء للروشتة
                                </button>
                            </div>

                            @if(count($prescriptionItems) > 0)
                                <div class="space-y-3">
                                    @foreach($prescriptionItems as $index => $item)
                                        <div class="grid grid-cols-12 gap-2 bg-gray-50 p-3 rounded-lg border border-gray-150 relative">
                                            <div class="col-span-3">
                                                <input type="text" wire:model="prescriptionItems.{{ $index }}.medication_name" placeholder="اسم الدواء *" required class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs text-right">
                                            </div>
                                            <div class="col-span-2">
                                                <input type="text" wire:model="prescriptionItems.{{ $index }}.dosage" placeholder="الجرعة (مثلا 500)" class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs text-right">
                                            </div>
                                            <div class="col-span-2">
                                                <input type="text" wire:model="prescriptionItems.{{ $index }}.frequency" placeholder="التكرار (مثلا 3 مرات)" class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs text-right">
                                            </div>
                                            <div class="col-span-2">
                                                <input type="text" wire:model="prescriptionItems.{{ $index }}.duration" placeholder="المدة (مثلا 7 أيام)" class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs text-right">
                                            </div>
                                            <div class="col-span-2">
                                                <input type="text" wire:model="prescriptionItems.{{ $index }}.instructions" placeholder="تعليمات (مثلا بعد الأكل)" class="w-full px-2 py-1.5 border border-gray-200 rounded text-xs text-right">
                                            </div>
                                            <div class="col-span-1 flex items-center justify-center">
                                                <button type="button" wire:click="removePrescriptionItem({{ $index }})" class="text-rose-600 font-bold hover:text-rose-800 text-lg">&times;</button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-400 italic">لا توجد أدوية مدرجة في الروشتة لهذه الزيارة حالياً.</p>
                            @endif
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">ملاحظات إضافية على الروشتة</label>
                            <input type="text" wire:model="prescriptionNotes" placeholder="تعليمات عامة حول الروشتة..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                        </div>

                        <div class="border-t border-gray-150 pt-4 flex justify-end gap-3">
                            <button type="button" wire:click="$set('isVisitModalOpen', false)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">إلغاء</button>
                            <button type="submit" class="px-5 py-2 bg-[#002855] text-white rounded-lg text-sm font-semibold hover:bg-[#003f8a]">
                                {{ $editingVisitId ? 'تعديل وحفظ الكشف' : 'تسجيل وحفظ الكشف' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 2: ADD/EDIT MEDICAL HISTORY MODAL -->
    @if($isHistoryModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" dir="rtl">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 backdrop-blur-sm" aria-hidden="true" wire:click="$set('isHistoryModalOpen', false)"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-150">
                    <div class="bg-gradient-to-r from-[#002855] to-[#003f8a] text-white px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-bold">{{ $editingHistoryId ? 'تعديل بند التاريخ المرضي' : 'إضافة بند للتاريخ المرضي' }}</h3>
                        <button wire:click="$set('isHistoryModalOpen', false)" class="text-white hover:text-[#00B4D8] font-bold text-xl">&times;</button>
                    </div>

                    <form wire:submit.prevent="saveHistory" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">نوع البند المرضي</label>
                            <select wire:model="historyType" required class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                                <option value="chronic_disease">مرض مزمن</option>
                                <option value="allergy">حساسية</option>
                                <option value="surgery">عملية جراحية</option>
                                <option value="family_history">تاريخ عائلي</option>
                                <option value="other">آخر</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">العنوان أو اسم المرض/البند *</label>
                            <input type="text" wire:model="historyTitle" required placeholder="مثلا: ضغط الدم المرتفع، حساسية بنسلين..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                            @error('historyTitle') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">وصف الحالة / الأعراض</label>
                            <textarea wire:model="historyDescription" rows="2" placeholder="وصف للأعراض أو نوع التحسس..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">تاريخ التشخيص</label>
                                <input type="date" wire:model="historyDiagnosedAt" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">الشدة / الخطورة</label>
                                <select wire:model="historySeverity" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                                    <option value="mild">خفيفة</option>
                                    <option value="moderate">متوسطة</option>
                                    <option value="severe">شديدة خطرة</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="historyIsActive" wire:model="historyIsActive" class="rounded text-[#00B4D8]">
                            <label for="historyIsActive" class="text-xs font-bold text-gray-600">بند نشط ومستمر مع المريض حالياً</label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">ملاحظات إضافية</label>
                            <textarea wire:model="historyNotes" rows="2" placeholder="أي تفاصيل أو ملاحظات للطبيب..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right"></textarea>
                        </div>

                        <div class="border-t border-gray-150 pt-4 flex justify-end gap-3">
                            <button type="button" wire:click="$set('isHistoryModalOpen', false)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">إلغاء</button>
                            <button type="submit" class="px-5 py-2 bg-[#002855] text-white rounded-lg text-sm font-semibold hover:bg-[#003f8a]">حفظ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 3: ADD/EDIT CURRENT MEDICATION MODAL -->
    @if($isMedicationModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" dir="rtl">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 backdrop-blur-sm" aria-hidden="true" wire:click="$set('isMedicationModalOpen', false)"></div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                
                <div class="inline-block align-bottom bg-white rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-150">
                    <div class="bg-gradient-to-r from-[#002855] to-[#003f8a] text-white px-6 py-4 flex items-center justify-between">
                        <h3 class="text-base font-bold">{{ $editingMedicationId ? 'تعديل الدواء المزمن' : 'إضافة دواء مزمن ومستمر' }}</h3>
                        <button wire:click="$set('isMedicationModalOpen', false)" class="text-white hover:text-[#00B4D8] font-bold text-xl">&times;</button>
                    </div>

                    <form wire:submit.prevent="saveMedication" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">اسم الدواء *</label>
                            <input type="text" wire:model="medicationName" required placeholder="مثلا: Concor, Metformin..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                            @error('medicationName') <span class="text-xs text-rose-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">الجرعة</label>
                                <input type="text" wire:model="medicationDosage" placeholder="مثلا: 5mg, 500mg" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">التكرار</label>
                                <input type="text" wire:model="medicationFrequency" placeholder="مثلا: مرة صباحاً" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">تاريخ البداية</label>
                                <input type="date" wire:model="medicationStartDate" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5">تاريخ الانتهاء (إن وجد)</label>
                                <input type="date" wire:model="medicationEndDate" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">الطبيب الذي وصفه</label>
                            <input type="text" wire:model="medicationPrescribedBy" placeholder="مثلا: د. محمد هلال، طبيب باطنة آخر..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right">
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="medicationIsActive" wire:model="medicationIsActive" class="rounded text-[#00B4D8]">
                            <label for="medicationIsActive" class="text-xs font-bold text-gray-600">الدواء نشط ويتناوله المريض حالياً</label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1.5">ملاحظات</label>
                            <textarea wire:model="medicationNotes" rows="2" placeholder="ملاحظات حول طريقة الاستعمال أو الآثار..." class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-[#00B4D8] focus:border-[#00B4D8] text-right"></textarea>
                        </div>

                        <div class="border-t border-gray-150 pt-4 flex justify-end gap-3">
                            <button type="button" wire:click="$set('isMedicationModalOpen', false)" class="px-4 py-2 border border-gray-200 rounded-lg text-sm text-gray-600 hover:bg-gray-50">إلغاء</button>
                            <button type="submit" class="px-5 py-2 bg-[#002855] text-white rounded-lg text-sm font-semibold hover:bg-[#003f8a]">حفظ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Inline Print Script for Prescription -->
    <script>
        function printPrescription(prescriptionId) {
            const printWindow = window.open(`/admin/prescriptions/${prescriptionId}/print`, '_blank', 'width=800,height=900');
            printWindow.focus();
        }
    </script>
</x-filament-panels::page>
