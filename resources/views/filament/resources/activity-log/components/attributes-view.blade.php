@php
    $attributes = $getState() ?? [];
    $ignoredFields = ['updated_at', 'created_at', 'id', 'deleted_at', 'password', 'remember_token'];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($attributes as $key => $value)
        @if(!in_array($key, $ignoredFields) && !empty($value))
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
            <div class="flex flex-col p-2 bg-gray-50 rounded-lg">
                <span class="text-xs font-bold text-gray-500 uppercase">{{ $label }}</span>
                <span class="text-sm text-gray-900">{{ is_array($value) ? json_encode($value) : $value }}</span>
            </div>
        @endif
    @endforeach
</div>
