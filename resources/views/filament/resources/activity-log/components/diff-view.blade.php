@php
    $properties = $getState();
    $attributes = $properties['attributes'] ?? [];
    $old = $properties['old'] ?? [];
    
    $ignoredFields = ['updated_at', 'created_at', 'id', 'deleted_at', 'password', 'remember_token'];
@endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm text-right text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-right">الحقل</th>
                <th class="px-4 py-2 text-right">القيمة السابقة</th>
                <th class="px-4 py-2 text-right">القيمة الجديدة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attributes as $key => $value)
                @if(!in_array($key, $ignoredFields) && isset($old[$key]) && $old[$key] != $value)
                    @php
                        $label = match($key) {
                            'name' => 'الاسم',
                            'description' => 'الوصف',
                            'is_active' => 'حالة النشاط',
                            'order' => 'الترتيب',
                            'patient_name' => 'اسم المريض',
                            'patient_phone' => 'رقم الهاتف',
                            'booking_date' => 'تاريخ الحجز',
                            'booking_time' => 'وقت الحجز',
                            'status' => 'الحالة',
                            'notes' => 'ملاحظات',
                            'email' => 'البريد الإلكتروني',
                            'title' => 'العنوان',
                            'content' => 'المحتوى',
                            default => $key
                        };
                    @endphp
                    <tr class="bg-white border-b">
                        <td class="px-4 py-2 font-medium text-gray-900">{{ $label }}</td>
                        <td class="px-4 py-2 text-danger-600 line-through">{{ is_array($old[$key]) ? json_encode($old[$key]) : $old[$key] }}</td>
                        <td class="px-4 py-2 text-success-600">{{ is_array($value) ? json_encode($value) : $value }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>
