<div class="p-4 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-900 dark:border-gray-700">
    <div class="text-xs font-bold text-gray-500 mb-4 uppercase tracking-wider">معاينة جوجل (Google Preview)</div>

    <div class="space-y-6">
        <!-- Desktop Preview -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded shadow-sm border border-gray-100 dark:border-gray-700 max-w-xl">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-6 h-6 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-[10px]">🌐</div>
                <div class="flex flex-col">
                    <span class="text-[12px] text-gray-700 dark:text-gray-300 leading-tight">site.com</span>
                    <span class="text-[10px] text-gray-400 leading-tight">https://site.com › ...</span>
                </div>
            </div>
            <h3 class="text-[18px] text-[#1a0dab] dark:text-[#8ab4f8] hover:underline cursor-pointer leading-tight mb-1 truncate">
                {{ $getRecord()->meta_title ?? 'عنوان الصفحة يظهر هنا' }}
            </h3>
            <p class="text-[14px] text-[#4d5156] dark:text-[#bdc1c6] leading-snug line-clamp-2">
                {{ $getRecord()->meta_description ?? 'وصف الصفحة سيظهر هنا في نتائج البحث. تأكد من كتابة وصف جذاب ومختصر يحتوي على الكلمات المفتاحية.' }}
            </p>
        </div>

        <!-- Mobile Preview -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded shadow-sm border border-gray-100 dark:border-gray-700 max-w-[360px]">
            <div class="flex items-center gap-2 mb-2">
                 <div class="w-5 h-5 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-[8px]">🌐</div>
                 <span class="text-[12px] text-gray-600 dark:text-gray-400">site.com</span>
            </div>
            <h3 class="text-[16px] text-[#1a0dab] dark:text-[#8ab4f8] leading-tight mb-1">
                {{ $getRecord()->meta_title ?? 'عنوان الصفحة يظهر هنا' }}
            </h3>
            <p class="text-[12px] text-[#4d5156] dark:text-[#bdc1c6] leading-snug line-clamp-3">
                {{ $getRecord()->meta_description ?? 'وصف الصفحة سيظهر هنا في نتائج البحث. تأكد من كتابة وصف جذاب ومختصر يحتوي على الكلمات المفتاحية.' }}
            </p>
        </div>
    </div>
</div>
