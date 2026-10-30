@if ($this->canManageSheets())
    @php($sheets = app(\App\Services\GoogleSheets\BookingSheetsSync::class)->connection())
    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600" role="status">
        @if ($sheets->connected_at)
            <span class="font-semibold text-primary-600">Google Sheets متصل</span>
            <span> · {{ $sheets->spreadsheet_title }}</span>
            @if(preg_match('/^[A-Za-z0-9_-]+$/D', (string) $sheets->spreadsheet_id))
                <a class="ms-2 font-medium text-primary-600 underline" href="https://docs.google.com/spreadsheets/d/{{ $sheets->spreadsheet_id }}/edit{{ $sheets->worksheet_id !== null ? '#gid='.(int) $sheets->worksheet_id : '' }}" target="_blank" rel="noopener noreferrer">فتح شيت الحجوزات المرتبط</a>
            @endif
            <span> · آخر مزامنة: {{ $sheets->last_synced_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') ?? 'بانتظار المزامنة الأولى' }}</span>
            @if ($sheets->last_error)<p class="mt-2 text-warning-600">{{ $sheets->last_error }}</p>@endif
        @else
            الحجوزات محفوظة على الموقع. يمكنك ربط Google Sheets من الزر أعلى الصفحة.
        @endif
        @php($email = app(\App\Services\GoogleSheets\BookingSheetsSync::class)->serviceAccountEmail())
        @if ($email)
            <button type="button" class="ms-2 font-medium text-primary-600" x-data="{ copied: false }" x-on:click="navigator.clipboard.writeText(@js($email)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" x-text="copied ? 'تم نسخ بريد الربط' : 'نسخ بريد الربط'">نسخ بريد الربط</button>
        @endif
        @foreach ($this->sheetConflicts() as $conflict)
            <details class="mt-3 rounded-lg border border-amber-200 p-3">
                <summary>تعارض يحتاج مراجعة — طلب رقم {{ $conflict->website_booking_request_id }} (تم الاحتفاظ بالنسختين)</summary>
                @foreach ($conflict->conflicts ?? [] as $field => $versions)
                    @if ($field !== '_history' && is_array($versions))
                        <p class="mt-2">{{ ['patient_name'=>'الاسم','patient_phone'=>'الهاتف','booking_date'=>'تاريخ الزيارة','service_name'=>'الخدمة','branch_name'=>'الفرع','status'=>'الحالة','admin_notes'=>'الملاحظات'][$field] ?? $field }}: تعديل الشيت «{{ $field === 'status' ? (\App\Models\WebsiteBookingRequest::STATUSES[$versions['sheet'] ?? ''] ?? ($versions['sheet'] ?? '')) : ($versions['sheet'] ?? '') }}» — تعديل اللوحة «{{ $field === 'status' ? (\App\Models\WebsiteBookingRequest::STATUSES[$versions['local'] ?? ''] ?? ($versions['local'] ?? '')) : ($versions['local'] ?? '') }}»</p>
                        @if (($versions['reason'] ?? '') === 'invalid_value')
                            <p class="mt-1 text-warning-600">قيمة الشيت غير صالحة لهذا الحقل. صححها في الشيت أو اعتمد قيمة اللوحة. التاريخ بصيغة سنة-شهر-يوم، والخدمة والفرع من القوائم المعتمدة.</p>
                        @elseif (($versions['reason'] ?? '') === 'missing_baseline')
                            <p class="mt-1 text-gray-500">يوجد اختلاف سابق لتوسيع المزامنة؛ اختر القيمة المطلوبة لبدء متابعة هذا الحقل.</p>
                        @endif
                        <div class="mt-2 flex flex-wrap gap-3">
                            <button type="button" class="font-medium text-primary-600" wire:loading.attr="disabled" wire:click="resolveSheetConflict({{ $conflict->website_booking_request_id }}, '{{ $field }}', 'sheet')">اعتماد تعديل الشيت</button>
                            <button type="button" class="font-medium text-primary-600" wire:loading.attr="disabled" wire:click="resolveSheetConflict({{ $conflict->website_booking_request_id }}, '{{ $field }}', 'local')">اعتماد تعديل اللوحة</button>
                        </div>
                    @endif
                @endforeach
            </details>
        @endforeach
    </div>
@endif
