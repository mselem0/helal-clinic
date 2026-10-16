<x-filament-widgets::widget><x-filament::section heading="إدارة موقع هلال"><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
@foreach(['filament.admin.pages.theme-editor' => 'المحتوى والثيمات', 'filament.admin.resources.services.index' => 'الخدمات', 'filament.admin.resources.clinic-branches.index' => 'الفروع', 'filament.admin.pages.seo-management' => 'محركات البحث', 'filament.admin.resources.articles.index' => 'المقالات', 'filament.admin.resources.bookings.index' => 'طلبات المواعيد'] as $route => $label)
@if(\Illuminate\Support\Facades\Route::has($route))<a class="rounded-xl bg-primary-50 p-5 font-semibold text-primary-800" href="{{ route($route) }}">{{ $label }}</a>@endif
@endforeach
<a class="rounded-xl bg-gray-50 p-5" href="{{ route('home') }}" target="_blank">فتح الموقع</a></div><p class="mt-4 text-sm text-gray-500">راجعي أرقام التواصل وعناوين الفروع والمحتوى الطبي قبل النشر. الحجوزات طلبات مبدئية تحتاج تأكيد العيادة.</p></x-filament::section></x-filament-widgets::widget>
