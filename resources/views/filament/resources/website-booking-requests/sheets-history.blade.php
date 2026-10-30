<div class="overflow-x-auto">
    <p class="mb-4 text-sm text-gray-500">آخر 100 تغيير. يحتفظ السجل بالقيم السابقة وتعديلات الطرفين للمراجعة.</p>
    <table class="w-full text-right text-sm">
        <thead><tr><th class="p-2">الطلب</th><th class="p-2">الحقل</th><th class="p-2">القيمة السابقة</th><th class="p-2">تعديل اللوحة</th><th class="p-2">تعديل الشيت</th><th class="p-2">القيمة المعتمدة</th><th class="p-2">المصدر</th><th class="p-2">الوقت</th></tr></thead>
        <tbody>
            @forelse ($changes as $change)
                <tr class="border-t border-gray-200 align-top">
                    <td class="p-2">{{ $change->website_booking_request_id }}</td>
                    <td class="p-2">{{ ['patient_name'=>'الاسم','patient_phone'=>'الهاتف','booking_date'=>'تاريخ الزيارة','service_name'=>'الخدمة','branch_name'=>'الفرع','status'=>'الحالة','admin_notes'=>'الملاحظات'][$change->field] ?? $change->field }}</td>
                    <td class="p-2 whitespace-pre-wrap">{{ $change->base_value }}</td><td class="p-2 whitespace-pre-wrap">{{ $change->local_value }}</td><td class="p-2 whitespace-pre-wrap">{{ $change->sheet_value }}</td><td class="p-2 whitespace-pre-wrap">{{ $change->selected_value }}</td><td class="p-2">{{ ['append'=>'إضافة للشيت','observed'=>'رصد تعديل','import'=>'استيراد من الشيت','push'=>'إرسال للشيـت','conflict'=>'تعارض للمراجعة','resolve_local'=>'اعتماد تعديل اللوحة','resolve_sheet'=>'اعتماد تعديل الشيت','resolution_stale'=>'تغيير أثناء المراجعة','legacy_conflict'=>'تعارض سابق محفوظ'][$change->source] ?? $change->source }}</td><td class="p-2 whitespace-nowrap">{{ $change->created_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-4 text-center text-gray-500">لا توجد تغييرات مسجلة بعد.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
