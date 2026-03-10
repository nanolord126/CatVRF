<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Карточка 1: Процент дисциплины --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Процент выполнения</span>
                <span class="p-2 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-gray-900 dark:text-gray-100">92%</h3>
                <p class="text-xs text-green-600 font-medium mt-1">+4.2% за неделю</p>
            </div>
        </div>

        {{-- Карточка 2: Питомцы --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Уход за питомцами</span>
                <span class="p-2 bg-purple-50 dark:bg-purple-900/20 text-purple-600 rounded-lg text-lg">🐾</span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-gray-900 dark:text-gray-100">85%</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Все вакцинации по графику</p>
            </div>
        </div>

        {{-- Карточка 3: Задач завершено --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Задач выполнено</span>
                <span class="p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-3xl font-black text-gray-900 dark:text-gray-100">124</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">За последние 30 дней</p>
            </div>
        </div>

        {{-- Карточка 4: Ближайший визит --}}
        <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Ближайший визит к врачу</span>
                <span class="p-2 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </span>
            </div>
            <div class="mt-4">
                <h3 class="text-xl font-black text-gray-900 dark:text-gray-100">Через 3 дня</h3>
                <p class="text-xs text-red-600 font-medium mt-1">Стоматолог: Гектор 🐾</p>
            </div>
        </div>
    </div>

    @livewire(\App\Filament\Tenant\Widgets\HealthComplianceChart::class)

    <div class="mt-8">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
