<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @php $stats = $this->getStats(); @endphp
        
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="text-sm font-medium text-gray-500">إجمالي السجلات</div>
            <div class="text-2xl font-bold mt-1">{{ number_format($stats['total_records']) }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="text-sm font-medium text-emerald-500">تمت أرشفتها</div>
            <div class="text-2xl font-bold mt-1 text-emerald-600">{{ number_format($stats['synced_count']) }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="text-sm font-medium text-amber-500">قيد الانتظار</div>
            <div class="text-2xl font-bold mt-1 text-amber-600">{{ number_format($stats['pending_count']) }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="text-sm font-medium text-rose-500">أخطاء المزامنة</div>
            <div class="text-2xl font-bold mt-1 text-rose-600">{{ number_format($stats['failed_count']) }}</div>
        </div>
    </div>

    <div class="mt-8 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-bold">تفاصيل المزامنة حسب النوع</h3>
        </div>
        <table class="w-full text-right">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-900/50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3">نوع البيانات</th>
                    <th class="px-6 py-3">إجمالي السجلات</th>
                    <th class="px-6 py-3">الحالة</th>
                    <th class="px-6 py-3 text-left">نسبة الإنجاز</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($this->getSyncData() as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                        <td class="px-6 py-4 font-bold">{{ $item['name'] }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ number_format($item['count']) }}</td>
                        <td class="px-6 py-4">
                            @if($item['synced'] >= $item['count'] && $item['count'] > 0)
                                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">مكتمل ✅</span>
                            @else
                                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">جاري المزامنة...</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php 
                                $percent = $item['count'] > 0 ? ($item['synced'] / $item['count']) * 100 : 100;
                            @endphp
                            <div class="w-32 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-primary-600 transition-all duration-1000" style="width: {{ $percent }}%"></div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-8 p-6 bg-primary-50 dark:bg-primary-900/20 rounded-2xl border border-primary-100 dark:border-primary-800">
        <div class="flex gap-4">
            <div class="p-3 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <x-heroicon-o-light-bulb class="w-6 h-6 text-amber-500" />
            </div>
            <div>
                <h4 class="font-bold text-primary-900 dark:text-primary-100 italic">كيف تعمل الذاكرة الشاملة؟</h4>
                <p class="text-sm text-primary-700 dark:text-primary-300 mt-1 leading-relaxed">
                    يعتمد النظام الآن على **الاستعلام المباشر (Native Tool Calling)**. بدلاً من أرشفة البيانات بشكل مسبق، يقوم المساعد الذكي بالتحدث مباشرة مع قاعدة بيانات العيادة (SQL) في لحظة سؤالك. هذا يضمن أن الأرقام والإحصائيات والبيانات دقيقة ومحدثة بنسبة 100% دون أي تأخير في المزامنة.
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
